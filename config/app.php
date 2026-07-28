<?php

declare(strict_types=1);

return [
    'name'      => $_ENV['APP_NAME'] ?? 'VoiceChat',
    'env'       => $_ENV['APP_ENV'] ?? 'production',
    'debug'     => (bool) ($_ENV['APP_DEBUG'] ?? false),
    'url'       => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'timezone'  => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'locale'    => $_ENV['APP_LOCALE'] ?? 'en',
    'key'       => $_ENV['APP_KEY'] ?? '',
    'version'   => '1.0.0',
    'features'  => [
        'registration' => (bool) ($_ENV['FEATURE_REGISTRATION'] ?? true),
        'agencies'     => (bool) ($_ENV['FEATURE_AGENCIES'] ?? true),
        'gifts'        => (bool) ($_ENV['FEATURE_GIFTS'] ?? true),
        'voice_rooms'  => (bool) ($_ENV['FEATURE_VOICE_ROOMS'] ?? true),
        'private_chat' => (bool) ($_ENV['FEATURE_PRIVATE_CHAT'] ?? true),
    ],
    'pagination' => [
        'per_page'     => 20,
        'max_per_page' => 100,
    ],
];
