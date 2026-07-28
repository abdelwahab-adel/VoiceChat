<?php

declare(strict_types=1);

return [
    'secret'       => $_ENV['JWT_SECRET'] ?? 'change-me',
    'algo'         => $_ENV['JWT_ALGO'] ?? 'HS256',
    'access_ttl'   => (int) ($_ENV['JWT_ACCESS_TTL'] ?? 3600),
    'refresh_ttl'  => (int) ($_ENV['JWT_REFRESH_TTL'] ?? 2592000),
    'issuer'       => $_ENV['JWT_ISSUER'] ?? 'voicechat',
    'audience'     => $_ENV['JWT_AUDIENCE'] ?? 'voicechat-app',
];
