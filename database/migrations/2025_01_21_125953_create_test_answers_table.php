<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->boolean('correct');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('test_id')->constrained('tests');
            $table->foreignId('question_id')->constrained('questions');
            $table->foreignId('option_id')->nullable()->constrained('question_options');
            $table->text('user_answer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_answers');
    }
};
