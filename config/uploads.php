<?php

declare(strict_types=1);

return [
    'max_size'   => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760),
    'images'     => explode(',', $_ENV['UPLOAD_ALLOWED_IMAGE'] ?? 'jpg,jpeg,png,gif,webp'),
    'audio'      => explode(',', $_ENV['UPLOAD_ALLOWED_AUDIO'] ?? 'mp3,wav,ogg,m4a'),
    'path'       => $_ENV['UPLOAD_PATH'] ?? 'public/uploads',
    'subdirs'    => [
        'avatars'  => 'avatars',
        'covers'   => 'covers',
        'rooms'    => 'rooms',
        'agencies' => 'agencies',
        'gifts'    => 'gifts',
        'messages' => 'messages',
    ],
];
