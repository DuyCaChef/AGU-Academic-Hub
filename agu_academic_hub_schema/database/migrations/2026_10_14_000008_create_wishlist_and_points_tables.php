<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('point_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->bigInteger('available_points')->default(0);
            $table->bigInteger('held_points')->default(0);
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE point_wallets
             ADD CONSTRAINT point_wallets_available_nonnegative CHECK (available_points >= 0),
             ADD CONSTRAINT point_wallets_held_nonnegative CHECK (held_points >= 0)'
        );

        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('creator_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('lecturer_name', 255)->nullable();
            $table->enum('category', ['EXAM', 'LECTURE', 'PROJECT', 'EXERCISE', 'QUIZ', 'OTHER'])->default('OTHER');
            $table->unsignedInteger('bounty_points')->default(50);
            $table->enum('status', ['OPEN', 'FULFILLED', 'CLOSED', 'CANCELLED'])->default('OPEN');
            $table->foreignId('fulfilled_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
            $table->index(['course_id', 'status']);
            $table->index(['creator_id', 'created_at']);
        });

        Schema::create('document_request_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('document_requests')->restrictOnDelete();
            $table->foreignId('document_id')->constrained('documents')->restrictOnDelete();
            $table->foreignId('contributor_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['PENDING', 'ACCEPTED', 'REJECTED'])->default('PENDING');
            $table->text('submitted_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['request_id', 'status']);
            $table->index(['contributor_id', 'created_at']);
            $table->unique(['request_id', 'document_id']);
        });

        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('point_wallets')->restrictOnDelete();
            $table->enum('transaction_type', ['EARN', 'HOLD', 'RELEASE', 'SPEND', 'REFUND']);
            $table->unsignedBigInteger('points');
            $table->bigInteger('available_delta');
            $table->bigInteger('held_delta');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['wallet_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('document_request_fulfillments');
        Schema::dropIfExists('document_requests');
        Schema::dropIfExists('point_wallets');
    }
};
