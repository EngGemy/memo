<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\PlaybackGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * The public library. No login, no gating - these videos exist to be watched.
 *
 * Protection here is not access control. It is:
 *   - the brand watermark burned into every frame at transcode time
 *   - a per-viewer forensic overlay drawn by the player
 *   - short-lived signed URLs, so the stream cannot be hotlinked elsewhere
 *   - a verify code on every video, so viewers can confirm the source
 */
class LibraryController extends Controller
{
    public function __construct(private PlaybackGuard $guard) {}

    public function index(): JsonResponse
    {
        return response()->json(
            Video::with('expert')
                ->where('is_public', true)
                ->where('status', 'published')
                ->orderBy('position')
                ->get()
                ->map->forPublic()
                ->values()
        );
    }

    /** Opens a playback session for a guest and returns signed URLs. */
    public function open(Request $request, string $slug): JsonResponse
    {
        $video = Video::where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        abort_unless($video->isPlayable(), 404);

        // Stable per-browser key so one visitor does not open fifty sessions.
        $guestKey = $request->session()->get('guest_key');
        if (! $guestKey) {
            $guestKey = (string) Str::uuid();
            $request->session()->put('guest_key', $guestKey);
        }

        $session = $this->guard->issueGuest($guestKey, $video, $request);

        $this->countView($request, $video);

        return response()->json([
            'video'    => $video->forPublic(),
            'manifest' => URL::temporarySignedRoute(
                'stream.manifest',
                now()->addSeconds(PlaybackGuard::GRANT_TTL),
                ['video' => $video->id, 'token' => $session->token]
            ),
            // Forensic overlay. Not the brand mark - that is burned into the
            // frames already. This one identifies the individual session.
            'trace'    => strtoupper(substr(md5($session->token), 0, 8)),
            'verify'   => route('verify.show', $video->verify_code),
        ]);
    }

    /** One row per visitor per video per day - enough for counts, no tracking. */
    private function countView(Request $request, Video $video): void
    {
        $hash = hash('sha256', $request->ip().'|'.$request->userAgent().'|'.now()->toDateString());

        $exists = DB::table('video_views')
            ->where('video_id', $video->id)
            ->where('visitor_hash', $hash)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('video_views')->insert([
            'video_id'     => $video->id,
            'visitor_hash' => $hash,
            'referer'      => Str::limit((string) $request->header('referer'), 290, ''),
            'created_at'   => now(),
        ]);

        $video->increment('views');
    }
}
