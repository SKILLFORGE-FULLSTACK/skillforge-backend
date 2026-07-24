<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RevokeAdminCommand extends Command
{
    protected $signature = 'admin:revoke
        {email : Adresse email du compte admin à rétrograder}
        {--role=developer : Rôle à réattribuer après révocation (developer|recruiter)}
        {--force : Ignore la confirmation interactive (usage scripté/CI uniquement)}';

    protected $description = "Rétrograde le compte admin actuel — nécessaire avant d'en promouvoir un nouveau via admin:create "
        .'(un seul compte admin est autorisé à la fois).';

    public function handle(): int
    {
        $email = $this->argument('email');
        $newRole = $this->option('role');

        if (! in_array($newRole, ['developer', 'recruiter'], true)) {
            $this->error("--role doit être 'developer' ou 'recruiter'.");

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun compte trouvé pour {$email}.");

            return self::FAILURE;
        }

        if (! $user->isAdmin()) {
            $this->info("{$user->email} n'est pas admin (rôle actuel : {$user->role}).");

            return self::SUCCESS;
        }

        $confirmed = $this->option('force')
            || $this->confirm("Rétrograder {$user->name} <{$user->email}> de admin vers {$newRole} ?");

        if (! $confirmed) {
            $this->comment('Annulé.');

            return self::SUCCESS;
        }

        $user->update(['role' => $newRole]);

        Log::info('Compte rétrogradé depuis admin via admin:revoke', [
            'user_id' => $user->id,
            'email' => $user->email,
            'new_role' => $newRole,
        ]);

        $this->info("✔ {$user->email} n'est plus admin (rôle : {$newRole}).");

        return self::SUCCESS;
    }
}
