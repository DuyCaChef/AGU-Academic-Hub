<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bao_cao_danh_gia', function (Blueprint $table) {
            // Phân loại mục tiêu bị phản ánh vi phạm trong bài đánh giá (Môn học hoặc Giảng viên)
            $table->enum('loai_doi_tuong', ['MON_HOC', 'GIANG_VIEN'])
                ->default('MON_HOC')
                ->after('ma_danh_gia');

            // Tên giảng viên liên quan nếu báo cáo nhắm tới giảng viên giảng dạy
            $table->string('ten_giang_vien', 191)
                ->nullable()
                ->after('loai_doi_tuong');

            $table->index(['loai_doi_tuong', 'trang_thai'], 'bao_cao_danh_gia_loai_trang_thai_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bao_cao_danh_gia', function (Blueprint $table) {
            $table->dropIndex('bao_cao_danh_gia_loai_trang_thai_idx');
            $table->dropColumn(['loai_doi_tuong', 'ten_giang_vien']);
        });
    }
};

