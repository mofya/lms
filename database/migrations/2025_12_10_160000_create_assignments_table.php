<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable(); // Detailed instructions (rich text)
            $table->enum('type', ['text', 'file', 'code'])->default('text');
            $table->json('allowed_file_types')->nullable(); // For file/code: ['pdf', 'docx', 'py', 'js']
            $table->integer('max_file_size_mb')->default(10);
            $table->integer('max_submissions')->default(1); // 0 = unlimited until due
            $table->integer('max_points')->default(100);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('late_due_at')->nullable(); // Grace period
            $table->integer('late_penalty_percent')->default(0); // e.g., 10 = -10% per day
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
