<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Application;
use RuntimeException;

/**
 * Resolves middleware classes from short names.
 */
final class MiddlewareStack
{
    public static function resolve(array $names): array
    {
        $instances = [];
        foreach ($names as $name) {
            $class = self::classFor($name);
            if (!class_exists($class)) {
                throw new RuntimeException("Middleware not found: $name");
            }
            $app = Application::getInstance();
            $reflector = new \ReflectionClass($class);
            $ctor = $reflector->getConstructor();
            $args = $ctor ? self::resolveArgs($ctor) : [];
            $instances[] = new $class(...$args);
        }
        return $instances;
    }

    public static function classFor(string $name): string
    {
        $map = [
            'auth'      => AuthMiddleware::class,
            'guest'     => GuestMiddleware::class,
            'admin'     => AdminMiddleware::class,
            'csrf'      => CsrfMiddleware::class,
            'api.auth'  => ApiAuthMiddleware::class,
            'rate'      => RateLimitMiddleware::class,
            'maintenance' => MaintenanceMiddleware::class,
        ];
        return $map[$name] ?? $name;
    }

    private static function resolveArgs(\ReflectionMethod $ctor): array
    {
        $app = Application::getInstance();
        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();
                $args[] = match ($typeName) {
                    Request::class, Response::class, Database::class => match ($typeName) {
                        Request::class  => $app->getRequest(),
                        Response::class => $app->getResponse(),
                        Database::class => $app->getDb(),
                    },
                    default => $app->getService($typeName),
                };
            } else {
                $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            }
        }
        return $args;
    }
}
