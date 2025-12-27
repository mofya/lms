<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('type')->default('text')->after('lesson_text');
            $table->string('video_url')->nullable()->after('type');
            $table->unsignedInteger('duration_seconds')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['type', 'video_url', 'duration_seconds']);
        });
    }
};

