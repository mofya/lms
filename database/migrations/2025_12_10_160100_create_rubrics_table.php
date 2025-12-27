<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['freeform', 'structured'])->default('freeform');
            $table->text('freeform_text')->nullable(); // For freeform rubrics
            $table->timestamps();
        });

        Schema::create('rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "Grammar", "Content Quality"
            $table->text('description')->nullable();
            $table->integer('max_points');
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_criteria');
        Schema::dropIfExists('rubrics');
    }
};
