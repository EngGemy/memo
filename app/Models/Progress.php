<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Progress extends Model
{
    protected $table = 'progress';
    protected $fillable = [
        'user_id','video_id','watched_seconds','furthest_second',
        'unlocked','completed','completed_at',
    ];
    protected $casts = [
        'unlocked' => 'boolean', 'completed' => 'boolean', 'completed_at' => 'datetime',
    ];
}