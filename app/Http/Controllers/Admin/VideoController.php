<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\TranscodeVideo;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Video::with('expert')->orderBy('position')->get()->map->forAdmin()->values()
        );
    }

    public function show(Video $video): JsonResponse
    {
        return response()->json($video->load('expert')->forAdmin());
    }

    public function update(Request $request, Video $video): JsonResponse
    {
        $data = $request->validate([
            'title'          => ['sometimes','string','max:180'],
            'title_ar'       => ['sometimes','nullable','string','max:180'],
            'description'    => ['sometimes','nullable','string','max:1000'],
            'description_ar' => ['sometimes','nullable','string','max:1000'],
            'category'       => ['sometimes','in:fund,prot,infra,asmt'],
            'position'       => ['sometimes','integer','min:1','max:999'],
            'expert_id'      => ['sometimes','nullable','exists:experts,id'],
        ]);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']).'-'.$video->id;
        }

        $video->update($data);

        return response()->json($video->fresh()->load('expert')->forAdmin());
    }

    /**
     * Publishing stamps first_published_at once and never again - that
     * timestamp is the ownership evidence a takedown request rests on.
     */
    public function publish(Video $video): JsonResponse
    {
        abort_unless($video->isPlayable(), 422, 'Video is not processed yet.');

        $video->update([
            'is_public'          => true,
            'published_at'       => now(),
            'first_published_at' => $video->first_published_at ?: now(),
        ]);

        return response()->json($video->fresh()->forAdmin());
    }

    public function unpublish(Video $video): JsonResponse
    {
        $video->update(['is_public' => false]);

        return response()->json($video->fresh()->forAdmin());
    }

    public function retry(Video $video): JsonResponse
    {
        abort_unless($video->master_path, 422, 'No master file to process.');

        $video->update(['status' => 'queued', 'progress' => 0, 'error' => null]);
        TranscodeVideo::dispatch($video->id)->onQueue('transcode');

        return response()->json(['status' => 'queued']);
    }

    public function destroy(Video $video): JsonResponse
    {
        $disk = Storage::disk($video->master_disk ?: 'private');

        if ($video->hls_path) {
            $disk->deleteDirectory($video->hls_path);
        }
        if ($video->master_path) {
            $disk->delete($video->master_path);
        }

        $video->delete();

        return response()->json(['deleted' => true]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order'   => ['required','array'],
            'order.*' => ['integer','exists:videos,id'],
        ]);

        foreach ($data['order'] as $i => $id) {
            Video::where('id', $id)->update(['position' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }
}
