<?php

namespace App\Services\Interview;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PiperTtsService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.piper.url'), '/');
    }

    /**
     * Synthèse vocale française via Piper (auto-hébergé, gratuit — voir
     * docker/piper/Dockerfile). Retourne les octets WAV, ou null en cas
     * d'échec/indisponibilité.
     */
    public function synthesizeSpeech(string $text): ?string
    {
        if ($this->baseUrl === '') {
            Log::warning('PiperTtsService: PIPER_TTS_URL absente, synthèse ignorée.');

            return null;
        }

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/synthesize", [
                'text' => $text,
            ]);

            if ($response->failed()) {
                Log::error('Piper TTS error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::error('Piper TTS exception', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
