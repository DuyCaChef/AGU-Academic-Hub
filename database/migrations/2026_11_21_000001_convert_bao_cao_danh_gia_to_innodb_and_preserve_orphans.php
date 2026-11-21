<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Sao lưu toàn bộ dữ liệu bảng hiện tại sang bảng sao lưu an toàn nếu chưa có
        DB::statement('CREATE TABLE IF NOT EXISTS bao_cao_danh_gia_backup_20261121 AS SELECT * FROM bao_cao_danh_gia;');

        // 2. Bổ sung các cột phục vụ lưu vết lịch sử kiểm duyệt
        Schema::table('bao_cao_danh_gia', function (Blueprint $table) {
            if (!Schema::hasColumn('bao_cao_danh_gia', 'ma_danh_gia_cu')) {
                $table->unsignedBigInteger('ma_danh_gia_cu')
                    ->nullable()
                    ->after('ma_danh_gia')
                    ->comment('Lưu vết ID bài đánh giá gốc trong trường hợp bài đánh giá đã bị xóa khỏi hệ thống');
            }

            if (!Schema::hasColumn('bao_cao_danh_gia', 'snapshot_noi_dung')) {
                $table->text('snapshot_noi_dung')
                    ->nullable()
                    ->after('chi_tiet')
                    ->comment('Snapshot nội dung bài đánh giá tại thời điểm báo cáo hoặc thông tin lịch sử xử lý');
            }
        });

        // 3. Cho phép cột ma_danh_gia nhận giá trị NULL để lưu trữ lịch sử báo cáo khi bài viết bị gỡ bỏ
        DB::statement('ALTER TABLE bao_cao_danh_gia MODIFY COLUMN ma_danh_gia BIGINT UNSIGNED NULL;');

        // 4. Bảo tồn 9 bản ghi mồ côi: Ghi nhận mã đánh giá cũ, tạo snapshot và đặt ma_danh_gia = NULL
        DB::statement("
            UPDATE bao_cao_danh_gia b
            SET 
                b.ma_danh_gia_cu = b.ma_danh_gia,
                b.snapshot_noi_dung = CONCAT('[Lịch sử kiểm duyệt] Bài đánh giá #', b.ma_danh_gia, ' đã được xử lý và gỡ bỏ trong phiên xử lý vi phạm trước đó.'),
                b.ma_danh_gia = NULL
            WHERE b.ma_danh_gia IS NOT NULL 
              AND b.ma_danh_gia NOT IN (SELECT d.ma_danh_gia FROM danh_gia_mon_hoc d);
        ");

        // 5. Chuyển đổi cả danh_gia_mon_hoc và bao_cao_danh_gia sang InnoDB để hỗ trợ đầy đủ Foreign Key và ACID
        DB::statement('ALTER TABLE danh_gia_mon_hoc ENGINE = InnoDB;');
        DB::statement('ALTER TABLE bao_cao_danh_gia ENGINE = InnoDB;');

        // 6. Thiết lập Foreign Key Constraint an toàn với chính sách ON DELETE SET NULL
        Schema::table('bao_cao_danh_gia', function (Blueprint $table) {
            $fks = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'bao_cao_danh_gia' 
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                  AND CONSTRAINT_NAME = 'fk_bao_cao_danh_gia_ma_danh_gia'
            ");

            if (empty($fks)) {
                $table->foreign('ma_danh_gia', 'fk_bao_cao_danh_gia_ma_danh_gia')
                    ->references('ma_danh_gia')
                    ->on('danh_gia_mon_hoc')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bao_cao_danh_gia', function (Blueprint $table) {
            $table->dropForeign('fk_bao_cao_danh_gia_ma_danh_gia');
        });

        // Khôi phục lại ma_danh_gia từ ma_danh_gia_cu
        DB::statement("
            UPDATE bao_cao_danh_gia 
            SET ma_danh_gia = ma_danh_gia_cu 
            WHERE ma_danh_gia IS NULL AND ma_danh_gia_cu IS NOT NULL;
        ");

        DB::statement('ALTER TABLE bao_cao_danh_gia MODIFY COLUMN ma_danh_gia BIGINT UNSIGNED NOT NULL;');

        Schema::table('bao_cao_danh_gia', function (Blueprint $table) {
            $table->dropColumn(['ma_danh_gia_cu', 'snapshot_noi_dung']);
        });

        DB::statement('ALTER TABLE bao_cao_danh_gia ENGINE = MyISAM;');
    }
};
