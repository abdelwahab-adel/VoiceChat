<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Middleware contract.
 */
interface MiddlewareInterface
{
    public function handle(Request $request, Response $response): void;
}
