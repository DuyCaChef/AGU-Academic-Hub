<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tai_lieu', function (Blueprint $table) {
            $table->id('ma_tai_lieu');
            
            // Quản lý phiên bản (Phiên bản gốc parent_id)
            $table->foreignId('ma_tai_lieu_goc')
                  ->nullable()
                  ->constrained('tai_lieu', 'ma_tai_lieu')
                  ->nullOnDelete()
                  ->comment('Quan hệ cha-con phục vụ quản lý phiên bản (v1.1, v2.0)');

            // Khóa ngoại liên kết môn học
            $table->foreignId('ma_mon_hoc')
                  ->constrained('mon_hoc', 'ma_mon_hoc')
                  ->onDelete('cascade')
                  ->comment('Môn học chứa tài liệu này');

            // Khóa ngoại liên kết người đăng (nguoi_dung / users)
            $table->foreignId('ma_nguoi_dang')
                  ->constrained('nguoi_dung', 'ma_nguoi_dung')
                  ->onDelete('cascade')
                  ->comment('Tài khoản sinh viên/người đăng');

            $table->string('tieu_de', 255)->comment('Tiêu đề tài liệu');
            $table->string('duong_dan_tep', 500)->comment('Đường dẫn file trên server/storage');
            $table->string('dinh_dang_tep', 20)->comment('Định dạng extension (pdf, docx, zip...)');
            $table->unsignedInteger('dung_luong_kb')->comment('Dung lượng tính bằng KB');
            
            // Mã băm SHA-256 dùng lọc trùng 100% Lớp 1 (Có thiết lập Index)
            $table->string('ma_bam_file', 64)->comment('Mã băm SHA-256 kiểm tra trùng lặp nhị phân');
            $table->text('chu_ky_noi_dung')->nullable()->comment('Chữ ký/SimHash phục vụ so khớp tương đồng nội dung Lớp 2');
            
            $table->enum('phan_loai', ['DE_THI', 'BAI_GIANG', 'DO_AN', 'BAI_TAP', 'KHAC'])
                  ->default('KHAC')
                  ->comment('Phân loại tài liệu học tập');

            $table->string('phien_ban', 10)->default('1.0')->comment('Tên hiển thị phiên bản (VD: 1.0, 1.1)');
            $table->unsignedInteger('luot_tai')->default(0)->comment('Tổng số lượt tải về');
            $table->unsignedInteger('luot_thich')->default(0)->comment('Tổng số lượt upvote');
            
            $table->enum('trang_thai', ['CHO_DUYET', 'DA_DUYET', 'CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA', 'TU_CHOI'])
                  ->default('DA_DUYET')
                  ->comment('Trạng thái kiểm duyệt tài liệu');

            $table->timestamps();
            $table->softDeletes('ngay_xoa_tam');

            // Index cho trường ma_bam_file tối ưu kiểm tra trùng lặp
            $table->index('ma_bam_file', 'idx_tai_lieu_ma_bam_file');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tai_lieu');
    }
};