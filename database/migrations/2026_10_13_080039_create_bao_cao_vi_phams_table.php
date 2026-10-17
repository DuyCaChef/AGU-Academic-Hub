<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bao_cao_vi_pham', function (Blueprint $table) {
            $table->id('ma_bao_cao');
            
            // Khóa ngoại liên kết bảng tai_lieu
            $table->foreignId('ma_tai_lieu')
                  ->constrained('tai_lieu', 'ma_tai_lieu')
                  ->onDelete('cascade')
                  ->comment('Tài liệu bị báo cáo');
            
            // Khóa ngoại liên kết người báo cáo (nguoi_dung / users)
            $table->foreignId('ma_nguoi_bao_cao')
                  ->constrained('nguoi_dung', 'ma_nguoi_dung')
                  ->onDelete('cascade')
                  ->comment('Người gửi báo cáo');

            $table->enum('ly_do', ['TRUNG_LAP', 'SAI_NOI_DUNG', 'MA_DOC', 'BAN_QUYEN', 'KHAC'])
                  ->comment('Phân loại lý do báo cáo');
            $table->text('chi_tiet_loi')->nullable()->comment('Mô tả chi tiết nội dung vi phạm');
            $table->enum('trang_thai_xu_ly', ['CHO_XU_LY', 'DA_XU_LY', 'BAC_BO'])
                  ->default('CHO_XU_LY')
                  ->comment('Trạng thái xử lý của Mod/Admin');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bao_cao_vi_pham');
    }
};