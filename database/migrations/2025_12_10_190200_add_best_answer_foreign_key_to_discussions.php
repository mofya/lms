<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->foreign('best_answer_id')
                ->references('id')
                ->on('discussion_replies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->dropForeign(['best_answer_id']);
        });
    }
};
