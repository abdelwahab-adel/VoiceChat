<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;
use App\Services\LoggerService;
use App\Services\CacheService;
use App\Services\SessionService;
use Throwable;

/**
 * Application Bootstrap - Pure PHP Implementation
 * 
 * The heart of the framework. Bootstraps all services,
 * loads configuration, registers globals, and dispatches the request.
 */
final class Application
{
    private static ?Application $instance = null;
    private Router $router;
    private Database $db;
    private Request $request;
    private Response $response;
    private array $config = [];
    private array $services = [];
    private string $rootPath;

    /**
     * Singleton accessor for the application instance.
     */
    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application not bootstrapped.');
        }
        return self::$instance;
    }

    /**
     * Boot the application.
     */
    public static function bootstrap(string $rootPath): Application
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $app = new self($rootPath);
        self::$instance = $app;
        return $app;
    }

    private function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
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
            try {
                Dotenv::createImmutable($this->rootPath)->safeLoad();
            } catch (Throwable $e) {
                error_log('Failed to load .env: ' . $e->getMessage());
            }
        }
    }

    /**
     * Load configuration files.
     */
    private function loadConfig(): void
    {
        $configPath = $this->rootPath . '/config';
        if (!is_dir($configPath)) {
            throw new \RuntimeException("Config directory not found: {$configPath}");
        }

        $files = glob($configPath . '/*.php');
        if ($files === false) {
            throw new \RuntimeException("Failed to load config files from {$configPath}");
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $config = require $file;
            if (is_array($config)) {
                $this->config[$key] = $config;
            }
        }
    }

    /**
     * Set up error, exception, and shutdown handlers.
     */
    private function setErrorHandling(): void
    {
        $debug = (bool)($_ENV['APP_DEBUG'] ?? false);

        set_exception_handler(function (Throwable $e) use ($debug): void {
            $this->handleException($e, $debug);
        });

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->handleShutdownError($error);
            }
        });
    }

    /**
     * Handle exceptions uniformly.
     */
    private function handleException(Throwable $e, bool $debug): void
    {
        $logService = $this->services['logger'] ?? null;
        if ($logService instanceof LoggerService) {
            $logService->error('Unhandled exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        if (!headers_sent()) {
            http_response_code(500);
            if ($debug) {
                echo '<pre style="text-align:left;background:#f5f5f5;padding:20px;border-radius:4px;">';
                echo htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8');
                echo '</pre>';
            } else {
                echo 'Internal Server Error';
            }
        }
    }

    /**
     * Handle fatal errors during shutdown.
     */
    private function handleShutdownError(array $error): void
    {
        $logService = $this->services['logger'] ?? null;
        if ($logService instanceof LoggerService) {
            $logService->error('Fatal error', $error);
        }

        if (!headers_sent()) {
            http_response_code(500);
            echo 'Fatal Server Error';
        }
    }

    /**
     * Initialize core services.
     */
    private function initServices(): void
    {
        // Logger
        $this->services['logger'] = new LoggerService(
            $this->rootPath . '/storage/logs',
            (string)($_ENV['LOG_LEVEL'] ?? 'debug')
        );

        // Cache
        $this->services['cache'] = new CacheService(
            $this->rootPath . '/storage/cache',
            (int)($_ENV['CACHE_TTL'] ?? 3600)
        );

        // Session
        $this->services['session'] = new SessionService([
            'name'        => (string)($_ENV['SESSION_NAME'] ?? 'VC_SESSION'),
            'lifetime'    => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
            'secure'      => (bool)($_ENV['SESSION_SECURE_COOKIE'] ?? false),
            'http_only'   => (bool)($_ENV['SESSION_HTTP_ONLY'] ?? true),
            'same_site'   => (string)($_ENV['SESSION_SAME_SITE'] ?? 'Lax'),
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
            $logService = $this->services['logger'] ?? null;
            if ($logService instanceof LoggerService) {
                $logService->error('Dispatch error', ['error' => $e->getMessage()]);
            }
            $this->response->setStatusCode(500)->json([
                'error'   => 'Server Error',
                'message' => (bool)($_ENV['APP_DEBUG'] ?? false) ? $e->getMessage() : 'Internal error',
            ]);
        }
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getDb(): Database
    {
        return $this->db;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

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

    public function getRootPath(): string
    {
        return $this->rootPath;
    }
}
