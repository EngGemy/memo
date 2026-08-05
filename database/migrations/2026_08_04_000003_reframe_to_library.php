<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reframe: a public showcase library, not a gated course.
 *
 * Dropped: attempts, progress, questions, question_options - they enforced
 * chapter-by-chapter unlocking, which is exactly wrong for marketing videos.
 * Those need to be seen, not locked.
 *
 * Added: ownership and anti-impersonation columns. The defence is no longer
 * "stop people watching"; it is "make every copy carry the brand, and give
 * the audience one link that proves what is genuine".
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['attempts', 'progress', 'question_options', 'questions'] as $t) {
            Schema::dropIfExists($t);
        }

        Schema::table('videos', function (Blueprint $t) {
            $t->renameColumn('chapter', 'position');
        });

        Schema::table('videos', function (Blueprint $t) {
            $t->boolean('is_public')->default(false)->index();
            $t->timestamp('published_at')->nullable();
            $t->unsignedBigInteger('views')->default(0);

            // Ownership evidence, for takedowns.
            $t->string('verify_code', 12)->nullable()->unique();
            $t->string('content_sha256', 64)->nullable();
            $t->timestamp('first_published_at')->nullable();
            $t->boolean('watermark_burned')->default(false);
        });

        Schema::table('videos', function (Blueprint $t) {
            $t->dropColumn(['pass_mark', 'max_attempts']);
        });

        // Guests watch too, so the owner column has to allow null.
        Schema::table('playback_sessions', function (Blueprint $t) {
            $t->string('guest_key', 40)->nullable()->index();
        });

        Schema::create('leak_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('video_id')->nullable()->constrained()->nullOnDelete();
            $t->string('url', 500);
            $t->string('platform', 60)->nullable();
            $t->string('impersonator', 160)->nullable();
            $t->text('notes')->nullable();
            $t->enum('status', ['open','reported','removed','ignored'])->default('open')->index();
            $t->timestamp('spotted_at')->nullable();
            $t->timestamps();
        });

        // One row per view. Enough for counts and referrers, no personal data.
        Schema::create('video_views', function (Blueprint $t) {
            $t->id();
            $t->foreignId('video_id')->constrained()->cascadeOnDelete();
            $t->string('visitor_hash', 64)->index();
            $t->string('referer', 300)->nullable();
            $t->unsignedInteger('seconds_watched')->default(0);
            $t->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_views');
        Schema::dropIfExists('leak_reports');

        Schema::table('videos', function (Blueprint $t) {
            $t->dropColumn([
                'is_public','published_at','views','verify_code',
                'content_sha256','first_published_at','watermark_burned',
            ]);
            $t->unsignedTinyInteger('pass_mark')->default(75);
            $t->unsignedTinyInteger('max_attempts')->default(3);
            $t->renameColumn('position', 'chapter');
        });
    }
};
