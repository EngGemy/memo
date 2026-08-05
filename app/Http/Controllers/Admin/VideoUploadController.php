<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\TranscodeVideo;
use App\Models\UploadSession;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resumable chunked upload.
 *
 * Chunks are 8 MB, written straight to disk with stream_copy_to_stream, so a
 * 20 GB master never enters PHP memory. The client can ask which chunks are
 * missing and resend only those, which is what makes a dropped connection a
 * pause rather than a restart.
 */
class VideoUploadController extends Controller
{
    private const CHUNK_SIZE = 8 * 1024 * 1024;

    private const MAX_BYTES = 20 * 1024 * 1024 * 1024;

    public function open(Request $request): JsonResponse
    {
        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'size_bytes' => ['required', 'integer', 'min:1', 'max:'.self::MAX_BYTES],
            'sha256' => ['nullable', 'string', 'size:64'],
            'title' => ['required', 'string', 'max:180'],
            'title_ar' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'expert_id' => ['nullable', 'integer', 'exists:experts,id'],
        ]);

        $ext = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        abort_unless(in_array($ext, ['mp4', 'mov', 'mkv', 'm4v', 'webm'], true), 422, 'Unsupported container.');

        // New row per upload - no fixed slot to overwrite, videos are a library now.
        $video = Video::create([
            'title' => $data['title'],
            'title_ar' => $data['title_ar'] ?? null,
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'expert_id' => $data['expert_id'] ?? null,
            'position' => (int) Video::max('position') + 1,
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(5)),
            'status' => 'uploading',
            'is_public' => false,
        ]);

        $session = UploadSession::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'filename' => $data['filename'],
            'size_bytes' => $data['size_bytes'],
            'chunk_size' => self::CHUNK_SIZE,
            'total_chunks' => (int) ceil($data['size_bytes'] / self::CHUNK_SIZE),
            'received_chunks' => [],
            'sha256' => $data['sha256'] ?? null,
        ]);

        Storage::disk('private')->makeDirectory("chunks/{$session->uuid}");

        return response()->json([
            'uuid' => $session->uuid,
            'video_id' => $video->id,
            'chunk_size' => $session->chunk_size,
            'total_chunks' => $session->total_chunks,
        ], 201);
    }

    /** Raw body PUT - no multipart overhead, constant memory. */
    public function chunk(Request $request, string $uuid, int $index): JsonResponse
    {
        $session = UploadSession::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->firstOrFail();

        abort_if($index < 0 || $index >= $session->total_chunks, 422, 'Chunk index out of range.');

        $path = Storage::disk('private')->path("chunks/{$uuid}/{$index}.part");

        $in = fopen('php://input', 'rb');
        $out = fopen($path, 'wb');
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        $received = $session->received_chunks ?: [];
        if (! in_array($index, $received, true)) {
            $received[] = $index;
            $session->update(['received_chunks' => $received]);
        }

        if ($videoId = $request->integer('video_id')) {
            Video::where('id', $videoId)->update([
                'progress' => (int) (count($received) / $session->total_chunks * 100),
            ]);
        }

        return response()->json(['received' => count($received), 'total' => $session->total_chunks]);
    }

    public function status(Request $request, string $uuid): JsonResponse
    {
        $session = UploadSession::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(['status' => $session->status, 'missing' => $session->missingChunks()]);
    }

    public function complete(Request $request, string $uuid): JsonResponse
    {
        $session = UploadSession::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless($session->isComplete(), 409, 'Chunks still missing.');

        $session->update(['status' => 'assembling']);

        $disk = Storage::disk('private');
        $ext = pathinfo($session->filename, PATHINFO_EXTENSION);
        $relPath = "masters/{$uuid}.{$ext}";
        $out = fopen($disk->path($relPath), 'wb');

        for ($i = 0; $i < $session->total_chunks; $i++) {
            $part = $disk->path("chunks/{$uuid}/{$i}.part");
            $in = fopen($part, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            @unlink($part);
        }
        fclose($out);
        $disk->deleteDirectory("chunks/{$uuid}");

        if ($session->sha256 && ! hash_equals($session->sha256, hash_file('sha256', $disk->path($relPath)))) {
            $disk->delete($relPath);
            $session->update(['status' => 'aborted']);

            return response()->json(['message' => 'Checksum mismatch. Upload again.'], 422);
        }

        $video = Video::findOrFail($request->integer('video_id'));
        $video->update([
            'master_path' => $relPath,
            'master_disk' => 'private',
            'size_bytes' => $disk->size($relPath),
            'status' => 'queued',
            'progress' => 0,
        ]);

        $session->update(['status' => 'complete']);
        TranscodeVideo::dispatch($video->id)->onQueue('transcode');

        return response()->json(['video_id' => $video->id, 'status' => 'queued']);
    }
}
