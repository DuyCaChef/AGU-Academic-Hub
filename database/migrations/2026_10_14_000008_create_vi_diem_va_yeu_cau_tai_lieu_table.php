<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vi_diem', function (Blueprint $table) {
            $table->id('ma_vi');
            $table->foreignId('ma_nguoi_dung')->unique()->constrained('nguoi_dung', 'ma_nguoi_dung')->restrictOnDelete();
            $table->bigInteger('diem_kha_dung')->default(0);
            $table->bigInteger('diem_tam_giu')->default(0);
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE vi_diem
             ADD CONSTRAINT vi_diem_available_nonnegative CHECK (diem_kha_dung >= 0),
             ADD CONSTRAINT vi_diem_held_nonnegative CHECK (diem_tam_giu >= 0)'
        );

        Schema::create('yeu_cau_tai_lieu', function (Blueprint $table) {
            $table->id('ma_yeu_cau');
            $table->foreignId('ma_mon_hoc')->constrained('mon_hoc', 'ma_mon_hoc')->restrictOnDelete();
            $table->foreignId('ma_nguoi_tao')->constrained('nguoi_dung', 'ma_nguoi_dung')->restrictOnDelete();
            $table->string('tieu_de', 255);
            $table->text('mo_ta')->nullable();
            $table->string('ten_giang_vien', 255)->nullable();
            $table->enum('phan_loai', ['DE_THI', 'BAI_GIANG', 'DO_AN', 'BAI_TAP', 'KHAC'])->default('KHAC');
            $table->unsignedInteger('diem_thuong')->default(50);
            $table->enum('trang_thai', ['MO', 'DA_HOAN_THANH', 'DONG', 'DA_HUY'])->default('MO');
            $table->foreignId('ma_tai_lieu_dap_ung')->nullable()->constrained('tai_lieu', 'ma_tai_lieu')->nullOnDelete();
            $table->foreignId('nguoi_xac_nhan')->nullable()->constrained('nguoi_dung', 'ma_nguoi_dung')->nullOnDelete();
            $table->dateTime('thoi_gian_xac_nhan')->nullable();
            $table->timestamps();
            $table->softDeletes('ngay_xoa_tam');

            $table->index(['trang_thai', 'created_at']);
            $table->index(['ma_mon_hoc', 'trang_thai']);
            $table->index(['ma_nguoi_tao', 'created_at']);
        });

        Schema::create('dap_ung_yeu_cau_tai_lieu', function (Blueprint $table) {
            $table->id('ma_dap_ung');
            $table->foreignId('ma_yeu_cau')->constrained('yeu_cau_tai_lieu', 'ma_yeu_cau')->restrictOnDelete();
            $table->foreignId('ma_tai_lieu')->constrained('tai_lieu', 'ma_tai_lieu')->restrictOnDelete();
            $table->foreignId('ma_nguoi_dong_gop')->constrained('nguoi_dung', 'ma_nguoi_dung')->restrictOnDelete();
            $table->enum('trang_thai', ['CHO_DUYET', 'CHAP_NHAN', 'TU_CHOI'])->default('CHO_DUYET');
            $table->text('ghi_chu_dong_gop')->nullable();
            $table->foreignId('nguoi_duyet')->nullable()->constrained('nguoi_dung', 'ma_nguoi_dung')->nullOnDelete();
            $table->dateTime('thoi_gian_duyet')->nullable();
            $table->timestamps();

            $table->index(['ma_yeu_cau', 'trang_thai']);
            $table->index(['ma_nguoi_dong_gop', 'created_at']);
            $table->unique(['ma_yeu_cau', 'ma_tai_lieu']);
        });

        Schema::create('giao_dich_diem', function (Blueprint $table) {
            $table->id('ma_giao_dich');
            $table->foreignId('ma_vi')->constrained('vi_diem', 'ma_vi')->restrictOnDelete();
            $table->enum('loai_giao_dich', ['CONG_DIEM', 'TAM_GIU', 'GIAI_PHONG', 'SU_DUNG', 'HOAN_TRA']);
            $table->unsignedBigInteger('so_diem');
            $table->bigInteger('thay_doi_kha_dung');
            $table->bigInteger('thay_doi_tam_giu');
            $table->string('loai_tham_chieu', 50)->nullable();
            $table->unsignedBigInteger('ma_tham_chieu')->nullable();
            $table->string('khoa_chong_trung', 100)->unique();
            $table->string('mo_ta', 255)->nullable();
            $table->timestamp('thoi_gian_tao')->useCurrent();

            $table->index(['ma_vi', 'thoi_gian_tao']);
            $table->index(['loai_tham_chieu', 'ma_tham_chieu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giao_dich_diem');
        Schema::dropIfExists('dap_ung_yeu_cau_tai_lieu');
        Schema::dropIfExists('yeu_cau_tai_lieu');
        Schema::dropIfExists('vi_diem');
    }
};
