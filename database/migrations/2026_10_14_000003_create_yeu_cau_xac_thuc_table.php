<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('yeu_cau_xac_thuc', function (Blueprint $table) {
            $table->id('ma_yeu_cau_xac_thuc');
            $table->foreignId('ma_nguoi_dung')->constrained('nguoi_dung', 'ma_nguoi_dung')->cascadeOnDelete();
            $table->enum('loai_xac_thuc', ['SINH_VIEN', 'CUU_SINH_VIEN']);
            $table->string('mssv', 50)->nullable();
            $table->unsignedSmallInteger('nam_tot_nghiep')->nullable();
            $table->string('duong_dan_minh_chung', 500);
            $table->string('loai_minh_chung', 50)->nullable();
            $table->enum('trang_thai', ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI'])->default('CHO_DUYET');
            $table->foreignId('nguoi_duyet')->nullable()->constrained('nguoi_dung', 'ma_nguoi_dung')->nullOnDelete();
            $table->dateTime('thoi_gian_duyet')->nullable();
            $table->text('ly_do_tu_choi')->nullable();
            $table->timestamps();

            $table->index(['trang_thai', 'created_at']);
            $table->index(['ma_nguoi_dung', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yeu_cau_xac_thuc');
    }
};
