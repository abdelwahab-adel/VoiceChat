<?php

declare(strict_types=1);

namespace App\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

/**
 * Logger service.
 * 
 * Thin wrapper around Monolog that exposes a simple level-based API.
 */
final class LoggerService
{
    private Logger $logger;

    public function __construct(string $logPath, string $level = 'debug')
    {
        $this->logger = new Logger('app');
        if (!is_dir($logPath)) @mkdir($logPath, 0755, true);

        $handler = new RotatingFileHandler(
            rtrim($logPath, '/') . '/app.log',
            30,
            $this->resolveLevel($level)
        );
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
        $handler->setFormatter($formatter);
        $this->logger->pushHandler($handler);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function logger(): Logger { return $this->logger; }

    private function resolveLevel(string $name): Level
    {
        return match (strtolower($name)) {
            'debug'     => Level::Debug,
            'info'      => Level::Info,
            'notice'    => Level::Notice,
            'warning'   => Level::Warning,
            'error'     => Level::Error,
            'critical'  => Level::Critical,
            'alert'     => Level::Alert,
            'emergency' => Level::Emergency,
            default     => Level::Debug,
        };
    }
}
