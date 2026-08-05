<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = ['video_id','body','body_ar','ask_at','position'];

    public function video(): BelongsTo { return $this->belongsTo(Video::class); }
    public function options(): HasMany { return $this->hasMany(QuestionOption::class)->orderBy('position'); }

    public function forLearner(string $locale = 'en'): array
    {
        return [
            'id'      => $this->id,
            'body'    => $locale === 'ar' && $this->body_ar ? $this->body_ar : $this->body,
            'ask_at'  => $this->ask_at,
            'options' => $this->options->map(fn ($o) => [
                'id'   => $o->id,
                'body' => $locale === 'ar' && $o->body_ar ? $o->body_ar : $o->body,
            ])->values(),
        ];
    }
}