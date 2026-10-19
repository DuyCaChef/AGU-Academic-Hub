<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('danh_gia_mon_hoc', function (Blueprint $table) {
            $table->id('ma_danh_gia');
            $table->foreignId('ma_mon_hoc')->constrained('mon_hoc', 'ma_mon_hoc')->restrictOnDelete();
            $table->foreignId('ma_nguoi_dung')->constrained('nguoi_dung', 'ma_nguoi_dung')->restrictOnDelete();
            $table->string('hoc_ky', 20)->comment('Định dạng học kỳ, VD: 2026-1 hoặc 2026-2');
            $table->unsignedTinyInteger('diem_do_kho')->comment('Thang điểm độ khó 1-5');
            $table->unsignedTinyInteger('diem_thuc_tien')->comment('Thang điểm thực tiễn 1-5');
            $table->unsignedTinyInteger('diem_khoi_luong')->comment('Thang điểm khối lượng học tập 1-5');
            $table->text('noi_dung')->nullable();
            $table->boolean('an_danh')->default(true);
            $table->enum('trang_thai', ['CHO_DUYET', 'DA_DUYET', 'TAM_AN', 'TU_CHOI'])->default('DA_DUYET');
            $table->timestamps();
            $table->softDeletes('ngay_xoa_tam');

            $table->unique(['ma_mon_hoc', 'ma_nguoi_dung', 'hoc_ky'], 'danh_gia_mon_hoc_user_term_unique');
            $table->index(['ma_mon_hoc', 'trang_thai', 'created_at']);
            $table->index(['ma_nguoi_dung', 'created_at']);
        });

        // Ràng buộc CHECK điểm từ 1 đến 5 sao
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE danh_gia_mon_hoc
             ADD CONSTRAINT danh_gia_mon_hoc_difficulty_range CHECK (diem_do_kho BETWEEN 1 AND 5),
             ADD CONSTRAINT danh_gia_mon_hoc_practicality_range CHECK (diem_thuc_tien BETWEEN 1 AND 5),
             ADD CONSTRAINT danh_gia_mon_hoc_workload_range CHECK (diem_khoi_luong BETWEEN 1 AND 5)'
        );

        Schema::create('binh_chon_danh_gia', function (Blueprint $table) {
            $table->id('ma_binh_chon');
            $table->foreignId('ma_danh_gia')->constrained('danh_gia_mon_hoc', 'ma_danh_gia')->cascadeOnDelete();
            $table->foreignId('ma_nguoi_dung')->constrained('nguoi_dung', 'ma_nguoi_dung')->cascadeOnDelete();
            $table->boolean('huu_ich')->default(true);
            $table->timestamps();
            $table->unique(['ma_danh_gia', 'ma_nguoi_dung']);
        });

        Schema::create('tu_cam', function (Blueprint $table) {
            $table->id('ma_tu_cam');
            $table->string('tu_khoa', 191);
            $table->string('tu_khoa_chuan_hoa', 191)->unique();
            $table->enum('muc_do', ['THAP', 'TRUNG_BINH', 'CAO', 'NGHIEM_TRONG'])->default('TRUNG_BINH');
            $table->boolean('dang_hoat_dong')->default(true);
            $table->foreignId('nguoi_tao')->nullable()->constrained('nguoi_dung', 'ma_nguoi_dung')->nullOnDelete();
            $table->timestamps();
            $table->index(['dang_hoat_dong', 'muc_do']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tu_cam');
        Schema::dropIfExists('binh_chon_danh_gia');
        Schema::dropIfExists('danh_gia_mon_hoc');
    }
};
