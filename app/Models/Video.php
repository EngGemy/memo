<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class Video extends Model
{
    protected $fillable = [
        'expert_id', 'category_id', 'position', 'title', 'title_ar',
        'description', 'description_ar', 'slug', 'master_disk', 'master_path', 'hls_path',
        'poster_path', 'poster_disk', 'key_iv', 'key_version', 'duration', 'size_bytes',
        'renditions', 'status', 'progress', 'error', 'is_public', 'published_at', 'views',
        'verify_code', 'content_sha256', 'first_published_at', 'watermark_burned',
    ];

    protected $casts = [
        'renditions' => 'array',
        'duration' => 'integer',
        'size_bytes' => 'integer',
        'position' => 'integer',
        'views' => 'integer',
        'is_public' => 'boolean',
        'watermark_burned' => 'boolean',
        'published_at' => 'datetime',
        'first_published_at' => 'datetime',
    ];

    protected $hidden = ['encryption_key', 'master_path', 'hls_path', 'master_disk', 'key_iv', 'content_sha256'];

    protected static function booted(): void
    {
        static::creating(function (self $v) {
            $v->verify_code = $v->verify_code ?: strtoupper(Str::random(4).'-'.Str::random(4));
        });
    }

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function setEncryptionKeyAttribute(?string $raw): void
    {
        $this->attributes['encryption_key'] = $raw ? Crypt::encryptString(base64_encode($raw)) : null;
    }

    public function getEncryptionKeyAttribute(?string $stored): ?string
    {
        return $stored ? base64_decode(Crypt::decryptString($stored)) : null;
    }

    public function isPlayable(): bool
    {
        return $this->status === 'published' && $this->hls_path !== null;
    }

    /**
     * A custom poster sits on the public disk and is linkable. The one ffmpeg
     * generates lives beside the HLS output on the private disk, so it is
     * served through a controller instead.
     */
    public function posterUrl(): ?string
    {
        if (! $this->poster_path) {
            return null;
        }

        return $this->poster_disk === 'public'
            ? asset('storage/'.$this->poster_path)
            : route('poster.show', ['video' => $this->id]);
    }

    public function forPublic(): array
    {
        // Prefer the relation — the legacy string column must never win here.
        $cat = $this->relationLoaded('category')
            ? $this->getRelation('category')
            : $this->category()->getResults();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'position' => $this->position,
            'category_id' => $this->category_id,
            'category' => $cat ? [
                'id' => $cat->id,
                'slug' => $cat->slug,
                'name' => $cat->name,
                'name_ar' => $cat->name_ar,
            ] : null,
            'title' => $this->title,
            'title_ar' => $this->title_ar,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'duration' => $this->duration,
            'views' => $this->views,
            'verify_code' => $this->verify_code,
            'poster' => $this->posterUrl(),
            'published_at' => optional($this->published_at)->toDateString(),
            'expert' => $this->expert ? $this->expert->only(['id', 'name', 'name_ar', 'role', 'role_ar']) : null,
        ];
    }

    public function forAdmin(): array
    {
        return $this->forPublic() + [
            'status' => $this->status,
            'progress' => $this->progress,
            'error' => $this->error,
            'is_public' => $this->is_public,
            'size_bytes' => $this->size_bytes,
            'renditions' => $this->renditions,
            'watermark_burned' => $this->watermark_burned,
            'has_custom_poster' => $this->poster_disk === 'public' && (bool) $this->poster_path,
            'first_published_at' => optional($this->first_published_at)->toDateTimeString(),
        ];
    }
}
