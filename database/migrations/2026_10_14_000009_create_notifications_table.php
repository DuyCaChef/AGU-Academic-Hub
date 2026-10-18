<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Laravel-compatible notification table with additional title/message columns.
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->morphs('notifiable');
            $table->string('type');
            $table->string('title', 255)->nullable();
            $table->text('message')->nullable();
            $table->text('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id', 'read_at', 'created_at'], 'notifications_unread_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
