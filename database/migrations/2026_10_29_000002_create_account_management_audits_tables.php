<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lich_su_trang_thai_nguoi_dung', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ma_nguoi_dung')->constrained('nguoi_dung', 'ma_nguoi_dung')->cascadeOnDelete();
            $table->foreignId('ma_nguoi_thuc_hien')->constrained('nguoi_dung', 'ma_nguoi_dung')->restrictOnDelete();
            $table->string('trang_thai_cu', 30)->nullable();
            $table->string('trang_thai_moi', 30);
            $table->text('ly_do')->nullable();
            $table->string('hanh_dong', 30);
            $table->timestamps();
            $table->index(['ma_nguoi_dung', 'created_at']);
        });

        Schema::create('lich_su_phan_quyen_nguoi_dung', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ma_nguoi_dung')->constrained('nguoi_dung', 'ma_nguoi_dung')->cascadeOnDelete();
            $table->foreignId('ma_nguoi_thuc_hien')->constrained('nguoi_dung', 'ma_nguoi_dung')->restrictOnDelete();
            $table->string('vai_tro_cu', 30)->nullable();
            $table->string('vai_tro_moi', 30);
            $table->unsignedBigInteger('ma_khoa_cu')->nullable();
            $table->unsignedBigInteger('ma_khoa_moi')->nullable();
            $table->text('ly_do')->nullable();
            $table->timestamps();
            $table->index(['ma_nguoi_dung', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_su_phan_quyen_nguoi_dung');
        Schema::dropIfExists('lich_su_trang_thai_nguoi_dung');
    }
};
