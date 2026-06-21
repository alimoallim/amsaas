<?php

return [
    'enabled' => (bool) env('EVC_WEBHOOK_ENABLED', false),

    'webhook_secret' => env('EVC_WEBHOOK_SECRET', ''),

    'signature_header' => env('EVC_SIGNATURE_HEADER', 'X-EVC-Signature'),
];
