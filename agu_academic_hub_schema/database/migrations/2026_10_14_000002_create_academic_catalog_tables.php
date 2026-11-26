<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['faculty_id', 'name']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code', 50)->unique();
            $table->string('name', 255);
            $table->unsignedTinyInteger('credits')->default(3);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('name');
            $table->fullText(['course_code', 'name', 'description'], 'courses_search_fulltext');
        });

        Schema::create('course_majors', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->primary(['course_id', 'major_id']);
            $table->index(['major_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_majors');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('majors');
    }
};
