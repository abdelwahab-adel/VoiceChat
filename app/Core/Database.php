<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use InvalidArgumentException;
use RuntimeException;

/**
 * PDO Database wrapper.
 * 
 * Provides a thin, secure abstraction over PDO with helpers
 * for query building, transactions, and result hydration.
 */
final class Database
{
    private PDO $pdo;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect(): void
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['driver'] ?? 'mysql',
            $this->config['host'] ?? '127.0.0.1',
            (int) ($this->config['port'] ?? 3306),
            $this->config['database'] ?? 'voicechat',
            $this->config['charset'] ?? 'utf8mb4'
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . ($this->config['charset'] ?? 'utf8mb4'),
        ];

        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'] ?? 'root',
                $this->config['password'] ?? '',
                $options
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 500, $e);
        }
    }

    public function pdo(): PDO { return $this->pdo; }

    public function query(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->sanitize($bindings));
        return $stmt;
    }

    public function fetchAll(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings)->fetchAll();
    }

    public function fetchOne(string $sql, array $bindings = []): ?array
    {
        $row = $this->query($sql, $bindings)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchValue(string $sql, array $bindings = []): mixed
    {
        $stmt = $this->query($sql, $bindings);
        $value = $stmt->fetchColumn();
        return $value === false ? null : $value;
    }

    public function insert(string $table, array $data): string
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Insert data cannot be empty');
        }
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdent($table),
            implode(',', array_map([$this, 'quoteIdent'], $columns)),
            implode(',', $placeholders)
        );
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereBindings = []): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Update data cannot be empty');
        }
        $set = [];
        foreach (array_keys($data) as $col) {
            $set[] = $this->quoteIdent($col) . ' = :set_' . $col;
        }
        $bindings = [];
        foreach ($data as $k => $v) $bindings['set_' . $k] = $v;
        $bindings = array_merge($bindings, $whereBindings);

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->quoteIdent($table),
            implode(', ', $set),
            $where
        );
        return $this->query($sql, $bindings)->rowCount();
    }

    public function delete(string $table, string $where, array $bindings = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->quoteIdent($table), $where);
        return $this->query($sql, $bindings)->rowCount();
    }

    public function count(string $table, string $where = '1=1', array $bindings = []): int
    {
        $sql = sprintf('SELECT COUNT(*) FROM %s WHERE %s', $this->quoteIdent($table), $where);
        return (int) $this->fetchValue($sql, $bindings);
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function lastInsertId(): string { return $this->pdo->lastInsertId(); }

    public function quoteIdent(string $identifier): string
    {
        if (str_contains($identifier, '.') || str_contains($identifier, ' ')) {
            return implode('.', array_map(
                fn($part) => '`' . str_replace('`', '``', $part) . '`',
                explode('.', $identifier)
            ));
        }
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function sanitize(array $bindings): array
    {
        $out = [];
        foreach ($bindings as $k => $v) {
            if (is_bool($v))      $v = (int) $v;
            elseif (is_null($v))  $v = null;
            elseif (is_array($v) || is_object($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            $out[$k] = $v;
        }
        return $out;
    }
}
