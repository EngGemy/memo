<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeakReport extends Model
{
    protected $fillable = ['video_id','url','platform','impersonator','notes','status','spotted_at'];
    protected $casts = ['spotted_at' => 'datetime'];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}