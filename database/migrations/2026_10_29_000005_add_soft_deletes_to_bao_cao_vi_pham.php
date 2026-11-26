<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bao_cao_vi_pham', function (Blueprint $table) {
            $table->softDeletes('ngay_xoa_tam');
        });
    }

    public function down(): void
    {
        Schema::table('bao_cao_vi_pham', function (Blueprint $table) {
            $table->dropColumn('ngay_xoa_tam');
        });
    }
};
