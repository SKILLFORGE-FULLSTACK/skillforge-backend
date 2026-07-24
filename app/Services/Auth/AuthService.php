<?php

namespace App\Services\Auth;

use App\Models\DeveloperProfile;
use App\Models\RecruiterProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class AuthService
{
    /**
     * Providers OAuth supportés, mappés vers la colonne d'identifiant sur `users`.
     */
    public const OAUTH_PROVIDERS = [
        'google' => 'google_id',
        'github' => 'github_id',
    ];

    public function register(array $data): array
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'username' => $this->generateUsername($data['name']),
            ]);

            if ($data['role'] === 'developer') {
                DeveloperProfile::create(['user_id' => $user->id]);
            } else {
                RecruiterProfile::create([
                    'user_id' => $user->id,
                    'company_name' => $data['company_name'],
                ]);
            }

            return $user;
        });

        $token = $user->createToken('skillforge')->plainTextToken;

        return compact('user', 'token');
    }

    public function login(array $data): ?array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! \Hash::check($data['password'], $user->password)) {
            return null;
        }

        $user->tokens()->where('name', 'skillforge')->delete();
        $token = $user->createToken('skillforge')->plainTextToken;

        return compact('user', 'token');
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Connecte ou crée un utilisateur à partir d'un profil OAuth (Google / GitHub).
     * Lie le compte social à un compte existant via l'email si nécessaire.
     *
     * $role/$companyName ne s'appliquent qu'à la CRÉATION d'un nouveau compte
     * (un compte existant conserve son rôle actuel, même si l'utilisateur
     * relance le flow OAuth depuis le bouton "recruteur").
     */
    public function loginOrRegisterWithProvider(
        string $provider,
        SocialiteUser $socialUser,
        string $role = 'developer',
        ?string $companyName = null,
    ): array {
        $idColumn = self::OAUTH_PROVIDERS[$provider];

        $user = User::where($idColumn, $socialUser->getId())->first();

        if (! $user && $socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();
        }

        if ($user) {
            $user->update(array_filter([
                $idColumn => $socialUser->getId(),
                'avatar_url' => $user->avatar_url ?: $socialUser->getAvatar(),
                'github_username' => $provider === 'github' ? $socialUser->getNickname() : null,
            ]));
        } else {
            $user = DB::transaction(function () use ($provider, $idColumn, $socialUser, $role, $companyName) {
                $name = $socialUser->getName() ?: $socialUser->getNickname() ?: 'Utilisateur';

                $user = User::create([
                    'name' => $name,
                    'email' => $socialUser->getEmail(),
                    'username' => $this->generateUsername($name),
                    'avatar_url' => $socialUser->getAvatar(),
                    'role' => $role,
                    'email_verified_at' => now(),
                    $idColumn => $socialUser->getId(),
                    'github_username' => $provider === 'github' ? $socialUser->getNickname() : null,
                ]);

                if ($role === 'recruiter') {
                    RecruiterProfile::create([
                        'user_id' => $user->id,
                        'company_name' => $companyName,
                    ]);
                } else {
                    DeveloperProfile::create(['user_id' => $user->id]);
                }

                return $user;
            });
        }

        $user->tokens()->where('name', 'skillforge')->delete();
        $token = $user->createToken('skillforge')->plainTextToken;

        return compact('user', 'token');
    }

    public function generateUsername(string $name): string
    {
        $base = Str::slug($name);
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = "{$base}-{$counter}";
            $counter++;
        }

        return $username;
    }
}
