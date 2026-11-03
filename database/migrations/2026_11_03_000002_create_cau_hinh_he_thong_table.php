<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cau_hinh_he_thong', function (Blueprint $table): void {
            $table->string('khoa', 100)->primary()->comment('Khóa định danh cấu hình (key)');
            $table->text('gia_tri')->nullable()->comment('Giá trị cấu hình');
            $table->string('kieu_du_lieu', 30)->default('string')->comment('string, integer, boolean, json');
            $table->string('nhom', 50)->default('chung')->comment('chung, tai_lieu, kiem_duyet, luu_tru');
            $table->string('tieu_de', 255)->comment('Tiêu đề cấu hình hiển thị UI');
            $table->text('mo_ta')->nullable()->comment('Mô tả giải thích tác động của cấu hình');
            $table->timestamps();
        });

        // Nạp cấu hình mặc định ban đầu
        $now = now();
        $defaults = [
            [
                'khoa' => 'documents.trash_retention_days',
                'gia_tri' => '60',
                'kieu_du_lieu' => 'integer',
                'nhom' => 'tai_lieu',
                'tieu_de' => 'Thời gian lưu trữ trong thùng rác (ngày)',
                'mo_ta' => 'Số ngày tối đa tài liệu được giữ trong thùng rác trước khi thanh lọc vĩnh viễn.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'khoa' => 'moderation.auto_hide_report_threshold',
                'gia_tri' => '3',
                'kieu_du_lieu' => 'integer',
                'nhom' => 'kiem_duyet',
                'tieu_de' => 'Ngưỡng tự động ẩn tài liệu khi bị báo cáo',
                'mo_ta' => 'Số lượt báo cáo hợp lệ tối thiểu để hệ thống tự động tạm ẩn tài liệu chờ kiểm duyệt.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'khoa' => 'documents.max_upload_size_mb',
                'gia_tri' => '50',
                'kieu_du_lieu' => 'integer',
                'nhom' => 'tai_lieu',
                'tieu_de' => 'Dung lượng tải lên tối đa (MB)',
                'mo_ta' => 'Kích thước tệp tài liệu tối đa cho phép tải lên hệ thống.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'khoa' => 'storage.disk_warning_percent',
                'gia_tri' => '80',
                'kieu_du_lieu' => 'integer',
                'nhom' => 'luu_tru',
                'tieu_de' => 'Ngưỡng cảnh báo vàng dung lượng ổ đĩa (%)',
                'mo_ta' => 'Tỷ lệ dung lượng đã dùng bắt đầu phát tín hiệu cảnh báo cần dọn dẹp.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'khoa' => 'storage.disk_critical_percent',
                'gia_tri' => '90',
                'kieu_du_lieu' => 'integer',
                'nhom' => 'luu_tru',
                'tieu_de' => 'Ngưỡng nguy cấp đỏ dung lượng ổ đĩa (%)',
                'mo_ta' => 'Tỷ lệ dung lượng đã dùng ở mức nghiêm trọng cần can thiệp khẩn cấp.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'khoa' => 'system.alumni_registration_open',
                'gia_tri' => '1',
                'kieu_du_lieu' => 'boolean',
                'nhom' => 'chung',
                'tieu_de' => 'Mở cổng tiếp nhận hồ sơ KYC cựu sinh viên',
                'mo_ta' => 'Bật hoặc tắt chức năng đăng ký xác thực cựu sinh viên ngoại bộ.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('cau_hinh_he_thong')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_he_thong');
    }
};

