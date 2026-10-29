<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nguoi_dung', function (Blueprint $table): void {
            $table->text('ly_do_khoa')->nullable()->after('trang_thai');
            $table->unsignedSmallInteger('so_lan_vi_pham')->default(0)->after('ly_do_khoa');
            $table->string('muc_canh_bao', 30)->nullable()->after('so_lan_vi_pham');
        });
    }

    public function down(): void
    {
        Schema::table('nguoi_dung', function (Blueprint $table): void {
            $table->dropColumn(['ly_do_khoa', 'so_lan_vi_pham', 'muc_canh_bao']);
        });
    }
};
