<?php

namespace App\Services;

use App\Models\PlaybackSession;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Short-lived playback grants, bound to a browser.
 *
 * Since the library is public, this is not access control - anyone can get a
 * grant. What it does stop is the cheap forms of theft: hotlinking the stream
 * from another site, pasting a manifest URL into a downloader, and scripted
 * bulk ripping of the whole library.
 */
class PlaybackGuard
{
    public const GRANT_TTL   = 90;   // seconds a manifest/segment URL stays valid
    public const KEY_TTL     = 30;   // seconds a key URL stays valid
    public const KEY_CEILING = 600;  // key hits per session before it is cut off

    public function issueGuest(string $guestKey, Video $video, Request $request): PlaybackSession
    {
        PlaybackSession::where('guest_key', $guestKey)
            ->where('video_id', $video->id)
            ->delete();

        return PlaybackSession::create([
            'user_id'    => optional($request->user())->id,
            'guest_key'  => $guestKey,
            'video_id'   => $video->id,
            'token'      => (string) Str::uuid(),
            'ip'         => $request->ip(),
            'ua_hash'    => $this->uaHash($request),
            'expires_at' => now()->addHours(3),
        ]);
    }

    public function verify(string $token, Video $video, Request $request): ?PlaybackSession
    {
        $session = PlaybackSession::where('token', $token)
            ->where('video_id', $video->id)
            ->first();

        if (! $session || ! $session->isValidFor($request->ip(), $this->uaHash($request))) {
            return null;
        }

        return $session;
    }

    public function tapKey(PlaybackSession $session): bool
    {
        $limiter = "key:{$session->id}";

        if (RateLimiter::tooManyAttempts($limiter, 60)) {
            return false;
        }

        RateLimiter::hit($limiter, 60);
        DB::table('playback_sessions')->where('id', $session->id)->increment('key_hits');

        return $session->key_hits < self::KEY_CEILING;
    }

    public function uaHash(Request $request): string
    {
        return hash('sha256', (string) $request->userAgent());
    }
}
