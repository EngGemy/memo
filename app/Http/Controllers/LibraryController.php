<?php

namespace App\Http\Controllers;

use App\Models\Category;
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
 * Protection here is not access control. It is the brand watermark burned in
 * at transcode time, a per-viewer forensic overlay drawn by the player, short
 * lived signed URLs so the stream cannot be hotlinked, and a verify code that
 * lets anyone confirm the source.
 */
class LibraryController extends Controller
{
    public function __construct(private PlaybackGuard $guard) {}

    public function index(): JsonResponse
    {
        return response()->json(
            Video::with(['expert','category'])
                ->where('is_public', true)
                ->where('status', 'published')
                ->orderBy('position')
                ->get()
                ->map->forPublic()
                ->values()
        );
    }

    /** Only tracks that actually contain something public. */
    public function categories(): JsonResponse
    {
        return response()->json(
            Category::where('is_active', true)
                ->withCount(['videos' => fn ($q) => $q->where('is_public', true)->where('status', 'published')])
                ->orderBy('position')
                ->get()
                ->filter(fn ($c) => $c->videos_count > 0)
                ->map(fn ($c) => [
                    'id' => $c->id, 'slug' => $c->slug,
                    'name' => $c->name, 'name_ar' => $c->name_ar,
                    'count' => $c->videos_count,
                ])
                ->values()
        );
    }

    public function open(Request $request, string $slug): JsonResponse
    {
        $video = Video::with(['expert','category'])
            ->where('slug', $slug)->where('is_public', true)->firstOrFail();

        abort_unless($video->isPlayable(), 404);

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
            'trace'  => strtoupper(substr(md5($session->token), 0, 8)),
            'verify' => route('verify.show', $video->verify_code),
        ]);
    }

    /** One row per visitor per video per day. Enough for counts, no tracking. */
    private function countView(Request $request, Video $video): void
    {
        $hash = hash('sha256', $request->ip().'|'.$request->userAgent().'|'.now()->toDateString());

        if (DB::table('video_views')->where('video_id', $video->id)->where('visitor_hash', $hash)->exists()) {
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
