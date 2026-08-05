<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    public $timestamps = false;
    protected $fillable = ['question_id','body','body_ar','is_correct','position'];
    protected $casts = ['is_correct' => 'boolean'];
    protected $hidden = ['is_correct'];

    public function question(): BelongsTo { return $this->belongsTo(Question::class); }
}