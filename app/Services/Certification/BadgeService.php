<?php

namespace App\Services\Certification;

use App\Models\CertificationSubmission;
use App\Models\UserBadge;
use Illuminate\Support\Str;

class BadgeService
{
    public function generateBadge(CertificationSubmission $submission): UserBadge
    {
        $certification = $submission->certification;

        // Couleur selon le score
        $color = match (true) {
            $submission->score_total >= 90 => '#F59E0B', // Or
            $submission->score_total >= 75 => '#7C3AED', // Violet
            default                        => '#2563EB', // Bleu
        };

        return UserBadge::create([
            'user_id'         => $submission->user_id,
            'certification_id' => $certification->id,
            'submission_id'   => $submission->id,
            'badge_type'      => 'certification',
            'verify_token'    => Str::random(32),
            'title'           => $certification->title,
            'description'     => "Certifié {$certification->title} avec un score de {$submission->score_total}/100",
            'score'           => $submission->score_total,
            'badge_image_url' => $certification->badge_svg_url,
            'issued_at'       => now(),
            'expires_at'      => now()->addMonths($certification->validity_months),
            'is_public'       => true,
        ]);
    }
}
