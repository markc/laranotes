<?php

return [
    'secret' => env('COLLAB_SECRET'),
    'kid' => env('COLLAB_TOKEN_KID', 'v1'),
    'hmac' => env('COLLAB_TOKEN_HMAC'),
    'ws_url' => env('COLLAB_WS_URL', 'ws://localhost:4444'),
    'http_url' => env('COLLAB_HTTP_URL', 'http://localhost:4444'),
    'ttl' => (int) env('COLLAB_TOKEN_TTL', 300),
];
