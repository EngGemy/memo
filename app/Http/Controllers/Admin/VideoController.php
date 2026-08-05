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
            Video::with(['expert','category'])->orderBy('position')->get()->map->forAdmin()->values()
        );
    }

    public function show(Video $video): JsonResponse
    {
        return response()->json($video->load(['expert','category'])->forAdmin());
    }

    public function update(Request $request, Video $video): JsonResponse
    {
        $data = $request->validate([
            'title'          => ['sometimes','string','max:180'],
            'title_ar'       => ['sometimes','nullable','string','max:180'],
            'description'    => ['sometimes','nullable','string','max:1000'],
            'description_ar' => ['sometimes','nullable','string','max:1000'],
            'category_id'    => ['sometimes','nullable','exists:categories,id'],
            'position'       => ['sometimes','integer','min:1','max:999'],
            'expert_id'      => ['sometimes','nullable','exists:experts,id'],
        ]);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(5));
        }

        $video->update($data);

        return response()->json($video->fresh()->load(['expert','category'])->forAdmin());
    }

    /**
     * Custom thumbnail.
     *
     * A frame grabbed 10% into the video is a reasonable default but rarely
     * the best frame. This lets a designed cover replace it. The file goes to
     * the public disk - unlike the video itself, a thumbnail is meant to be
     * seen by search engines and social previews.
     */
    public function poster(Request $request, Video $video): JsonResponse
    {
        $request->validate([
            'poster' => ['required','file','mimetypes:image/jpeg,image/png,image/webp','max:4096'],
        ]);

        // Remove the previous custom poster; the generated one lives on the
        // private disk and is left alone so a reset is always possible.
        if ($video->poster_disk === 'public' && $video->poster_path) {
            Storage::disk('public')->delete($video->poster_path);
        }

        $ext  = $request->file('poster')->extension();
        $name = 'posters/v'.$video->id.'-'.now()->timestamp.'.'.$ext;

        $request->file('poster')->storePubliclyAs(
            dirname($name), basename($name), 'public'
        );

        $video->update(['poster_path' => $name, 'poster_disk' => 'public']);

        return response()->json([
            'poster' => asset('storage/'.$name),
            'video'  => $video->fresh()->forAdmin(),
        ]);
    }

    /** Falls back to the frame ffmpeg grabbed during transcode. */
    public function resetPoster(Video $video): JsonResponse
    {
        if ($video->poster_disk === 'public' && $video->poster_path) {
            Storage::disk('public')->delete($video->poster_path);
        }

        $generated = $video->hls_path ? $video->hls_path.'/poster.jpg' : null;

        $video->update([
            'poster_path' => $generated,
            'poster_disk' => 'private',
        ]);

        return response()->json($video->fresh()->forAdmin());
    }

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

        if ($video->hls_path)   { $disk->deleteDirectory($video->hls_path); }
        if ($video->master_path) { $disk->delete($video->master_path); }
        if ($video->poster_disk === 'public' && $video->poster_path) {
            Storage::disk('public')->delete($video->poster_path);
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
