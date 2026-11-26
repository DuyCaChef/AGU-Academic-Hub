<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('binh_chon_tai_lieu', function (Blueprint $table) {
            $table->id('ma_binh_chon');
            
            // Khóa ngoại liên kết tài liệu
            $table->foreignId('ma_tai_lieu')
                  ->constrained('tai_lieu', 'ma_tai_lieu')
                  ->cascadeOnDelete()
                  ->comment('Tài liệu được bình chọn/thích');

            // Khóa ngoại liên kết người dùng thích
            $table->foreignId('ma_nguoi_dung')
                  ->constrained('nguoi_dung', 'ma_nguoi_dung')
                  ->cascadeOnDelete()
                  ->comment('Người dùng thực hiện thích tài liệu');

            $table->timestamps();

            // Ràng buộc duy nhất: Một người dùng chỉ thích 1 tài liệu 1 lần (chống spam like)
            $table->unique(['ma_tai_lieu', 'ma_nguoi_dung'], 'unique_tai_lieu_nguoi_dung_upvote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('binh_chon_tai_lieu');
    }
};

