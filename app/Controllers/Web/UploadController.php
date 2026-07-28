<?php

declare(strict_types=1);

namespace App\Controllers\Web;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Services\UploadService;

class UploadController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db,
        private readonly UploadService $upload
    ) {
        parent::__construct($request, $response, $db);
    }

    public function image(): void
    {
        $file = $this->request->file('file');
        if (!$file) {
            $this->json(['error' => 'No file uploaded'], 400);
            return;
        }
        try {
            $info = $this->upload->uploadForUser((int) $this->user()['id'], $file, 'messages', 'message_image');
            $this->success($info, 'Uploaded');
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    public function audio(): void
    {
        $file = $this->request->file('file');
        if (!$file) {
            $this->json(['error' => 'No file uploaded'], 400);
            return;
        }
        try {
            $info = $this->upload->uploadForUser((int) $this->user()['id'], $file, 'messages', 'message_audio');
            $this->success($info, 'Uploaded');
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }
    }
}
