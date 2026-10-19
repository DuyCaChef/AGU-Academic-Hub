<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nguoi_dung', function (Blueprint $table) {
            $table->id('ma_nguoi_dung');
            $table->foreignId('ma_khoa')
                  ->nullable()
                  ->constrained('khoa', 'ma_khoa')
                  ->nullOnDelete()
                  ->comment('Khoa/Bộ môn trực thuộc');
            $table->string('ho_ten', 255)->comment('Họ và tên người dùng');
            $table->string('email', 255)->unique()->comment('Địa chỉ email (@student.agu.edu.vn hoặc @agu.edu.vn)');
            $table->string('mssv', 50)->nullable()->unique()->comment('Mã số sinh viên (VD: DTH215001)');
            $table->string('mat_khau')->comment('Mật khẩu mã hóa Bcrypt');
            $table->enum('vai_tro', ['SINH_VIEN', 'CUU_SINH_VIEN', 'KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'])
                  ->default('SINH_VIEN')
                  ->comment('Vai trò phân quyền hệ thống');
            $table->enum('trang_thai', ['CHUA_XAC_THUC', 'DANG_HOAT_DONG', 'TAM_KHOA'])
                  ->default('CHUA_XAC_THUC')
                  ->comment('Trạng thái tài khoản');
            $table->unsignedInteger('diem_uy_tin')->default(100)->comment('Điểm uy tín khởi tạo');
            $table->string('anh_dai_dien', 500)->nullable()->comment('Đường dẫn ảnh đại diện avatar');
            $table->timestamp('xac_thuc_email_luc')->nullable()->comment('Thời gian xác thực email');
            $table->unsignedSmallInteger('nam_tot_nghiep')->nullable()->comment('Năm tốt nghiệp đối với cựu sinh viên');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes('ngay_xoa_tam');

            $table->index(['vai_tro', 'trang_thai']);
            $table->index(['ma_khoa', 'trang_thai']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('nguoi_dung');
    }
};
