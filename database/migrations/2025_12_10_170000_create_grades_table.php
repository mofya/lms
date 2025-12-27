<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            
            // Grade components
            $table->decimal('quiz_average', 5, 2)->nullable();
            $table->decimal('assignment_average', 5, 2)->nullable();
            $table->decimal('participation_score', 5, 2)->nullable();
            
            // Weighted final grade
            $table->decimal('final_grade', 5, 2)->nullable();
            
            // Grade weights (stored per course, but can be overridden)
            $table->integer('quiz_weight')->default(40); // percentage
            $table->integer('assignment_weight')->default(50);
            $table->integer('participation_weight')->default(10);
            
            // Metadata
            $table->integer('total_quizzes')->default(0);
            $table->integer('completed_quizzes')->default(0);
            $table->integer('total_assignments')->default(0);
            $table->integer('completed_assignments')->default(0);
            
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            
            // Ensure one grade record per user per course
            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
