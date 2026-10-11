<?php

return [
    'url' => env('LIVEKIT_URL', 'wss://demo.livekit.cloud'),
    'api_key' => env('LIVEKIT_API_KEY', 'devkey'),
    'api_secret' => env('LIVEKIT_API_SECRET', 'secret'),
    'call_ring_timeout' => (int) env('CALL_RING_TIMEOUT', 30),
];
