<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_document_id')->nullable()->constrained('documents')->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploader_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('category', ['EXAM', 'LECTURE', 'PROJECT', 'EXERCISE', 'QUIZ', 'OTHER'])->default('OTHER');
            $table->string('original_filename', 255);
            $table->string('storage_disk', 50)->default('private');
            $table->string('storage_path', 500);
            $table->string('file_extension', 20);
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('file_size_bytes');
            $table->char('file_hash_sha256', 64);
            $table->string('content_fingerprint', 255)->nullable();
            $table->enum('similarity_status', ['NOT_CHECKED', 'QUEUED', 'CHECKED', 'MATCH_FOUND', 'FAILED'])->default('NOT_CHECKED');
            $table->decimal('similarity_score', 5, 4)->nullable();
            $table->string('version_label', 20)->default('1.0');
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->enum('status', ['PENDING_REVIEW', 'PUBLISHED', 'HIDDEN_FOR_REVIEW', 'REJECTED', 'SUSPENDED'])->default('PENDING_REVIEW');
            $table->text('moderation_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('deletion_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Hash uniqueness is scoped to a course, as required. MySQL permits multiple NULLs
            // but file_hash_sha256 is non-null. Soft-deleted rows still occupy this unique key.
            $table->unique(['course_id', 'file_hash_sha256'], 'documents_course_hash_unique');
            $table->index(['course_id', 'status', 'category'], 'documents_course_status_category_idx');
            $table->index(['uploader_id', 'created_at'], 'documents_uploader_created_idx');
            $table->index(['parent_document_id', 'created_at'], 'documents_parent_created_idx');
            $table->index(['status', 'created_at'], 'documents_status_created_idx');
            $table->fullText(['title', 'description'], 'documents_title_description_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
