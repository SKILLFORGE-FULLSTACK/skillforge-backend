<?php

namespace App\Services\Certification;

use App\Models\UserBadge;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;

class CertificateGeneratorService
{
    /**
     * Génère le PDF du certificat personnalisé pour ce badge. Retourne les
     * octets du PDF — l'appelant décide où le stocker.
     */
    public function generate(UserBadge $badge): string
    {
        $verifyUrl = rtrim((string) config('services.frontend_url'), '/')."/verify/{$badge->verify_token}";

        $qr = (new Builder(
            writer: new PngWriter,
            data: $verifyUrl,
            size: 200,
            margin: 8,
        ))->build();

        $accentColor = match (true) {
            $badge->score >= 90 => '#B8860B', // Or
            $badge->score >= 75 => '#7C3AED', // Violet
            default => '#2563EB',             // Bleu
        };

        $pdf = Pdf::loadView('certificates.certificate', [
            // Le nom est mis en forme (Title Case) uniquement pour l'affichage
            // sur le certificat — la valeur en base n'est jamais modifiée.
            'recipientName' => Str::title($badge->user->name),
            'certificationTitle' => $badge->title,
            'score' => number_format((float) $badge->score, 0),
            'level' => ucfirst((string) $badge->certification?->level ?? 'Certifié'),
            'issuedAt' => $badge->issued_at->translatedFormat('d F Y'),
            'verifyCode' => strtoupper(substr($badge->verify_token, 0, 12)),
            'qrDataUri' => $qr->getDataUri(),
            'accentColor' => $accentColor,
        ])->setPaper('a4', 'landscape');

        return $pdf->output();
    }
}
