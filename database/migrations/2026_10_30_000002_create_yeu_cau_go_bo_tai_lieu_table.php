<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yeu_cau_go_bo_tai_lieu', function (Blueprint $table): void {
            $table->id('ma_yeu_cau_go_bo');
            $table->foreignId('ma_tai_lieu')
                ->constrained('tai_lieu', 'ma_tai_lieu')
                ->restrictOnDelete();
            $table->foreignId('ma_nguoi_yeu_cau')
                ->constrained('nguoi_dung', 'ma_nguoi_dung')
                ->restrictOnDelete();
            $table->text('ly_do');
            $table->enum('trang_thai', ['CHO_XU_LY', 'DA_DUYET', 'TU_CHOI'])
                ->default('CHO_XU_LY');
            $table->foreignId('ma_nguoi_xu_ly')
                ->nullable()
                ->constrained('nguoi_dung', 'ma_nguoi_dung')
                ->nullOnDelete();
            $table->timestamp('xu_ly_luc')->nullable();
            $table->text('ly_do_tu_choi')->nullable();
            $table->timestamps();
            $table->index(['trang_thai', 'created_at']);
            $table->index(['ma_tai_lieu', 'trang_thai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yeu_cau_go_bo_tai_lieu');
    }
};
