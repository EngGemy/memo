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
        // Binary paths differ per machine, so they come from .env
        // rather than being committed and overwriting each other.
        $this->app->bind(\App\Services\HlsPackager::class, fn () => new \App\Services\HlsPackager(
            env('FFMPEG_PATH', 'ffmpeg'),
            env('FFPROBE_PATH', 'ffprobe')
        ));
        // DEV: anyone signed in can manage content.
        // Replace with a real role check before going live.
        Gate::define('manage-content', fn ($user) => in_array($user->email, ['admin@memo.store'], true));
    }
}