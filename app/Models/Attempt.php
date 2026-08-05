<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    protected $fillable = ['user_id','video_id','attempt_no','score','passed','answers'];
    protected $casts = ['answers' => 'array', 'passed' => 'boolean'];
}