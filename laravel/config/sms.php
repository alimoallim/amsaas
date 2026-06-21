<?php

return [
    'enabled' => (bool) env('SMS_ENABLED', false),

    'driver' => env('SMS_DRIVER', 'log'),

    'prefer_sms' => (bool) env('SMS_PREFER_OVER_EMAIL', false),

    'from' => env('SMS_FROM', 'AMSAAS'),

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'africastalking' => [
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'from' => env('AFRICASTALKING_FROM'),
    ],
];
