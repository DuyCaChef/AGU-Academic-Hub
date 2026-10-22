<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột loai_tep vào bảng yeu_cau_xac_thuc.
 * Lý do: KycController cần lưu MIME type của ảnh minh chứng để phục vụ
 * stream file trong preview() với Content-Type chính xác.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('yeu_cau_xac_thuc', function (Blueprint $table) {
            $table->string('loai_tep', 100)
                ->nullable()
                ->after('duong_dan_minh_chung')
                ->comment('MIME type của file minh chứng (vd: image/jpeg, image/png)');
        });
    }

    public function down(): void
    {
        Schema::table('yeu_cau_xac_thuc', function (Blueprint $table) {
            $table->dropColumn('loai_tep');
        });
    }
};
