<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Redirige le navigateur vers l'écran de consentement Google / GitHub.
     * Le rôle choisi (et le nom d'entreprise pour un recruteur) transite via
     * le paramètre `state` du flow OAuth pour être récupéré au callback —
     * c'est le seul moyen de faire survivre ces infos à la redirection vers
     * le provider (stateless : pas de session côté API).
     */
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        $role = $request->query('role') === 'recruiter' ? 'recruiter' : 'developer';
        $companyName = $role === 'recruiter'
            ? mb_substr((string) $request->query('company_name', ''), 0, 255)
            : null;

        $state = base64_encode(json_encode([
            'role' => $role,
            'company_name' => $companyName,
        ]));

        $driver = Socialite::driver($provider)->stateless()->with(['state' => $state]);

        if ($provider === 'github') {
            $driver->scopes(['read:user', 'user:email']);
        }

        return $driver->redirect();
    }

    /**
     * Callback appelé par le provider OAuth après authentification.
     * Crée/relie le compte, génère un token Sanctum, puis redirige
     * vers le frontend avec le token en query string.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        $frontendUrl = rtrim(config('services.frontend_url'), '/');
        [$role, $companyName] = $this->decodeState($request->query('state'));

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            if (! $socialUser->getEmail()) {
                return redirect("{$frontendUrl}/auth/callback?error=email_missing");
            }

            $result = $this->authService->loginOrRegisterWithProvider(
                $provider,
                $socialUser,
                $role,
                $companyName,
            );
        } catch (\Throwable $e) {
            Log::error("Échec de l'authentification OAuth ({$provider})", [
                'message' => $e->getMessage(),
            ]);

            return redirect("{$frontendUrl}/auth/callback?error=oauth_failed");
        }

        return redirect("{$frontendUrl}/auth/callback?token={$result['token']}");
    }

    /**
     * @return array{0: string, 1: ?string} [role, companyName]
     */
    private function decodeState(?string $state): array
    {
        if (! $state) {
            return ['developer', null];
        }

        $decoded = json_decode(base64_decode($state), true);
        $role = ($decoded['role'] ?? null) === 'recruiter' ? 'recruiter' : 'developer';
        $companyName = $decoded['company_name'] ?? null;

        return [$role, $companyName ?: null];
    }

    private function ensureProviderIsSupported(string $provider): void
    {
        abort_unless(
            array_key_exists($provider, AuthService::OAUTH_PROVIDERS),
            404,
            'Provider OAuth non supporté.'
        );
    }
}
