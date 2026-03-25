<?php

return [

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

    // ── Termii SMS/OTP ──
    'termii' => [
        'api_key'   => env('TERMII_API_KEY'),
        'sender_id' => env('TERMII_SENDER_ID', 'IntecWorks'),
        'base_url'  => env('TERMII_BASE_URL', 'https://v3.api.termii.com'),
        'channel'   => env('TERMII_CHANNEL', 'generic'),
    ],

    // ── Cloudinary (client-side upload) ──
    'cloudinary' => [
        'cloud_name'    => env('CLOUDINARY_CLOUD_NAME'),
        'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    ],

    // ── Paystack ──
    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    ],

    // ── Korapay ──
    'korapay' => [
        'secret_key'     => env('KORAPAY_SECRET_KEY'),
        'public_key'     => env('KORAPAY_PUBLIC_KEY'),
        'encryption_key' => env('KORAPAY_ENCRYPTION_KEY'),
    ],

];
