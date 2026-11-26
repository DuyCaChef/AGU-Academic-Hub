<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name', 255);
            $table->string('email', 255)->unique();
            $table->string('student_code', 50)->nullable()->unique();
            $table->string('password');
            $table->enum('role', ['STUDENT', 'ALUMNI', 'MODERATOR', 'ADMIN'])->default('STUDENT');
            $table->enum('status', ['UNVERIFIED', 'ACTIVE', 'SUSPENDED'])->default('UNVERIFIED');
            $table->unsignedInteger('reputation_score')->default(100);
            $table->string('avatar_path', 500)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['role', 'status']);
            $table->index(['faculty_id', 'status']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('faculties');
    }
};
