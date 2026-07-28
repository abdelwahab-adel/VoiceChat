<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\MiddlewareStack;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\ApiAuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;
use Closure;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * HTTP Router.
 * 
 * Supports:
 *  - HTTP verb routing (GET, POST, PUT, PATCH, DELETE)
 *  - URL parameters (e.g. /users/{id})
 *  - Route groups with prefixes and middleware
 *  - Controller@method dispatch with auto DI
 *  - Closure handlers
 */
final class Router
{
    private array $routes = [
        'GET' => [], 'POST' => [], 'PUT' => [],
        'PATCH' => [], 'DELETE' => [], 'OPTIONS' => [],
    ];
    private array $groupStack = [];
    private array $named = [];
    private ?array $current = null;

    public function __construct(
        private readonly Request $request,
        private readonly Response $response
    ) {}

    public function get(string $path, array|Closure $handler, array $opts = []): void
    { $this->add('GET', $path, $handler, $opts); }

    public function post(string $path, array|Closure $handler, array $opts = []): void
    { $this->add('POST', $path, $handler, $opts); }

    public function put(string $path, array|Closure $handler, array $opts = []): void
    { $this->add('PUT', $path, $handler, $opts); }

    public function patch(string $path, array|Closure $handler, array $opts = []): void
    { $this->add('PATCH', $path, $handler, $opts); }

    public function delete(string $path, array|Closure $handler, array $opts = []): void
    { $this->add('DELETE', $path, $handler, $opts); }

    public function options(string $path, array|Closure $handler, array $opts = []): void
    { $this->add('OPTIONS', $path, $handler, $opts); }

    /**
     * Group routes with shared prefix / middleware.
     */
    public function group(array $attrs, Closure $callback): void
    {
        $this->groupStack[] = $attrs;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function add(string $verb, string $path, array|Closure $handler, array $opts): void
    {
        $prefix = '';
        $middleware = [];
        $namespace = '';
        $name = null;
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'] ?? '';
            $middleware = array_merge($middleware, $group['middleware'] ?? []);
            $namespace .= $group['namespace'] ?? '';
        }
        if (isset($opts['middleware'])) $middleware = array_merge($middleware, $opts['middleware']);
        if (isset($opts['namespace'])) $namespace .= $opts['namespace'];
        if (isset($opts['name'])) $name = $opts['name'];

        $fullPath = '/' . trim($prefix . $path, '/');
        $fullPath = $fullPath === '/' ? '/' : rtrim($fullPath, '/');

        $route = [
            'verb'       => $verb,
            'path'       => $fullPath,
            'pattern'    => $this->compile($fullPath),
            'handler'    => $handler,
            'middleware' => $middleware,
            'namespace'  => $namespace,
            'name'       => $name,
            'params'     => $this->paramNames($fullPath),
        ];
        $this->routes[$verb][] = $route;
        if ($name) $this->named[$name] = $route;
    }

    private function compile(string $path): string
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    private function paramNames(string $path): array
    {
        preg_match_all('/\{(\w+)\}/', $path, $m);
        return $m[1];
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch(): void
    {
        $verb  = $this->request->method();
        $path  = $this->request->path();
        $route = $this->match($verb, $path);

        if ($route === null) {
            $this->response->notFound('Route not found: ' . $path);
            return;
        }

        $this->current = $route;

        try {
            $this->runMiddleware($route['middleware']);
            $this->invoke($route);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    private function match(string $verb, string $path): ?array
    {
        $candidates = $this->routes[$verb] ?? [];
        foreach ($candidates as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                $params = [];
                foreach ($route['params'] as $p) {
                    if (isset($matches[$p])) $params[$p] = $matches[$p];
                }
                $route['resolved_params'] = $params;
                return $route;
            }
        }
        return null;
    }

    private function runMiddleware(array $middleware): void
    {
        $stack = MiddlewareStack::resolve($middleware);
        foreach ($stack as $mw) {
            $mw->handle($this->request, $this->response);
        }
    }

    private function invoke(array $route): void
    {
        $handler = $route['handler'];
        $params  = $route['resolved_params'] ?? [];
        $ns      = $route['namespace'] ?? '';

        if ($handler instanceof Closure) {
            $reflection = new \ReflectionFunction($handler);
            $args = $this->resolveArgs($reflection, $params);
            $handler(...$args);
            return;
        }

        [$class, $method] = $handler;
        $controllerClass = $ns . '\\' . $class;
        $controller = $this->makeController($controllerClass);

        $reflection = new ReflectionMethod($controller, $method);
        $args = $this->resolveArgs($reflection, $params);
        $controller->{$method}(...$args);
    }

    private function makeController(string $class): object
    {
        $app = Application::getInstance();
        $reflector = new ReflectionClass($class);
        $ctor = $reflector->getConstructor();
        $args = $ctor ? $this->resolveArgs($ctor) : [];
        return new $class(...$args);
    }

    private function resolveArgs(\ReflectionFunctionAbstract $fn, array $params = []): array
    {
        $app = Application::getInstance();
        $args = [];
        foreach ($fn->getParameters() as $param) {
            $type = $param->getType();
            $name = $param->getName();
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();
                $args[] = match ($typeName) {
                    Request::class     => $app->getRequest(),
                    Response::class    => $app->getResponse(),
                    Database::class    => $app->getDb(),
                    default            => $this->makeService($typeName),
                };
            } elseif (isset($params[$name])) {
                $args[] = $params[$name];
            } else {
                $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            }
        }
        return $args;
    }

    private function makeService(string $class): object
    {
        $reflector = new ReflectionClass($class);
        $ctor = $reflector->getConstructor();
        $args = $ctor ? $this->resolveArgs($ctor) : [];
        return new $class(...$args);
    }

    private function handleException(Throwable $e): void
    {
        $debug = (bool) ($_ENV['APP_DEBUG'] ?? false);
        $code = (int) $e->getCode();
        if ($code < 100 || $code > 599) $code = 500;
        $this->response->json([
            'error'   => $e->getMessage() ?: 'Server Error',
            'file'    => $debug ? $e->getFile() : null,
            'line'    => $debug ? $e->getLine() : null,
            'trace'   => $debug ? explode("\n", $e->getTraceAsString()) : null,
        ], $code);
    }

    public function current(): ?array { return $this->current; }
    public function routes(): array { return $this->routes; }
}
