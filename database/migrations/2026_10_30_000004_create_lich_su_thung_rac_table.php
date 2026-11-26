<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lich_su_thung_rac', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ma_tai_lieu');
            $table->foreignId('ma_nguoi_thuc_hien')
                ->nullable()
                ->constrained('nguoi_dung', 'ma_nguoi_dung')
                ->nullOnDelete();
            $table->string('hanh_dong', 30);
            $table->string('nguon', 30)->nullable();
            $table->text('ly_do')->nullable();
            $table->json('chi_tiet')->nullable();
            $table->timestamps();

            $table->index(['ma_tai_lieu', 'created_at']);
            $table->index(['hanh_dong', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_su_thung_rac');
    }
};
