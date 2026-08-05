<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->bind(\App\Services\HlsPackager::class, fn () => new \App\Services\HlsPackager('C:\\Users\\SPEED LAP\\AppData\\Local\\Microsoft\\WinGet\\Packages\\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\\ffmpeg-9.0-full_build\\bin\\ffmpeg.exe', 'C:\\Users\\SPEED LAP\\AppData\\Local\\Microsoft\\WinGet\\Packages\\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\\ffmpeg-9.0-full_build\\bin\\ffprobe.exe'));
        // DEV: anyone signed in can manage content.
        // Replace with a real role check before going live.
        Gate::define('manage-content', fn ($user) => in_array($user->email, ['admin@memo.store'], true));
    }
}