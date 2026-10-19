<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bao_cao_danh_gia', function (Blueprint $table) {
            $table->id('ma_bao_cao');
            $table->foreignId('ma_danh_gia')->constrained('danh_gia_mon_hoc', 'ma_danh_gia')->cascadeOnDelete();
            $table->foreignId('ma_nguoi_bao_cao')->constrained('nguoi_dung', 'ma_nguoi_dung')->cascadeOnDelete();
            $table->enum('ly_do', ['NGON_TU_KICH_DONG', 'BOI_NHO', 'QUAY_ROI', 'SPAM', 'KHAC']);
            $table->text('chi_tiet')->nullable();
            $table->enum('trang_thai', ['CHO_XU_LY', 'DA_XU_LY', 'BAC_BO'])->default('CHO_XU_LY');
            $table->foreignId('nguoi_xu_ly')->nullable()->constrained('nguoi_dung', 'ma_nguoi_dung')->nullOnDelete();
            $table->dateTime('thoi_gian_xu_ly')->nullable();
            $table->text('ghi_chu_xu_ly')->nullable();
            $table->timestamps();

            $table->unique(['ma_danh_gia', 'ma_nguoi_bao_cao']);
            $table->index(['trang_thai', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bao_cao_danh_gia');
    }
};
