<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use PDO;

/**
 * Base Model.
 * 
 * Provides a lightweight active-record-style abstraction
 * with helpers for common CRUD patterns.
 */
abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $timestamps = true;
    protected string $createdAtColumn = 'created_at';
    protected string $updatedAtColumn = 'updated_at';

    public function __construct(protected Database $db) {}

    public function all(array $orderBy = []): array
    {
        $sql = "SELECT * FROM {$this->db->quoteIdent($this->table)}";
        if ($orderBy) {
            $parts = [];
            foreach ($orderBy as $col => $dir) $parts[] = $this->db->quoteIdent($col) . ' ' . ($dir === 'desc' ? 'DESC' : 'ASC');
            $sql .= ' ORDER BY ' . implode(', ', $parts);
        }
        return $this->db->fetchAll($sql);
    }

    public function find(int|string $id): ?array
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM {$this->db->quoteIdent($this->table)} WHERE {$this->db->quoteIdent($this->primaryKey)} = :id LIMIT 1",
            ['id' => $id]
        );
        return $row ? $this->hydrate($row) : null;
    }

    public function findBy(string $column, mixed $value): ?array
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM {$this->db->quoteIdent($this->table)} WHERE {$this->db->quoteIdent($column)} = :v LIMIT 1",
            ['v' => $value]
        );
        return $row ? $this->hydrate($row) : null;
    }

    public function where(array $conditions, string $extra = '', array $bindings = []): array
    {
        $where = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            $where[] = $this->db->quoteIdent($col) . ' = :' . $col;
            $params[$col] = $val;
        }
        $sql = "SELECT * FROM {$this->db->quoteIdent($this->table)} WHERE " . implode(' AND ', $where) . ' ' . $extra;
        $rows = $this->db->fetchAll($sql, array_merge($params, $bindings));
        return array_map(fn($r) => $this->hydrate($r), $rows);
    }

    public function firstWhere(array $conditions): ?array
    {
        $rows = $this->where($conditions, 'LIMIT 1');
        return $rows[0] ?? null;
    }

    public function create(array $data): string
    {
        $data = $this->filter($data);
        if ($this->timestamps) {
            $data[$this->createdAtColumn] = date('Y-m-d H:i:s');
            $data[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        }
        return $this->db->insert($this->table, $data);
    }

    public function update(int|string $id, array $data): int
    {
        $data = $this->filter($data);
        if ($this->timestamps) {
            $data[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        }
        return $this->db->update(
            $this->table,
            $data,
            "{$this->db->quoteIdent($this->primaryKey)} = :_id",
            ['_id' => $id]
        );
    }

    public function delete(int|string $id): int
    {
        return $this->db->delete(
            $this->table,
            "{$this->db->quoteIdent($this->primaryKey)} = :id",
            ['id' => $id]
        );
    }

    public function count(string $where = '1=1', array $bindings = []): int
    {
        return $this->db->count($this->table, $where, $bindings);
    }

    public function paginate(int $page = 1, int $perPage = 20, string $where = '1=1', array $bindings = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $total = $this->db->count($this->table, $where, $bindings);
        $rows = $this->db->fetchAll(
            "SELECT * FROM {$this->db->quoteIdent($this->table)} WHERE {$where} LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );
        return [
            'data' => array_map(fn($r) => $this->hydrate($r), $rows),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function query(): Database
    {
        return $this->db;
    }

    protected function filter(array $data): array
    {
        if (empty($this->fillable)) return $data;
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function hydrate(array $row): array
    {
        foreach ($this->casts as $col => $type) {
            if (!array_key_exists($col, $row)) continue;
            $row[$col] = match ($type) {
                'int', 'integer' => (int) $row[$col],
                'float', 'double' => (float) $row[$col],
                'bool', 'boolean' => (bool) $row[$col],
                'array', 'json'   => is_string($row[$col]) ? (json_decode($row[$col], true) ?? []) : (array) $row[$col],
                'datetime'        => $row[$col] ? date('Y-m-d H:i:s', strtotime((string) $row[$col])) : null,
                default           => $row[$col],
            };
        }
        foreach ($this->hidden as $h) unset($row[$h]);
        return $row;
    }
}
