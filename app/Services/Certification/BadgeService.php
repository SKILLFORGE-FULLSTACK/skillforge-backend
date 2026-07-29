<?php

namespace App\Services\Certification;

use App\Models\CertificationSubmission;
use App\Models\UserBadge;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BadgeService
{
    public function __construct(private CertificateGeneratorService $certificateGenerator) {}

    public function generateBadge(CertificationSubmission $submission): UserBadge
    {
        $certification = $submission->certification;

        // Couleur selon le score
        $color = match (true) {
            $submission->score_total >= 90 => '#F59E0B', // Or
            $submission->score_total >= 75 => '#7C3AED', // Violet
            default => '#2563EB', // Bleu
        };

        $badge = UserBadge::create([
            'user_id' => $submission->user_id,
            'certification_id' => $certification->id,
            'submission_id' => $submission->id,
            'badge_type' => 'certification',
            'verify_token' => Str::random(32),
            'title' => $certification->title,
            'description' => "Certifié {$certification->title} avec un score de {$submission->score_total}/100",
            'score' => $submission->score_total,
            'badge_image_url' => $certification->badge_svg_url,
            'issued_at' => now(),
            'expires_at' => now()->addMonths($certification->validity_months),
            'is_public' => true,
        ]);

        $this->attachCertificatePdf($badge->fresh(['user', 'certification']));

        return $badge->fresh();
    }

    /**
     * Génère le PDF personnalisé et le stocke — séparé de la création du
     * badge pour ne jamais faire échouer la délivrance du badge si la
     * génération PDF plante (l'utilisateur garde son badge même sans PDF,
     * régénérable a posteriori).
     */
    public function attachCertificatePdf(UserBadge $badge): void
    {
        try {
            $pdf = $this->certificateGenerator->generate($badge);
            $path = "certificates/{$badge->id}.pdf";
            Storage::disk('s3')->put($path, $pdf);
            $badge->update(['certificate_url' => Storage::disk('s3')->url($path)]);
        } catch (\Throwable $e) {
            Log::error('Génération du certificat PDF échouée', [
                'badge_id' => $badge->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
