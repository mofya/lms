<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('attempt_number')->default(1);
            $table->text('content')->nullable(); // For text submissions
            $table->string('file_path')->nullable(); // For file/code uploads
            $table->string('file_name')->nullable();
            $table->enum('status', ['draft', 'submitted', 'grading', 'graded', 'approved'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
