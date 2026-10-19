<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chuyen_nganh', function (Blueprint $table) {
            $table->id('ma_chuyen_nganh');
            $table->foreignId('ma_khoa')
                ->constrained('khoa', 'ma_khoa')
                ->restrictOnDelete()
                ->comment('Khoa trực thuộc');
            $table->string('ky_hieu_nganh', 30)->unique()->comment('Mã chuyên ngành (VD: DTH, DPM)');
            $table->string('ten_chuyen_nganh', 191)->comment('Tên đầy đủ chuyên ngành đào tạo');
            $table->text('mo_ta')->nullable()->comment('Mô tả chuyên ngành');
            $table->timestamps();
            $table->softDeletes('ngay_xoa_tam');

            $table->index(['ma_khoa', 'ten_chuyen_nganh']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chuyen_nganh');
    }
};
