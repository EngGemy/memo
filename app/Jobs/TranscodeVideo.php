<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\HlsPackager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranscodeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;
    public int $tries   = 2;

    public function __construct(public int $videoId) {}

    public function handle(HlsPackager $packager): void
    {
        $video = Video::findOrFail($this->videoId);
        $video->update(['status' => 'transcoding', 'progress' => 0, 'error' => null]);

        try {
            $result = $packager->package($video, function (int $pct) use ($video) {
                if ($pct !== $video->progress) {
                    $video->forceFill(['progress' => $pct])->saveQuietly();
                }
            });

            $video->encryption_key = $result['key'];
            $video->fill([
                'hls_path'         => $result['hls_path'],
                'poster_path'      => $result['poster'],
                'key_iv'           => $result['iv'],
                'duration'         => $result['duration'],
                'renditions'       => $result['renditions'],
                'content_sha256'   => $result['sha256'],
                'watermark_burned' => $result['watermark'],
                'status'           => 'published',
                'progress'         => 100,
            ])->save();

            Log::info('Video packaged', [
                'video'     => $video->id,
                'watermark' => $result['watermark'],
            ]);
        } catch (Throwable $e) {
            $video->update([
                'status' => 'failed',
                'error'  => mb_substr($e->getMessage(), 0, 2000),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Video::where('id', $this->videoId)->update([
            'status' => 'failed',
            'error'  => mb_substr($e->getMessage(), 0, 2000),
        ]);
    }
}
