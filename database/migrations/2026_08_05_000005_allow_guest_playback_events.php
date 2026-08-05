<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Same fix as migration 4, for the audit table this time.
 *
 * The library is public, so most playback events have no user behind them.
 * user_id being NOT NULL meant every guest request threw on the audit write
 * and aborted mid-stream. SQLite cannot drop the constraint in place, so the
 * table is rebuilt; the old rows are logs, not data worth migrating.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('playback_events');

        Schema::create('playback_events', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->foreignId('video_id')->constrained()->cascadeOnDelete();
            $t->enum('type', ['manifest','key','segment','denied','flagged']);
            $t->string('ip', 45);
            $t->string('ua_hash', 64);
            $t->string('session_id', 64)->nullable();
            $t->json('meta')->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
            $t->index(['user_id','type','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playback_events');
    }
};