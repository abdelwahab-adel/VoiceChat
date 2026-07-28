<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected string $table = 'settings';
    protected array $fillable = ['key_name','value','type','group_name','description','is_public'];
    protected array $casts = ['is_public' => 'bool'];

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->findBy('key_name', $key);
        if (!$row) return $default;
        return match ($row['type']) {
            'int', 'integer' => (int) $row['value'],
            'bool', 'boolean'=> filter_var($row['value'], FILTER_VALIDATE_BOOLEAN),
            'json'           => json_decode((string) $row['value'], true),
            default          => $row['value'],
        };
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): bool
    {
        $row = $this->findBy('key_name', $key);
        $val = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
        if ($row) {
            return $this->update($row['id'], [
                'value'       => $val,
                'type'        => $type,
                'group_name'  => $group,
                'description' => $description,
            ]) > 0;
        }
        $id = $this->create([
            'key_name'    => $key,
            'value'       => $val,
            'type'        => $type,
            'group_name'  => $group,
            'description' => $description,
        ]);
        return (bool) $id;
    }

    public function publicSettings(): array
    {
        return $this->db->fetchAll('SELECT key_name, value, type FROM settings WHERE is_public = 1');
    }
}
