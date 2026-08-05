<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaybackSession extends Model
{
    protected $fillable = ['user_id','video_id','token','ip','ua_hash','key_hits','expires_at'];
    protected $casts = ['expires_at' => 'datetime'];

    public function isValidFor(string $ip, string $uaHash): bool
    {
        return $this->expires_at->isFuture()
            && hash_equals($this->ua_hash, $uaHash)
            && $this->ip === $ip;
    }
}