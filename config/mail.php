<?php

declare(strict_types=1);

return [
    'driver'    => $_ENV['MAIL_DRIVER'] ?? 'smtp',
    'host'      => $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io',
    'port'      => (int) ($_ENV['MAIL_PORT'] ?? 2525),
    'username'  => $_ENV['MAIL_USERNAME'] ?? '',
    'password'  => $_ENV['MAIL_PASSWORD'] ?? '',
    'encryption'=> $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
    'from'      => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@voicechat.local',
        'name'    => $_ENV['MAIL_FROM_NAME'] ?? 'VoiceChat',
    ],
];
