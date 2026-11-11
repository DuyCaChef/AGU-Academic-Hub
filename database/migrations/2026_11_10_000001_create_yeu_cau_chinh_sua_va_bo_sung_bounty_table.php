<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Bổ sung cột lý do người tạo từ chối bài đóng góp vào bảng dap_ung_yeu_cau_tai_lieu
        if (Schema::hasTable('dap_ung_yeu_cau_tai_lieu') && !Schema::hasColumn('dap_ung_yeu_cau_tai_lieu', 'ly_do_nguoi_tao_tu_choi')) {
            Schema::table('dap_ung_yeu_cau_tai_lieu', function (Blueprint $table) {
                $table->text('ly_do_nguoi_tao_tu_choi')
                    ->nullable()
                    ->after('ly_do_tu_choi_giai_ngan');
            });
        }

        // 2. Bảng Yêu Cầu Chỉnh Sửa Đóng Góp Sau Khi Bounty Hoàn Thành (100% Tiếng Việt không dấu)
        if (!Schema::hasTable('yeu_cau_chinh_sua_dong_gop')) {
            Schema::create('yeu_cau_chinh_sua_dong_gop', function (Blueprint $table) {
                $table->id('ma_yeu_cau_chinh_sua');
                $table->foreignId('ma_yeu_cau')->constrained('yeu_cau_tai_lieu', 'ma_yeu_cau', 'fk_yccs_yeu_cau')->restrictOnDelete();
                $table->foreignId('ma_dap_ung')->constrained('dap_ung_yeu_cau_tai_lieu', 'ma_dap_ung', 'fk_yccs_dap_ung')->restrictOnDelete();
                $table->foreignId('ma_nguoi_yeu_cau')->constrained('nguoi_dung', 'ma_nguoi_dung', 'fk_yccs_nguoi_yc')->restrictOnDelete();
                $table->foreignId('ma_nguoi_dong_gop')->constrained('nguoi_dung', 'ma_nguoi_dung', 'fk_yccs_nguoi_dg')->restrictOnDelete();
                $table->text('ly_do_chinh_sua');
                $table->text('ly_do_tu_choi')->nullable();
                $table->string('trang_thai', 50)->default('CHO_XU_LY');
                $table->dateTime('thoi_gian_phan_hoi')->nullable();
                $table->dateTime('thoi_gian_hoan_tat')->nullable();
                $table->timestamp('ngay_tao')->nullable();
                $table->timestamp('ngay_cap_nhat')->nullable();

                $table->index(['ma_yeu_cau', 'trang_thai'], 'idx_yccs_yc_trang_thai');
                $table->index(['ma_nguoi_dong_gop', 'trang_thai'], 'idx_yccs_nguoi_dg_trang_thai');
                $table->index(['ma_nguoi_yeu_cau', 'trang_thai'], 'idx_yccs_nguoi_yc_trang_thai');
            });
        }

        // 3. Bảng Các Lần Nộp Tài Liệu Thay Thế (100% Tiếng Việt không dấu)
        if (!Schema::hasTable('lan_nop_tai_lieu_thay_the')) {
            Schema::create('lan_nop_tai_lieu_thay_the', function (Blueprint $table) {
                $table->id('ma_lan_nop_thay_the');
                $table->foreignId('ma_yeu_cau_chinh_sua')->constrained('yeu_cau_chinh_sua_dong_gop', 'ma_yeu_cau_chinh_sua', 'fk_lntt_yccs')->cascadeOnDelete();
                $table->foreignId('ma_tai_lieu')->constrained('tai_lieu', 'ma_tai_lieu', 'fk_lntt_tai_lieu')->restrictOnDelete();
                $table->foreignId('ma_nguoi_nop')->constrained('nguoi_dung', 'ma_nguoi_dung', 'fk_lntt_nguoi_nop')->restrictOnDelete();
                $table->text('ghi_chu')->nullable();
                $table->boolean('la_phien_ban_duoc_chap_nhan')->default(false);
                $table->timestamp('ngay_tao')->nullable();
                $table->timestamp('ngay_cap_nhat')->nullable();

                $table->index(['ma_yeu_cau_chinh_sua', 'la_phien_ban_duoc_chap_nhan'], 'idx_lntt_yccs_chap_nhan');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lan_nop_tai_lieu_thay_the');
        Schema::dropIfExists('yeu_cau_chinh_sua_dong_gop');

        if (Schema::hasTable('dap_ung_yeu_cau_tai_lieu') && Schema::hasColumn('dap_ung_yeu_cau_tai_lieu', 'ly_do_nguoi_tao_tu_choi')) {
            Schema::table('dap_ung_yeu_cau_tai_lieu', function (Blueprint $table) {
                $table->dropColumn('ly_do_nguoi_tao_tu_choi');
            });
        }
    }
};
