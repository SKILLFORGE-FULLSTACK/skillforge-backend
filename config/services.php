<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),

        // Entretien vocal IA : Speech-to-Text (Whisper) et Text-to-Speech (Orpheus).
        // ATTENTION : playai-tts a été décommissionné par Groq. Le remplaçant,
        // canopylabs/orpheus-v1-english, ne supporte QUE l'anglais (et
        // canopylabs/orpheus-arabic-saudi l'arabe) — pas de voix française
        // actuellement disponible côté Groq. Nécessite d'accepter les conditions
        // sur https://console.groq.com/playground?model=canopylabs%2Forpheus-v1-english
        'stt_model' => env('GROQ_STT_MODEL', 'whisper-large-v3'),
        'tts_model' => env('GROQ_TTS_MODEL', 'canopylabs/orpheus-v1-english'),
        'tts_voice' => env('GROQ_TTS_VOICE', 'troy'),
        'tts_format' => env('GROQ_TTS_FORMAT', 'wav'),
    ],

    'judge0' => [
        'url' => env('JUDGE0_URL', 'http://localhost:2358'),
    ],
    'github' => [
        'token' => env('GITHUB_TOKEN'), // optionnel, augmente les rate limits

        // OAuth (connexion "Se connecter avec GitHub")
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'google_translate' => [
        // Clé API "Cloud Translation API" (console.cloud.google.com/apis/credentials)
        // — distincte de la clé OAuth ci-dessus, même projet GCP possible.
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
    ],

    'libretranslate' => [
        // Instance auto-hébergée (voir docker-compose.yml, service "libretranslate")
        'url' => env('LIBRETRANSLATE_URL', 'http://localhost:5005'),
    ],

    'piper' => [
        // Serveur TTS français auto-hébergé et gratuit (voir docker-compose.yml,
        // service "piper-tts" — Groq n'a pas de voix française actuellement).
        'url' => env('PIPER_TTS_URL', 'http://localhost:5006'),
    ],

    'translation' => [
        // "libretranslate" (gratuit, auto-hébergé) ou "google" (nécessite une
        // clé Cloud Translation API facturée par Google, cf. GOOGLE_TRANSLATE_API_KEY)
        'driver' => env('TRANSLATION_DRIVER', 'libretranslate'),
    ],

    // URL du frontend Next.js, utilisée pour rediriger après le callback OAuth
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
];
