<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tai_lieu', function (Blueprint $table): void {
            $table->unsignedInteger('so_bao_cao_hop_le')->default(0)->after('luot_thich');
            $table->text('ly_do_trang_thai')->nullable()->after('trang_thai');
            $table->timestamp('tu_dong_chuyen_luc')->nullable()->after('ly_do_trang_thai');
            $table->index(['trang_thai', 'so_bao_cao_hop_le']);
        });

        Schema::table('bao_cao_vi_pham', function (Blueprint $table): void {
            $table->unique(['ma_tai_lieu', 'ma_nguoi_bao_cao'], 'bao_cao_tai_lieu_nguoi_duy_nhat');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tai_lieu MODIFY trang_thai ENUM('CHO_DUYET', 'DA_DUYET', 'CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA', 'TU_CHOI') NOT NULL DEFAULT 'DA_DUYET'");
        }
    }

    public function down(): void
    {
        Schema::table('bao_cao_vi_pham', function (Blueprint $table): void {
            $table->dropUnique('bao_cao_tai_lieu_nguoi_duy_nhat');
        });

        Schema::table('tai_lieu', function (Blueprint $table): void {
            $table->dropIndex(['trang_thai', 'so_bao_cao_hop_le']);
            $table->dropColumn(['so_bao_cao_hop_le', 'ly_do_trang_thai', 'tu_dong_chuyen_luc']);
        });
    }
};
