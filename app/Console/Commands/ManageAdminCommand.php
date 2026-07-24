<?php

namespace App\Console\Commands;

use App\Models\DeveloperProfile;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ManageAdminCommand extends Command
{
    protected $signature = 'admin:create
        {email : Adresse email du compte à promouvoir, ou à créer s\'il n\'existe pas}
        {--name= : Nom complet (uniquement si le compte n\'existe pas encore)}
        {--password= : Mot de passe (uniquement si le compte n\'existe pas encore) — sinon demandé de façon masquée}
        {--force : Ignore la confirmation interactive (usage scripté/CI uniquement)}';

    protected $description = "Promeut un compte existant au rôle admin, ou en crée un nouveau si l'email est inconnu. "
        ."Aucun rôle 'admin' n'est jamais accessible depuis /register — c'est la seule façon d'en obtenir un. "
        .'Un seul compte admin est autorisé à la fois (voir admin:revoke pour en changer).';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        $existingAdmin = User::where('role', 'admin')->first();

        if ($existingAdmin && (! $user || $existingAdmin->id !== $user->id)) {
            $this->error("Un compte admin existe déjà : {$existingAdmin->name} <{$existingAdmin->email}>.");
            $this->comment("Un seul compte admin est autorisé à la fois. Pour le remplacer, révoque d'abord l'actuel : php artisan admin:revoke {$existingAdmin->email}");

            return self::FAILURE;
        }

        return $user ? $this->promote($user) : $this->createNew($email);
    }

    private function promote(User $user): int
    {
        if ($user->isAdmin()) {
            $this->info("{$user->email} est déjà admin.");

            return self::SUCCESS;
        }

        $confirmed = $this->option('force')
            || $this->confirm("Compte trouvé : {$user->name} <{$user->email}> (rôle actuel : {$user->role}). Le promouvoir en admin ?");

        if (! $confirmed) {
            $this->comment('Annulé.');

            return self::SUCCESS;
        }

        $previousRole = $user->role;
        $user->update(['role' => 'admin']);

        Log::info('Compte promu admin via admin:create', [
            'user_id' => $user->id,
            'email' => $user->email,
            'previous_role' => $previousRole,
        ]);

        $this->info("✔ {$user->email} est maintenant admin.");

        return self::SUCCESS;
    }

    private function createNew(string $email): int
    {
        $name = $this->option('name') ?: $this->ask('Nom complet');

        if (! $name) {
            $this->error('Le nom est requis pour créer un nouveau compte.');

            return self::FAILURE;
        }

        $password = $this->option('password');

        if (! $password) {
            $password = $this->secret('Mot de passe (min. 8 caractères)');

            if ($password !== $this->secret('Confirmer le mot de passe')) {
                $this->error('Les mots de passe ne correspondent pas.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', Password::min(8)]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($name, $email, $password) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => 'admin',
                'username' => app(AuthService::class)->generateUsername($name),
                'email_verified_at' => now(),
            ]);

            DeveloperProfile::create(['user_id' => $user->id]);

            return $user;
        });

        Log::info('Compte admin créé via admin:create', ['user_id' => $user->id, 'email' => $user->email]);

        $this->info("✔ Compte admin créé : {$user->email}");

        return self::SUCCESS;
    }
}
