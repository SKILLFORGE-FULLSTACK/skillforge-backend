<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Le frontend Next.js (SPA) appelle l'API depuis une autre origine
    | (ex: http://localhost:3000 -> http://localhost:8000/api). L'authentification
    | se fait par token Bearer (Sanctum "personal access token"), pas par cookie,
    | donc `supports_credentials` reste à false.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('FRONTEND_URL', 'http://localhost:3000'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
