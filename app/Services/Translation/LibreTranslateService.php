<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LibreTranslateService implements TranslatorService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.libretranslate.url'), '/');
    }

    public function translate(string $text, string $target, string $source = 'fr'): ?string
    {
        if (trim($text) === '') {
            return $text;
        }

        if ($this->baseUrl === '') {
            Log::warning('LibreTranslateService: LIBRETRANSLATE_URL absente, traduction ignorée.');

            return null;
        }

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/translate", [
                'q' => $text,
                'source' => $source,
                'target' => $target,
                'format' => 'text',
            ]);

            if ($response->failed()) {
                Log::error('LibreTranslate error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('translatedText');
        } catch (\Throwable $e) {
            Log::error('LibreTranslate exception', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
