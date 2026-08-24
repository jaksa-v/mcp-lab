<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Inspector runs in the browser on :6274 and calls this app on :8000.
    | Default Laravel CORS only matches api/*, so OAuth discovery returns 200
    | with no Access-Control-Allow-Origin and the browser throws the response
    | away. Then Inspector guesses /token and openid-configuration, which 404.
    |
    */

    'paths' => [
        'mcp/*',
        'oauth/*',
        '.well-known/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [
        '#\Ahttps?://(localhost|127\.0\.0\.1)(:\d+)?\z#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'WWW-Authenticate',
    ],

    'max_age' => 0,

    'supports_credentials' => false,

];
