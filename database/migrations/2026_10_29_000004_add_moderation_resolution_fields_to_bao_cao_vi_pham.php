<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bao_cao_vi_pham', function (Blueprint $table) {
            $table->foreignId('ma_nguoi_xu_ly')
                ->nullable()
                ->after('trang_thai_xu_ly')
                ->constrained('nguoi_dung', 'ma_nguoi_dung')
                ->nullOnDelete();
            $table->text('ly_do_xu_ly')->nullable()->after('ma_nguoi_xu_ly');
            $table->timestamp('xu_ly_luc')->nullable()->after('ly_do_xu_ly');

            $table->index(['trang_thai_xu_ly', 'ly_do']);
            $table->index('xu_ly_luc');
        });
    }

    public function down(): void
    {
        Schema::table('bao_cao_vi_pham', function (Blueprint $table) {
            $table->dropForeign(['ma_nguoi_xu_ly']);
            $table->dropIndex(['trang_thai_xu_ly', 'ly_do']);
            $table->dropIndex(['xu_ly_luc']);
            $table->dropColumn(['ma_nguoi_xu_ly', 'ly_do_xu_ly', 'xu_ly_luc']);
        });
    }
};
