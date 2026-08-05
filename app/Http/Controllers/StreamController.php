<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\PlaybackGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Every byte of video passes through here. Nothing sits in public/.
 *
 * The library is open, so this is not a wall. What it stops is the cheap
 * theft: a manifest URL pasted into a downloader (signature expires in 90s),
 * an <iframe> on someone else's site (binding fails), and scripted ripping
 * of the whole library (key ceiling and rate limit).
 */
class StreamController extends Controller
{
    public function __construct(private PlaybackGuard $guard) {}

    public function manifest(Request $request, Video $video)
    {
        $session = $this->authorizeRequest($request, $video);
        $child   = $request->query('r');

        $disk = Storage::disk($video->master_disk);
        $rel  = $child ? "{$video->hls_path}/{$child}/index.m3u8" : "{$video->hls_path}/master.m3u8";

        abort_unless($disk->exists($rel), 404);

        $body = $disk->get($rel);
        $body = $child
            ? $this->signSegments($body, $video, $session->token, (int) $child)
            : $this->signRenditions($body, $video, $session->token);

        $this->log($session, $video, 'manifest', $request);

        return response($body, 200, [
            'Content-Type'  => 'application/vnd.apple.mpegurl',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
        ]);
    }

    public function key(Request $request, Video $video)
    {
        $session = $this->authorizeRequest($request, $video);

        if (! $this->guard->tapKey($session)) {
            $this->log($session, $video, 'flagged', $request);
            abort(429, 'Playback rate exceeded.');
        }

        $this->log($session, $video, 'key', $request);

        return response($video->encryption_key, 200, [
            'Content-Type'           => 'application/octet-stream',
            'Content-Length'         => 16,
            'Cache-Control'          => 'no-store, no-cache, must-revalidate, private, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function segment(Request $request, Video $video, int $rendition, string $file)
    {
        $session = $this->authorizeRequest($request, $video);

        abort_unless(preg_match('/^seg_\d{5}\.ts$/', $file), 404);

        $disk = Storage::disk($video->master_disk);
        $rel  = "{$video->hls_path}/{$rendition}/{$file}";
        abort_unless($disk->exists($rel), 404);

        $abs  = $disk->path($rel);
        $size = filesize($abs);

        if (str_ends_with($file, '0.ts')) {
            $this->log($session, $video, 'segment', $request);
        }

        return new StreamedResponse(function () use ($abs) {
            $fh = fopen($abs, 'rb');
            fpassthru($fh);
            fclose($fh);
        }, 200, [
            'Content-Type'           => 'video/mp2t',
            'Content-Length'         => $size,
            'Accept-Ranges'          => 'none',
            'Cache-Control'          => 'private, max-age=20',
            'Content-Disposition'    => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeRequest(Request $request, Video $video)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link expired.');

        $session = $this->guard->verify((string) $request->query('token'), $video, $request);

        abort_unless($session, 403, 'Session not valid on this device.');

        return $session;
    }

    private function signRenditions(string $body, Video $video, string $token): string
    {
        return preg_replace_callback('#^(\d+)/index\.m3u8$#m', fn ($m) => URL::temporarySignedRoute(
            'stream.manifest',
            now()->addSeconds(PlaybackGuard::GRANT_TTL),
            ['video' => $video->id, 'token' => $token, 'r' => $m[1]]
        ), $body);
    }

    private function signSegments(string $body, Video $video, string $token, int $rendition): string
    {
        $body = preg_replace_callback('#URI="([^"]+)"#', fn () => 'URI="'.URL::temporarySignedRoute(
            'stream.key',
            now()->addSeconds(PlaybackGuard::KEY_TTL),
            ['video' => $video->id, 'token' => $token]
        ).'"', $body);

        return preg_replace_callback('#^(seg_\d{5}\.ts)$#m', fn ($m) => URL::temporarySignedRoute(
            'stream.segment',
            now()->addSeconds(PlaybackGuard::GRANT_TTL),
            ['video' => $video->id, 'rendition' => $rendition, 'file' => $m[1], 'token' => $token]
        ), $body);
    }

    private function log($session, Video $video, string $type, Request $request): void
    {
        DB::table('playback_events')->insert([
            'user_id'    => $session->user_id,
            'video_id'   => $video->id,
            'type'       => $type,
            'ip'         => $request->ip(),
            'ua_hash'    => $this->guard->uaHash($request),
            'session_id' => $session->token,
            'meta'       => null,
            'created_at' => now(),
        ]);
    }
}
