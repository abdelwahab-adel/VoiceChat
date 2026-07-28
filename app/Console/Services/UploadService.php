<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use RuntimeException;
use InvalidArgumentException;

/**
 * File Upload service.
 * 
 * Handles:
 *  - Validation (size, type, MIME)
 *  - Image processing (resize, optimize)
 *  - Disk storage
 *  - Database persistence
 */
final class UploadService
{
    public function __construct(
        private readonly Database $db,
        private readonly array $config
    ) {}

    /**
     * Upload a file from $_FILES.
     */
    public function upload(array $file, string $subdir, array $options = []): array
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('No file uploaded');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload error code: ' . $file['error']);
        }
        if ($file['size'] > (int) ($this->config['max_size'] ?? 10485760)) {
            throw new InvalidArgumentException('File too large');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $category = $this->categorize($ext);
        $allowed = $this->config[$category] ?? [];
        if (!in_array($ext, $allowed, true)) {
            throw new InvalidArgumentException("File type .$ext not allowed");
        }

        $relativeDir = trim($this->config['path'] ?? 'public/uploads', '/') . '/' . trim($subdir, '/');
        $absoluteDir = dirname($this->config['path'] && str_starts_with($this->config['path'], 'public')
            ? \App\Core\Application::getInstance()->rootPath() . '/' . $this->config['path']
            : $this->config['path']) . '/' . trim($subdir, '/');

        if (!is_dir($absoluteDir)) @mkdir($absoluteDir, 0755, true);

        $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        $absolute = $absoluteDir . '/' . $name;
        $relative = $relativeDir . '/' . $name;

        if (!@move_uploaded_file($file['tmp_name'], $absolute)) {
            throw new RuntimeException('Failed to save uploaded file');
        }
        @chmod($absolute, 0644);

        // Image post-processing
        if ($category === 'images' && class_exists(ImageManager::class)) {
            $this->processImage($absolute, $ext, $options);
        }

        return [
            'path'     => $relative,
            'url'      => url('public/' . $relative),
            'filename' => $name,
            'size'     => filesize($absolute),
            'mime'     => mime_content_type($absolute) ?: $file['type'],
        ];
    }

    /**
     * Persist an upload record linked to a user.
     */
    public function uploadForUser(int $userId, array $file, string $subdir, string $purpose = 'avatar', array $options = []): array
    {
        $info = $this->upload($file, $subdir, $options);

        $this->db->insert('activity_logs', [
            'user_id'    => $userId,
            'action'     => 'upload',
            'subject_type' => 'file',
            'metadata'   => json_encode(['path' => $info['path'], 'purpose' => $purpose]),
        ]);

        return $info;
    }

    /**
     * Delete a stored file.
     */
    public function delete(string $relativePath): bool
    {
        $root = \App\Core\Application::getInstance()->rootPath();
        $absolute = $root . '/' . ltrim($relativePath, '/');
        if (is_file($absolute) && str_starts_with($absolute, $root . '/public/')) {
            return @unlink($absolute);
        }
        return false;
    }

    private function categorize(string $ext): string
    {
        $imageExts = ['jpg','jpeg','png','gif','webp'];
        $audioExts = ['mp3','wav','ogg','m4a'];
        if (in_array($ext, $imageExts, true)) return 'images';
        if (in_array($ext, $audioExts, true)) return 'audio';
        return 'images';
    }

    private function processImage(string $absolute, string $ext, array $options): void
    {
        try {
            $manager = new ImageManager(new GdDriver());
            $img = $manager->read($absolute);
            $maxW = (int) ($options['max_width']  ?? 1920);
            $maxH = (int) ($options['max_height'] ?? 1920);
            $quality = (int) ($options['quality'] ?? 85);
            $img->scaleDown($maxW, $maxH);
            $img->save($absolute, $quality);
        } catch (\Throwable $e) {
            // Silently fall back to original.
        }
    }
}
