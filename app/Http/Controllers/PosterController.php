<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Support\Facades\Storage;

/**
 * Serves the auto-generated poster, which lives on the private disk next to
 * the HLS output. Custom posters go to the public disk and never reach here.
 */
class PosterController extends Controller
{
    public function show(Video $video)
    {
        abort_unless($video->poster_path && $video->poster_disk !== 'public', 404);

        $disk = Storage::disk('private');
        abort_unless($disk->exists($video->poster_path), 404);

        return response()->file($disk->path($video->poster_path), [
            'Content-Type'  => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
