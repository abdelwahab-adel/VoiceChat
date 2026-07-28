<?php

declare(strict_types=1);

return [
    'host'  => $_ENV['WS_HOST'] ?? '0.0.0.0',
    'port'  => (int) ($_ENV['WS_PORT'] ?? 8080),
    'path'  => $_ENV['WS_PATH'] ?? '/ws',
    'ping_interval' => 30,
];
