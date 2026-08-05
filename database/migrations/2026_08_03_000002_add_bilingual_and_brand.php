<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $t) {
            $t->string('title_ar')->nullable()->after('title');
            $t->text('description_ar')->nullable()->after('description');
            $t->enum('category', ['fund','prot','infra','asmt'])->default('fund')->after('chapter')->index();
        });

        Schema::table('questions', function (Blueprint $t) {
            $t->text('body_ar')->nullable()->after('body');
        });

        Schema::table('question_options', function (Blueprint $t) {
            $t->string('body_ar')->nullable()->after('body');
        });

        Schema::table('experts', function (Blueprint $t) {
            $t->string('name_ar')->nullable()->after('name');
            $t->string('role_ar')->nullable()->after('role');
            $t->text('bio_ar')->nullable()->after('bio');
        });

        // Brand settings — logo path plus the three sizes the dashboard controls.
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::table('videos', fn (Blueprint $t) => $t->dropColumn(['title_ar','description_ar','category']));
        Schema::table('questions', fn (Blueprint $t) => $t->dropColumn('body_ar'));
        Schema::table('question_options', fn (Blueprint $t) => $t->dropColumn('body_ar'));
        Schema::table('experts', fn (Blueprint $t) => $t->dropColumn(['name_ar','role_ar','bio_ar']));
    }
};
