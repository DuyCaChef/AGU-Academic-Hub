<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->dateTime('downloaded_at');
            $table->timestamps();
            $table->index(['document_id', 'downloaded_at']);
            $table->index(['user_id', 'downloaded_at']);
        });

        Schema::create('document_upvotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['document_id', 'user_id']);
        });

        Schema::create('document_processing_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->enum('job_type', ['HASH', 'EXTRACT_TEXT', 'SIMILARITY', 'MALWARE_SCAN']);
            $table->enum('status', ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED'])->default('PENDING');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->json('result_json')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['document_id', 'job_type', 'created_at']);
        });

        Schema::create('document_similarity_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('matched_document_id')->constrained('documents')->cascadeOnDelete();
            $table->decimal('similarity_score', 5, 4);
            $table->enum('algorithm', ['SIMHASH', 'COSINE', 'OTHER']);
            $table->enum('decision', ['PENDING', 'CREATE_VERSION', 'CREATE_NEW', 'DISMISSED'])->default('PENDING');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['source_document_id', 'matched_document_id', 'algorithm'], 'doc_similarity_pair_algorithm_unique');
            $table->index(['matched_document_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_similarity_matches');
        Schema::dropIfExists('document_processing_jobs');
        Schema::dropIfExists('document_upvotes');
        Schema::dropIfExists('document_downloads');
    }
};
