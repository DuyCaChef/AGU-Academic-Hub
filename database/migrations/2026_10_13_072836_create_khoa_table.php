<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khoa', function (Blueprint $table) {
            $table->id('ma_khoa');
            $table->string('ky_hieu_khoa', 20)->unique()->comment('Ký hiệu khoa (VD: CNTT, KT, SP)');
            $table->string('ten_khoa', 255)->comment('Tên đầy đủ của Khoa / Bộ môn');
            $table->timestamps();
            $table->softDeletes('ngay_xoa_tam');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khoa');
    }
};