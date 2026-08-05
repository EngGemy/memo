<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['slug','name','name_ar','position','is_active'];
    protected $casts = ['is_active' => 'boolean', 'position' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            $c->slug = $c->slug ?: (Str::slug($c->name) ?: Str::lower(Str::random(6)));
            $c->position = $c->position ?: (int) static::max('position') + 1;
        });
    }

    public function videos(): HasMany { return $this->hasMany(Video::class); }

    public function forApi(): array
    {
        return [
            'id'        => $this->id,
            'slug'      => $this->slug,
            'name'      => $this->name,
            'name_ar'   => $this->name_ar,
            'position'  => $this->position,
            'is_active' => $this->is_active,
            'videos'    => $this->videos_count ?? 0,
        ];
    }
}
