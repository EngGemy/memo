<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;

/**
 * The answer to "is this person really MEMO STORE?".
 *
 * Every video carries a short code, burned nowhere but printed under the
 * player and in the description you post alongside it. Anyone who sees a
 * suspicious re-upload can check the code here. A stolen copy either has
 * no code, or has yours - which points straight back at you.
 */
class VerifyController extends Controller
{
    public function show(Request $request, string $code)
    {
        $video = Video::with('expert')
            ->where('verify_code', strtoupper($code))
            ->where('is_public', true)
            ->first();

        $payload = $video ? [
            'verified' => true,
            'title' => $video->title,
            'title_ar' => $video->title_ar,
            'verify_code' => $video->verify_code,
            'first_published_at' => optional($video->first_published_at)->toDateString(),
            'duration' => $video->duration,
            'watch_url' => url('/?v='.$video->slug),
            'owner' => 'MEMO STORE',
            'channels' => [
                'whatsapp' => '01095236175',
                'instagram' => 'memo__store11',
                'tiktok' => 'memo__store11',
            ],
        ] : [
            'verified' => false,
            'message' => 'No official video carries this code.',
        ];

        if ($request->wantsJson()) {
            return response()->json($payload, $video ? 200 : 404);
        }

        return response()
            ->view('pages.verify', ['payload' => $payload, 'code' => strtoupper($code)])
            ->header('Cache-Control', 'no-store');
    }
}
