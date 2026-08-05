<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Master file in, encrypted HLS ladder out - with the brand mark burned in.
 *
 * Everything here is derived from the source file, not assumed. An earlier
 * version hardcoded 16:9 and 25 fps, which broke on a 1080x1080 60 fps phone
 * recording three ways: the watermark came out nearly twice its intended
 * size, keyframes landed every 2.5s instead of 6s, and the bitrate was set
 * for a 16:9 frame with 44% more pixels than a square one.
 *
 * The burned watermark is the defence that survives theft. Encryption and
 * signed URLs stop downloaders; they do nothing about a screen recording.
 * A mark composited into the pixels does - the stolen copy carries the logo
 * and the phone number, so re-uploading it advertises the real owner.
 */
class HlsPackager
{
    /** Target heights, tallest first. Widths are computed from the source. */
    private const HEIGHTS = [1080, 720, 360];

    private const SEGMENT_SECONDS = 6;

    /** Bits per pixel per second. 0.08 is a sane H.264 quality point. */
    private const BPP = 0.08;

    /** Watermark width as a fraction of the actual output width. */
    private const MARK_FRACTION = 0.18;

    public function __construct(
        private string $ffmpeg  = 'ffmpeg',
        private string $ffprobe = 'ffprobe',
    ) {}

    public function package(Video $video, ?callable $onProgress = null): array
    {
        $disk      = Storage::disk($video->master_disk);
        $masterAbs = $disk->path($video->master_path);

        if (! is_file($masterAbs)) {
            throw new RuntimeException("Master file missing: {$video->master_path}");
        }

        $probe = $this->probe($masterAbs);

        if ($probe['width'] < 2 || $probe['height'] < 2) {
            throw new RuntimeException('Could not read video dimensions - is this really a video file?');
        }

        $uuid   = (string) Str::uuid();
        $hlsRel = "hls/{$uuid}";
        $hlsAbs = $disk->path($hlsRel);
        @mkdir($hlsAbs, 0750, true);

        $key = random_bytes(16);
        $iv  = bin2hex(random_bytes(16));

        $keyAbs  = "{$hlsAbs}/.key.bin";
        $infoAbs = "{$hlsAbs}/.keyinfo";
        file_put_contents($keyAbs, $key);
        file_put_contents($infoAbs, implode("\n", [
            route('stream.key', ['video' => $video->id]),
            $keyAbs,
            $iv,
        ]));

        $brand         = \App\Models\Setting::brand();
        $watermarkText = trim((($brand['brand_name'] ?? 'MEMO STORE').'  '.($brand['watermark_phone'] ?? '')));
        $watermark     = $watermarkText !== '';
        $renditions = [];

        foreach ($this->ladder($probe) as $rung) {
            @mkdir("{$hlsAbs}/{$rung['height']}", 0750, true);

            $args = [$this->ffmpeg, '-y', '-i', $masterAbs];

            if ($watermark) {
                // movie= loads the PNG as a source node inside a single-input
                // graph. A second -i input makes this ffmpeg build fail in the
                // auto-scaler, and chaining movie= after a comma makes it
                // complain about too many inputs - it has to start its own chain.
                $markW = max(48, (int) round($rung['width'] * self::MARK_FRACTION));
                $inset = max(10, (int) round($rung['width'] * 0.035));
                $png   = str_replace(['\', ':'], ['/', '\:'], $watermark);

                $args[] = '-vf';
                $args[] = "movie={$png},scale={$markW}:-2,format=rgba[wm];"
                        . "[in]scale={$rung['width']}:{$rung['height']}:flags=lanczos,setsar=1[base];"
                        . "[base][wm]overlay=W-w-{$inset}:H-h-{$inset},format=yuv420p[out]";
            } else {]}:flags=lanczos,setsar=1,format=yuv420p";
            }

            $gop = (int) round(round($rung['fps']) * self::SEGMENT_SECONDS);

            array_push($args,
                '-threads', (string) max(0, (int) env('FFMPEG_THREADS', 0)),
                '-c:v', 'libx264', '-profile:v', 'main', '-preset', 'veryfast',
                '-b:v', $rung['vb'].'k',
                '-maxrate', $rung['vb'].'k',
                '-bufsize', ($rung['vb'] * 2).'k',
                '-g', (string) $gop, '-keyint_min', (string) $gop, '-sc_threshold', '0',
                '-c:a', 'aac', '-b:a', $rung['ab'].'k', '-ac', '2', '-ar', '48000',
                '-hls_time', (string) self::SEGMENT_SECONDS,
                '-hls_playlist_type', 'vod',
                '-hls_key_info_file', $infoAbs,
                '-hls_segment_filename', "{$hlsAbs}/{$rung['height']}/seg_%05d.ts",
                "{$hlsAbs}/{$rung['height']}/index.m3u8"
            );

            $p = new Process($args, timeout: 7200);
            $p->run(function ($type, $buffer) use ($onProgress, $probe) {
                if ($onProgress && preg_match('/time=(\d+):(\d+):(\d+)/', $buffer, $m)) {
                    $done = ($m[1] * 3600) + ($m[2] * 60) + $m[3];
                    $onProgress($probe['duration'] > 0
                        ? min(99, (int) ($done / $probe['duration'] * 100))
                        : 0);
                }
            });

            if (! $p->isSuccessful()) {
                $this->cleanup($keyAbs, $infoAbs);
                throw new RuntimeException(
                    "ffmpeg failed at {$rung['height']}p: ".mb_substr($p->getErrorOutput(), -900)
                );
            }

            $renditions[] = [
                'height'    => $rung['height'],
                'width'     => $rung['width'],
                'fps'       => $rung['fps'],
                'bandwidth' => ($rung['vb'] + $rung['ab']) * 1000,
                'playlist'  => "{$rung['height']}/index.m3u8",
            ];
        }

        if ($renditions === []) {
            $this->cleanup($keyAbs, $infoAbs);
            throw new RuntimeException('No rendition produced - source resolution too low.');
        }

        $this->writeMasterPlaylist($hlsAbs, $renditions);
        $this->makePoster($masterAbs, $hlsAbs, $probe);
        $this->cleanup($keyAbs, $infoAbs);

        return [
            'hls_path'    => $hlsRel,
            'key'         => $key,
            'iv'          => $iv,
            'duration'    => (int) round($probe['duration']),
            'renditions'  => $renditions,
            'watermark'   => (bool) $watermark,
            'sha256'      => hash_file('sha256', $masterAbs),   // ownership evidence
            'poster'      => "{$hlsRel}/poster.jpg",
            'source'      => $probe,
        ];
    }

