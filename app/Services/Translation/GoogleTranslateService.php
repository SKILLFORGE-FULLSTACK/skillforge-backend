<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTranslateService implements TranslatorService
{
    private string $baseUrl = 'https://translation.googleapis.com/language/translate/v2';

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) config('services.google_translate.api_key');
    }

    /**
     * Traduit un texte vers la langue cible. Retourne null si la clé API est
     * absente ou si l'appel échoue — l'appelant doit décider du repli
     * (généralement : laisser le champ traduit vide et réessayer plus tard).
     */
    public function translate(string $text, string $target, string $source = 'fr'): ?string
    {
        if (trim($text) === '') {
            return $text;
        }

        if ($this->apiKey === '') {
            Log::warning('GoogleTranslateService: GOOGLE_TRANSLATE_API_KEY absente, traduction ignorée.');

            return null;
        }

        try {
            $response = Http::asForm()->post("{$this->baseUrl}?key={$this->apiKey}", [
                'q' => $text,
                'source' => $source,
                'target' => $target,
                'format' => 'text',
            ]);

            if ($response->failed()) {
                Log::error('Google Translate error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('data.translations.0.translatedText');
        } catch (\Throwable $e) {
            Log::error('Google Translate exception', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
