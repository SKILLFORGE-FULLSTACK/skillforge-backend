<?php

namespace App\Services\Auth;

use App\Models\DeveloperProfile;
use App\Models\RecruiterProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthService
{
    public function register(array $data): array
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'role'     => $data['role'],
                'username' => $this->generateUsername($data['name']),
            ]);

            if ($data['role'] === 'developer') {
                DeveloperProfile::create(['user_id' => $user->id]);
            } else {
                RecruiterProfile::create([
                    'user_id'      => $user->id,
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

    private function generateUsername(string $name): string
    {
        $base     = Str::slug($name);
        $username = $base;
        $counter  = 1;

        while (User::where('username', $username)->exists()) {
            $username = "{$base}-{$counter}";
            $counter++;
        }

        return $username;
    }
}
