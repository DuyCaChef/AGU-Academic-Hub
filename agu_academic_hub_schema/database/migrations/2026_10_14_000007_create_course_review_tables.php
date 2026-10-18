<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            // Format is application-validated, e.g. 2026-1 or 2026-2.
            $table->string('academic_term', 20);
            $table->unsignedTinyInteger('difficulty_score');
            $table->unsignedTinyInteger('practicality_score');
            $table->unsignedTinyInteger('workload_score');
            $table->text('content')->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->enum('status', ['PENDING', 'PUBLISHED', 'HIDDEN', 'REJECTED'])->default('PUBLISHED');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'user_id', 'academic_term'], 'course_review_user_term_unique');
            $table->index(['course_id', 'status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // MySQL enforces CHECK constraints from 8.0.16 onward.
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE course_reviews
             ADD CONSTRAINT course_reviews_difficulty_range CHECK (difficulty_score BETWEEN 1 AND 5),
             ADD CONSTRAINT course_reviews_practicality_range CHECK (practicality_score BETWEEN 1 AND 5),
             ADD CONSTRAINT course_reviews_workload_range CHECK (workload_score BETWEEN 1 AND 5)'
        );

        Schema::create('course_review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('course_reviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_helpful')->default(true);
            $table->timestamps();
            $table->unique(['review_id', 'user_id']);
        });

        Schema::create('prohibited_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 255);
            $table->string('normalized_word', 255)->unique();
            $table->enum('severity', ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])->default('MEDIUM');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_active', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prohibited_words');
        Schema::dropIfExists('course_review_votes');
        Schema::dropIfExists('course_reviews');
    }
};
