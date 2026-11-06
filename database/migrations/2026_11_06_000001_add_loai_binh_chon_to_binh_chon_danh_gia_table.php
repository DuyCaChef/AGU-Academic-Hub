<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('binh_chon_danh_gia', function (Blueprint $table) {
            // 1 = Upvote, -1 = Downvote
            $table->tinyInteger('loai_binh_chon')->default(1)->after('ma_nguoi_dung')->comment('1 = Upvote, -1 = Downvote');
        });

        // Đồng bộ dữ liệu cũ: nếu huu_ich = 1 -> loai_binh_chon = 1, nếu 0 -> loai_binh_chon = -1
        DB::table('binh_chon_danh_gia')->update([
            'loai_binh_chon' => DB::raw("CASE WHEN huu_ich = 1 THEN 1 ELSE -1 END")
        ]);
    }

    public function down(): void
    {
        Schema::table('binh_chon_danh_gia', function (Blueprint $table) {
            $table->dropColumn('loai_binh_chon');
        });
    }
};

