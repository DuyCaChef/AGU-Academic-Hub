<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tai_lieu', function (Blueprint $table): void {
            $table->foreignId('ma_nguoi_xoa')
                ->nullable()
                ->after('ngay_xoa_tam')
                ->constrained('nguoi_dung', 'ma_nguoi_dung')
                ->nullOnDelete();
            $table->text('ly_do_xoa')->nullable()->after('ma_nguoi_xoa');
            $table->string('nguon_xoa', 30)->nullable()->after('ly_do_xoa');
            $table->timestamp('han_luu_thung_rac')->nullable()->after('nguon_xoa');
            $table->index(['ngay_xoa_tam', 'ma_nguoi_xoa']);
        });
    }

    public function down(): void
    {
        Schema::table('tai_lieu', function (Blueprint $table): void {
            $table->dropForeign(['ma_nguoi_xoa']);
            $table->dropIndex(['ngay_xoa_tam', 'ma_nguoi_xoa']);
            $table->dropColumn(['ma_nguoi_xoa', 'ly_do_xoa', 'nguon_xoa', 'han_luu_thung_rac']);
        });
    }
};
