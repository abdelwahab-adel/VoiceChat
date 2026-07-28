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
use ReflectionFunction;
use ReflectionFunctionAbstract;
use Throwable;

/**
 * HTTP Router - Pure PHP Implementation
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
        'GET'     => [],
        'POST'    => [],
        'PUT'     => [],
        'PATCH'   => [],
        'DELETE'  => [],
        'OPTIONS' => [],
    ];
    private array $groupStack = [];
    private array $named = [];
    private ?array $current = null;
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, array|Closure $handler, array $opts = []): void
    {
        $this->add('GET', $path, $handler, $opts);
    }

    public function post(string $path, array|Closure $handler, array $opts = []): void
    {
        $this->add('POST', $path, $handler, $opts);
    }

    public function put(string $path, array|Closure $handler, array $opts = []): void
    {
        $this->add('PUT', $path, $handler, $opts);
    }

    public function patch(string $path, array|Closure $handler, array $opts = []): void
    {
        $this->add('PATCH', $path, $handler, $opts);
    }

    public function delete(string $path, array|Closure $handler, array $opts = []): void
    {
        $this->add('DELETE', $path, $handler, $opts);
    }

    public function options(string $path, array|Closure $handler, array $opts = []): void
    {
        $this->add('OPTIONS', $path, $handler, $opts);
    }

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

        if (isset($opts['middleware'])) {
            $middleware = array_merge($middleware, $opts['middleware']);
        }
        if (isset($opts['namespace'])) {
            $namespace .= $opts['namespace'];
        }
        if (isset($opts['name'])) {
            $name = $opts['name'];
        }

        $fullPath = '/' . trim($prefix . $path, '/');
        $fullPath = $fullPath === '/' ? '/' : rtrim($fullPath, '/');

        $route = [
            'verb'       => $verb,
            'path'       => $fullPath,
            'pattern'    => $this->compilePattern($fullPath),
            'handler'    => $handler,
            'middleware' => $middleware,
            'namespace'  => $namespace,
            'name'       => $name,
            'params'     => $this->extractParamNames($fullPath),
        ];

        $this->routes[$verb][] = $route;
        if ($name) {
            $this->named[$name] = $route;
        }
    }

    private function compilePattern(string $path): string
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?<$1>[^/]+)', $path);
        if ($regex === null) {
            throw new \RuntimeException('Failed to compile route pattern');
        }
        return '#^' . $regex . '$#';
    }

    private function extractParamNames(string $path): array
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch(): void
    {
        $verb  = $this->request->method();
        $path  = $this->request->path();
        $route = $this->matchRoute($verb, $path);

        if ($route === null) {
            $this->response->notFound('Route not found: ' . $path);
            return;
        }

        $this->current = $route;

        try {
            $this->runMiddleware($route['middleware']);
            $this->invoke($route);
        } catch (Throwable $e) {
            $this->handleRouteException($e);
        }
    }

    private function matchRoute(string $verb, string $path): ?array
    {
        $candidates = $this->routes[$verb] ?? [];

        foreach ($candidates as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                $params = [];
                foreach ($route['params'] as $p) {
                    if (isset($matches[$p])) {
                        $params[$p] = $matches[$p];
                    }
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
            $reflection = new ReflectionFunction($handler);
            $args = $this->resolveArguments($reflection, $params);
            $handler(...$args);
            return;
        }

        if (!is_array($handler) || count($handler) !== 2) {
            throw new \InvalidArgumentException('Invalid handler format');
        }

        [$class, $method] = $handler;
        $controllerClass = $ns . '\\' . $class;
        $controller = $this->instantiateController($controllerClass);

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method {$method} not found in {$controllerClass}");
        }

        $reflection = new ReflectionMethod($controller, $method);
        $args = $this->resolveArguments($reflection, $params);
        $controller->{$method}(...$args);
    }

    private function instantiateController(string $class): object
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Controller class not found: {$class}");
        }

        $app = Application::getInstance();
        $reflector = new ReflectionClass($class);
        $ctor = $reflector->getConstructor();
        $args = $ctor ? $this->resolveArguments($ctor) : [];

        return new $class(...$args);
    }

    private function resolveArguments(ReflectionFunctionAbstract $fn, array $params = []): array
    {
        $app = Application::getInstance();
        $args = [];

        foreach ($fn->getParameters() as $param) {
            $type = $param->getType();
            $name = $param->getName();

            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();
                $resolved = $this->resolveService($typeName);
                if ($resolved !== null) {
                    $args[] = $resolved;
                    continue;
                }
            }

            if (isset($params[$name])) {
                $args[] = $params[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        return $args;
    }

    private function resolveService(string $typeName): mixed
    {
        $app = Application::getInstance();

        return match ($typeName) {
            Request::class  => $app->getRequest(),
            Response::class => $app->getResponse(),
            Database::class => $app->getDb(),
            default         => $this->instantiateService($typeName),
        };
    }

    private function instantiateService(string $class): object
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Service class not found: {$class}");
        }

        $reflector = new ReflectionClass($class);
        $ctor = $reflector->getConstructor();
        $args = $ctor ? $this->resolveArguments($ctor) : [];

        return new $class(...$args);
    }

    private function handleRouteException(Throwable $e): void
    {
        $debug = (bool)($_ENV['APP_DEBUG'] ?? false);
        $code = (int)$e->getCode();

        if ($code < 100 || $code > 599) {
            $code = 500;
        }

        $this->response->json([
            'error'   => $e->getMessage() ?: 'Server Error',
            'file'    => $debug ? $e->getFile() : null,
            'line'    => $debug ? $e->getLine() : null,
            'trace'   => $debug ? explode("\n", $e->getTraceAsString()) : null,
        ], $code);
    }

    public function current(): ?array
    {
        return $this->current;
    }

    public function routes(): array
    {
        return $this->routes;
    }
}
