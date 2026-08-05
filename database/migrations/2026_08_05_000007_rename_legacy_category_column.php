<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename the old enum column so Eloquent stops preferring it over the
 * category() relation. Without this, $video->category is a string and
 * $video->category->id throws.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('videos', 'category') && ! Schema::hasColumn('videos', 'legacy_category')) {
            Schema::table('videos', function (Blueprint $t) {
                $t->renameColumn('category', 'legacy_category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('videos', 'legacy_category') && ! Schema::hasColumn('videos', 'category')) {
            Schema::table('videos', function (Blueprint $t) {
                $t->renameColumn('legacy_category', 'category');
            });
        }
    }
};
