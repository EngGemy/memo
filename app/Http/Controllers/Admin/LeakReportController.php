<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeakReport;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeakReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'video_id'     => ['nullable','exists:videos,id'],
            'url'          => ['required','url','max:500'],
            'platform'     => ['nullable','string','max:60'],
            'impersonator' => ['nullable','string','max:160'],
            'notes'        => ['nullable','string','max:2000'],
        ]);

        $data['spotted_at'] = now();

        return response()->json(LeakReport::create($data)->load('video:id,title'), 201);
    }

    public function update(Request $request, LeakReport $leakReport): JsonResponse
    {
        $leakReport->update($request->validate([
            'status' => ['required','in:open,reported,removed,ignored'],
            'notes'  => ['nullable','string','max:2000'],
        ]));

        return response()->json($leakReport->fresh());
    }

    /**
     * The evidence pack for a takedown notice: what the video is, when it
     * was first published here, and the hash of the master file. Copy-paste
     * into a platform's copyright form.
     */
    public function evidence(LeakReport $leakReport): JsonResponse
    {
        $v = $leakReport->video;
        abort_unless($v, 404, 'Report is not linked to a video.');

        return response()->json([
            'original_title'     => $v->title,
            'verify_url'         => route('verify.show', $v->verify_code),
            'verify_code'        => $v->verify_code,
            'first_published_at' => optional($v->first_published_at)->toIso8601String(),
            'duration_seconds'   => $v->duration,
            'master_sha256'      => $v->getRawOriginal('content_sha256'),
            'watermark_burned'   => $v->watermark_burned,
            'infringing_url'     => $leakReport->url,
            'platform'           => $leakReport->platform,
            'claimed_by'         => $leakReport->impersonator,
            'statement'          => sprintf(
                'The work at %s is a copy of "%s", first published at %s and carrying '.
                'a burned-in brand watermark. Ownership can be confirmed at %s.',
                $leakReport->url,
                $v->title,
                optional($v->first_published_at)->toDayDateTimeString() ?: 'an earlier date',
                route('verify.show', $v->verify_code)
            ),
        ]);
    }
}
