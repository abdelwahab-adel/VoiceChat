<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Notification extends Model
{
    protected string $table = 'notifications';
    protected array $fillable = [
        'user_id','type','title','body','data','icon','image','action_url','is_read','read_at',
    ];
    protected array $casts = [
        'data' => 'json',
        'is_read' => 'bool',
    ];
}
