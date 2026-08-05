<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expert extends Model
{
    protected $fillable = ['name','name_ar','role','role_ar','bio','bio_ar','avatar_path','is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function videos(): HasMany { return $this->hasMany(Video::class); }
}