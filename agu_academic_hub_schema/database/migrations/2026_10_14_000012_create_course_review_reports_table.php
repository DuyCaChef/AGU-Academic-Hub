<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_review_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('course_reviews')->restrictOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->enum('reason', ['ABUSIVE_LANGUAGE', 'DEFAMATION', 'HARASSMENT', 'SPAM', 'OTHER']);
            $table->text('details')->nullable();
            $table->enum('status', ['PENDING', 'RESOLVED', 'DISMISSED'])->default('PENDING');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->unique(['review_id', 'reporter_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_review_reports');
    }
};
