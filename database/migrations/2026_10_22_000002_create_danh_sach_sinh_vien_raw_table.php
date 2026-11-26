<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng danh sách sinh viên đang học (dữ liệu thô được Admin/Nhà trường cung cấp).
 *
 * Luồng nghiệp vụ:
 *   1. Admin seed/import dữ liệu sinh viên vào bảng này (mssv, email_sv, mat_khau_mac_dinh).
 *   2. Sinh viên đăng nhập lần đầu bằng mssv + mat_khau_mac_dinh.
 *   3. Hệ thống tra cứu bảng này để xác minh danh tính.
 *   4. Sau khi xác minh thành công → buộc sinh viên đổi mật khẩu.
 *   5. Khi đổi xong → đánh dấu da_kich_hoat = true, trang_thai nguoi_dung = DANG_HOAT_DONG.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('danh_sach_sinh_vien_raw', function (Blueprint $table) {
            $table->id('ma_ban_ghi');

            $table->string('mssv', 50)
                ->unique()
                ->comment('Mã số sinh viên chính thức (VD: DTH215001)');

            $table->string('ho_ten', 255)
                ->comment('Họ và tên sinh viên từ dữ liệu nhà trường');

            $table->string('email_sv', 191)
                ->nullable()
                ->comment('Email sinh viên do trường cấp (@student.agu.edu.vn hoặc @agu.edu.vn)');

            $table->string('mat_khau_mac_dinh')
                ->comment('Mật khẩu mặc định được cấp (bcrypt) — bắt buộc đổi sau lần đăng nhập đầu');

            $table->string('ma_khoa', 20)
                ->nullable()
                ->comment('Mã khoa/bộ môn của sinh viên');

            $table->boolean('da_kich_hoat')
                ->default(false)
                ->comment('true = sinh viên đã đăng nhập lần đầu và đổi mật khẩu thành công');

            $table->timestamp('kich_hoat_luc')
                ->nullable()
                ->comment('Thời điểm tài khoản được kích hoạt lần đầu');

            $table->timestamps();

            // Index tìm kiếm nhanh theo mssv khi xác minh đăng nhập
            $table->index('mssv');
            $table->index('da_kich_hoat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('danh_sach_sinh_vien_raw');
    }
};
