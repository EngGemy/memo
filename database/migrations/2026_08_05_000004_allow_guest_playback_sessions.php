<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The library is public, so playback sessions must work without a user.
 * Migration 3 added guest_key but left user_id NOT NULL, which meant every
 * logged-out visitor hit a constraint violation and saw "playback failed".
 *
 * SQLite cannot drop a NOT NULL constraint in place, so the table is rebuilt.
 * Existing rows are disposable - sessions expire in three hours anyway.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('playback_sessions');

        Schema::create('playback_sessions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('guest_key', 40)->nullable()->index();
            $t->foreignId('video_id')->constrained()->cascadeOnDelete();
            $t->uuid('token')->unique();
            $t->string('ip', 45);
            $t->string('ua_hash', 64);
            $t->unsignedInteger('key_hits')->default(0);
            $t->timestamp('expires_at')->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playback_sessions');
    }
};