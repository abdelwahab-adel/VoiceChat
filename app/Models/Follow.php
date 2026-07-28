<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Follow extends Model
{
    protected string $table = 'follows';
    protected array $fillable = ['follower_id','following_id','is_close_friend','is_notifications'];
    protected array $casts = [
        'is_close_friend' => 'bool',
        'is_notifications' => 'bool',
    ];
}
