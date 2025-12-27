<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('assignment_submissions')->cascadeOnDelete();
            
            // AI-suggested values
            $table->decimal('ai_score', 5, 2)->nullable();
            $table->text('ai_feedback')->nullable();
            $table->json('ai_criteria_scores')->nullable(); // For structured rubrics
            $table->string('ai_provider')->nullable();
            $table->timestamp('ai_graded_at')->nullable();
            
            // Final approved values
            $table->decimal('final_score', 5, 2)->nullable();
            $table->text('final_feedback')->nullable();
            $table->json('final_criteria_scores')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users');
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'modified'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_grades');
    }
};
