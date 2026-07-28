<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;
use App\Services\LoggerService;
use App\Services\CacheService;
use App\Services\SessionService;
use Throwable;

/**
 * Application Bootstrap
 * 
 * The heart of the framework. Bootstraps all services,
 * loads configuration, registers globals, and dispatches the request.
 */
final class Application
{
    private static ?self $instance = null;
    private Router $router;
    private Database $db;
    private Request $request;
    private Response $response;
    private array $config = [];
    private array $services = [];

    /**
     * Singleton accessor for the application instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application not bootstrapped.');
        }
        return self::$instance;
    }

    /**
     * Boot the application.
     */
    public static function bootstrap(string $rootPath): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $app = new self($rootPath);
        self::$instance = $app;
        return $app;
    }

    private function __construct(private readonly string $rootPath)
    {
        $this->loadEnvironment();
        $this->loadConfig();
        $this->setErrorHandling();
        $this->initServices();
        $this->request = Request::capture();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
    }

    /**
     * Load .env file into environment.
     */
    private function loadEnvironment(): void
    {
        $envFile = $this->rootPath . '/.env';
        if (file_exists($envFile)) {
            Dotenv::createImmutable($this->rootPath)->safeLoad();
        }
    }

    /**
     * Load configuration files.
     */
    private function loadConfig(): void
    {
        $configPath = $this->rootPath . '/config';
        foreach (glob($configPath . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }
    }

    /**
     * Set up error, exception, and shutdown handlers.
     */
    private function setErrorHandling(): void
    {
        $debug = (bool) ($_ENV['APP_DEBUG'] ?? false);

        set_exception_handler(function (Throwable $e) use ($debug): void {
            $this->services['logger']->error('Unhandled exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            if (!headers_sent()) {
                http_response_code(500);
                if ($debug) {
                    echo '<pre style="text-align:left">'
                       . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8')
                       . '</pre>';
                } else {
                    echo 'Internal Server Error';
                }
            }
        });

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) return false;
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    /**
     * Initialize core services.
     */
    private function initServices(): void
    {
        // Logger
        $this->services['logger'] = new LoggerService(
            $this->rootPath . '/storage/logs',
            $_ENV['LOG_LEVEL'] ?? 'debug'
        );

        // Cache
        $this->services['cache'] = new CacheService(
            $this->rootPath . '/storage/cache',
            (int) ($_ENV['CACHE_TTL'] ?? 3600)
        );

        // Session
        $this->services['session'] = new SessionService([
            'name'        => $_ENV['SESSION_NAME'] ?? 'VC_SESSION',
            'lifetime'    => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200),
            'secure'      => (bool) ($_ENV['SESSION_SECURE_COOKIE'] ?? false),
            'http_only'   => (bool) ($_ENV['SESSION_HTTP_ONLY'] ?? true),
            'same_site'   => $_ENV['SESSION_SAME_SITE'] ?? 'Lax',
        ]);

        // Database
        $dbConfig = $this->config['database'] ?? [];
        $this->db = new Database($dbConfig);
    }

    /**
     * Run the application: route the request and send the response.
     */
    public function run(): void
    {
        try {
            $this->router->dispatch();
        } catch (Throwable $e) {
            $this->services['logger']->error('Dispatch error', ['error' => $e->getMessage()]);
            $this->response->setStatusCode(500)->json([
                'error'   => 'Server Error',
                'message' => (bool) ($_ENV['APP_DEBUG'] ?? false) ? $e->getMessage() : 'Internal error',
            ]);
        }
    }

    public function getRouter(): Router { return $this->router; }
    public function getDb(): Database { return $this->db; }
    public function getRequest(): Request { return $this->request; }
    public function getResponse(): Response { return $this->response; }
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
    public function getService(string $name): mixed
    {
        return $this->services[$name] ?? null;
    }

    public function setService(string $name, mixed $instance): void
    {
        $this->services[$name] = $instance;
    }
    public function rootPath(): string { return $this->rootPath; }
}
