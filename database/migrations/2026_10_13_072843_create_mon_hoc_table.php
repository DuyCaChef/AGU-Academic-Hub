<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mon_hoc', function (Blueprint $table) {
            $table->id('ma_mon_hoc');
            $table->foreignId('ma_khoa')
                ->constrained('khoa', 'ma_khoa')
                ->onDelete('cascade')
                ->comment('Khóa ngoại liên kết tới bảng khoa');
            $table->string('ma_hoc_phan', 50)->unique()->comment('Mã học phần chuẩn (VD: COS101, KTE201)');
            $table->string('ten_mon_hoc', 191)->comment('Tên môn học');
            $table->unsignedSmallInteger('so_tin_chi')->default(3)->comment('Số tín chỉ');
            $table->text('mo_ta')->nullable()->comment('Mô tả môn học');
            $table->timestamps();
            $table->softDeletes('ngay_xoa_tam');

            $table->index('ten_mon_hoc');
            $table->fullText(['ma_hoc_phan', 'ten_mon_hoc', 'mo_ta'], 'mon_hocs_search_fulltext');
        });

        Schema::create('mon_hoc_chuyen_nganh', function (Blueprint $table) {
            $table->foreignId('ma_mon_hoc')->constrained('mon_hoc', 'ma_mon_hoc')->cascadeOnDelete();
            $table->foreignId('ma_chuyen_nganh')->constrained('chuyen_nganh', 'ma_chuyen_nganh')->restrictOnDelete();
            $table->timestamps();
            $table->primary(['ma_mon_hoc', 'ma_chuyen_nganh']);
            $table->index(['ma_chuyen_nganh', 'ma_mon_hoc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mon_hoc_chuyen_nganh');
        Schema::dropIfExists('mon_hoc');
    }
};