    /**
     * Builds the ladder from the real source shape.
     *
     * Square and vertical phone video is the common case here, so the width
     * follows the source aspect ratio rather than a fixed 16:9 assumption.
     * High frame rates are halved below the top rung - 60 fps on a 360p
     * stream spends bandwidth nobody can see.
     */
    private function ladder(array $probe): array
    {
        $srcW = $probe['width'];
        $srcH = $probe['height'];
        $fps  = $probe['fps'];
        $out  = [];

        foreach (self::HEIGHTS as $i => $targetH) {
            if ($targetH > (int) env('FFMPEG_MAX_HEIGHT', 1080)) {
                continue;
            }

            if ($targetH > $srcH) {
                continue;                                  // never upscale
            }

            $h = $this->even($targetH);
            $w = $this->even((int) round($srcW * $h / $srcH));

            // Top rung keeps the source frame rate; lower rungs halve it if high.
            $rungFps = ($i === 0 || $fps <= 35) ? $fps : round($fps / 2);

            $vb = (int) round($w * $h * $rungFps * self::BPP / 1000);
            $vb = max(400, min(6000, $vb));

            $out[] = [
                'height' => $targetH,
                'width'  => $w,
                'fps'    => $rungFps,
                'vb'     => $vb,
                'ab'     => $targetH >= 720 ? 128 : 96,
            ];
        }

        // Source shorter than the lowest rung - still encode one pass at source size.
        if ($out === []) {
            $h = $this->even($srcH);
            $w = $this->even($srcW);
            $out[] = [
                'height' => $h,
                'width'  => $w,
                'fps'    => $fps,
                'vb'     => max(400, (int) round($w * $h * $fps * self::BPP / 1000)),
                'ab'     => 96,
            ];
        }

        return $out;
    }

    private function even(int $n): int
    {
        return $n % 2 === 0 ? $n : $n + 1;
    }

    /** Composed in the dashboard Brand panel; null means no mark configured yet. */
    private function watermarkPath(): ?string
    {
        $rel = Setting::brand()['watermark_path'] ?? null;

        if (! $rel) {
            return null;
        }

        $abs = Storage::disk('private')->path($rel);

        return is_file($abs) ? $abs : null;
    }

    /** One ffprobe call for everything we need. */
    private function probe(string $file): array
    {
        $p = new Process([
            $this->ffprobe, '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height,r_frame_rate',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1', $file,
        ]);
        $p->run();

        $vals = [];
        foreach (explode("\n", trim($p->getOutput())) as $line) {
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                $vals[trim($k)] = trim($v);
            }
        }

        // r_frame_rate arrives as a fraction, e.g. 60/1 or 30000/1001.
        $fps = 30.0;
        if (! empty($vals['r_frame_rate']) && str_contains($vals['r_frame_rate'], '/')) {
            [$n, $d] = explode('/', $vals['r_frame_rate']);
            $fps = ((float) $d) > 0 ? round((float) $n / (float) $d, 3) : 30.0;
        }

        return [
            'width'    => (int) ($vals['width'] ?? 0),
            'height'   => (int) ($vals['height'] ?? 0),
            'fps'      => max(1.0, min(120.0, $fps)),
            'duration' => (float) ($vals['duration'] ?? 0),
        ];
    }

    /** A frame 10% in - avoids the black first frame most cameras leave. */
    private function makePoster(string $master, string $hlsAbs, array $probe): void
    {
        $at = max(1, (int) ($probe['duration'] * 0.1));
        $w  = $this->even((int) round(720 * $probe['width'] / max(1, $probe['height'])));

        $p = new Process([
            $this->ffmpeg, '-y', '-ss', (string) $at, '-i', $master,
            '-frames:v', '1', '-vf', "scale={$w}:720:flags=lanczos",
            '-q:v', '3', "{$hlsAbs}/poster.jpg",
        ], timeout: 120);
        $p->run();
    }

    private function writeMasterPlaylist(string $abs, array $renditions): void
    {
        $lines = ['#EXTM3U', '#EXT-X-VERSION:3'];

        foreach ($renditions as $r) {
            $lines[] = sprintf(
                '#EXT-X-STREAM-INF:BANDWIDTH=%d,RESOLUTION=%dx%d,FRAME-RATE=%s',
                $r['bandwidth'], $r['width'], $r['height'], $r['fps']
            );
            $lines[] = $r['playlist'];
        }

        file_put_contents("{$abs}/master.m3u8", implode("\n", $lines)."\n");
    }

    private function cleanup(string ...$paths): void
    {
        foreach ($paths as $p) {
            @unlink($p);
        }
    }
}
