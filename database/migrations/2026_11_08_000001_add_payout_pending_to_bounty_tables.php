<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Thêm trạng thái CHO_GIAI_NGAN vào yeu_cau_tai_lieu
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE yeu_cau_tai_lieu MODIFY COLUMN trang_thai ENUM('MO', 'CHO_GIAI_NGAN', 'DA_HOAN_THANH', 'DONG', 'DA_HUY') NOT NULL DEFAULT 'MO'");
        }

        Schema::table('yeu_cau_tai_lieu', function (Blueprint $table) {
            $table->foreignId('ma_dap_ung_duoc_chon')
                ->nullable()
                ->constrained('dap_ung_yeu_cau_tai_lieu', 'ma_dap_ung')
                ->nullOnDelete()
                ->after('trang_thai');

            $table->dateTime('thoi_gian_nguoi_yeu_cau_xac_nhan')
                ->nullable()
                ->after('ma_dap_ung_duoc_chon');
        });

        // Thêm trạng thái NGUOI_YEU_CAU_XAC_NHAN vào dap_ung_yeu_cau_tai_lieu
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE dap_ung_yeu_cau_tai_lieu MODIFY COLUMN trang_thai ENUM('CHO_DUYET', 'NGUOI_YEU_CAU_XAC_NHAN', 'CHAP_NHAN', 'TU_CHOI') NOT NULL DEFAULT 'CHO_DUYET'");
        }

        Schema::table('dap_ung_yeu_cau_tai_lieu', function (Blueprint $table) {
            $table->text('ly_do_tu_choi_giai_ngan')
                ->nullable()
                ->after('ghi_chu_dong_gop');
        });
    }

    public function down(): void
    {
        // Lưu ý: việc khôi phục enum trên MySQL cần thực hiện thủ công nếu cần
        Schema::table('dap_ung_yeu_cau_tai_lieu', function (Blueprint $table) {
            $table->dropColumn('ly_do_tu_choi_giai_ngan');
        });

        Schema::table('yeu_cau_tai_lieu', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ma_dap_ung_duoc_chon');
            $table->dropColumn('thoi_gian_nguoi_yeu_cau_xac_nhan');
        });
    }
};
