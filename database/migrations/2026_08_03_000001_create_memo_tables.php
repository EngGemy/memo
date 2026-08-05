<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('role');
            $t->text('bio')->nullable();
            $t->string('avatar_path')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('videos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('expert_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedTinyInteger('chapter')->unique();   // 1..10 - ordering is the gate
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('slug')->unique();

            // storage - never web-reachable
            $t->string('master_disk')->default('private');
            $t->string('master_path')->nullable();
            $t->string('hls_path')->nullable();             // hls/{uuid}/
            $t->string('poster_path')->nullable();

            // encryption
            $t->text('encryption_key')->nullable();         // 16 raw bytes, Crypt-wrapped at rest
            $t->string('key_iv', 32)->nullable();
            $t->unsignedInteger('key_version')->default(1);

            // media
            $t->unsignedInteger('duration')->default(0);
            $t->unsignedBigInteger('size_bytes')->default(0);
            $t->json('renditions')->nullable();

            $t->enum('status', ['draft','uploading','queued','transcoding','published','failed'])
              ->default('draft')->index();
            $t->unsignedTinyInteger('progress')->default(0);
            $t->text('error')->nullable();

            // policy
            $t->unsignedTinyInteger('pass_mark')->default(75);
            $t->unsignedTinyInteger('max_attempts')->default(3);
            $t->timestamps();
        });

        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('video_id')->constrained()->cascadeOnDelete();
            $t->text('body');
            $t->unsignedInteger('ask_at')->default(0);      // second the answer comes from
            $t->unsignedTinyInteger('position')->default(1);
            $t->timestamps();
        });

        // is_correct lives here and is NEVER serialized to the client.
        Schema::create('question_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->string('body');
            $t->boolean('is_correct')->default(false);
            $t->unsignedTinyInteger('position')->default(1);
        });

        Schema::create('attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('video_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('attempt_no');
            $t->unsignedTinyInteger('score');
            $t->boolean('passed')->default(false);
            $t->json('answers');
            $t->timestamps();
            $t->index(['user_id','video_id']);
        });

        Schema::create('progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('video_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('watched_seconds')->default(0);
            $t->unsignedInteger('furthest_second')->default(0);
            $t->boolean('unlocked')->default(false);
            $t->boolean('completed')->default(false);
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id','video_id']);
        });

        // Audit trail - every key handout is recorded.
        Schema::create('playback_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('video_id')->constrained()->cascadeOnDelete();
            $t->enum('type', ['manifest','key','segment','denied','flagged']);
            $t->string('ip', 45);
            $t->string('ua_hash', 64);
            $t->string('session_id', 64)->nullable();
            $t->json('meta')->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
            $t->index(['user_id','type','created_at']);
        });

        // One live session per account.
        Schema::create('playback_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('video_id')->constrained()->cascadeOnDelete();
            $t->uuid('token')->unique();
            $t->string('ip', 45);
            $t->string('ua_hash', 64);
            $t->unsignedInteger('key_hits')->default(0);
            $t->timestamp('expires_at')->index();
            $t->timestamps();
        });

        Schema::create('upload_sessions', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('filename');
            $t->unsignedBigInteger('size_bytes');
            $t->unsignedInteger('chunk_size');
            $t->unsignedInteger('total_chunks');
            $t->json('received_chunks')->nullable();
            $t->string('sha256', 64)->nullable();
            $t->enum('status', ['open','assembling','complete','aborted'])->default('open');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['upload_sessions','playback_sessions','playback_events','progress',
                  'attempts','question_options','questions','videos','experts'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
