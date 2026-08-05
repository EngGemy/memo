<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Categories become rows instead of an enum.
 *
 * The enum meant adding a track required a migration and a deploy. As a table
 * they are editable from the dashboard, reorderable, and renameable in both
 * languages without touching code.
 *
 * The old string column stays for one release so nothing breaks mid-deploy;
 * category_id is what the app reads from here on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 40)->unique();
            $t->string('name');
            $t->string('name_ar')->nullable();
            $t->unsignedSmallInteger('position')->default(0)->index();
            $t->boolean('is_active')->default(true)->index();
            $t->timestamps();
        });

        // Seed from what the enum held, so existing videos keep their track
        // rather than landing in an uncategorised bucket.
        foreach ([
            ['fund',  'Fundamentals',   'الأساسيات',      1],
            ['prot',  'Protection',     'الحماية',        2],
            ['infra', 'Infrastructure', 'البنية التحتية', 3],
            ['asmt',  'Assessment',     'التقييم',        4],
        ] as [$slug, $en, $ar, $pos]) {
            DB::table('categories')->insert([
                'slug' => $slug, 'name' => $en, 'name_ar' => $ar,
                'position' => $pos, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::table('videos', function (Blueprint $t) {
            $t->unsignedBigInteger('category_id')->nullable()->index();
            $t->string('poster_disk', 20)->default('public');
        });

        foreach (DB::table('categories')->get() as $cat) {
            DB::table('videos')->where('category', $cat->slug)
                ->update(['category_id' => $cat->id]);
        }
    }

    public function down(): void
    {
        Schema::table('videos', fn (Blueprint $t) => $t->dropColumn(['category_id','poster_disk']));
        Schema::dropIfExists('categories');
    }
};
