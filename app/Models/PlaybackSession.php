<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaybackSession extends Model
{
    protected $fillable = ['user_id','video_id','token','ip','ua_hash','key_hits','expires_at'];
    protected $casts = ['expires_at' => 'datetime'];

    public function isValidFor(string $ip, string $uaHash): bool
    {
        // The user agent stays bound, but not the IP: mobile networks
        // reassign addresses mid-session, and a viewer losing playback
        // halfway through a video is a worse outcome than the marginal
        // hotlink protection a strict IP check would add.
        return $this->expires_at->isFuture()
            && hash_equals($this->ua_hash, $uaHash);
    }
}