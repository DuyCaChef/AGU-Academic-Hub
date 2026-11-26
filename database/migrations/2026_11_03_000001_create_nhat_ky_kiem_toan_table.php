<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nhat_ky_kiem_toan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ma_nguoi_thuc_hien')
                ->nullable()
                ->constrained('nguoi_dung', 'ma_nguoi_dung')
                ->nullOnDelete()
                ->comment('Người dùng thực hiện hành vi kiểm toán');
            $table->string('hanh_dong', 50)
                ->comment('Mã hành động nghiệp vụ (VD: DANG_NHAP_ADMIN, KHOA_TAI_KHOAN, DUYET_KYC...)');
            $table->string('loai_doi_tuong', 50)
                ->comment('Loại đối tượng bị tác động (VD: NguoiDung, TaiLieu, Khoa, CauHinhHeThong...)');
            $table->string('ma_doi_tuong', 100)
                ->nullable()
                ->comment('Định danh khóa chính hoặc mã của đối tượng bị tác động');
            $table->text('mo_ta')
                ->comment('Mô tả hành động bằng ngôn ngữ tự nhiên');
            $table->json('du_lieu_cu')
                ->nullable()
                ->comment('Dữ liệu trạng thái trước khi thay đổi (JSON snapshot)');
            $table->json('du_lieu_moi')
                ->nullable()
                ->comment('Dữ liệu trạng thái sau khi thay đổi (JSON snapshot)');
            $table->string('dia_chi_ip', 45)
                ->nullable()
                ->comment('Địa chỉ IP của client thực hiện');
            $table->text('user_agent')
                ->nullable()
                ->comment('Thông tin User-Agent / thiết bị');
            $table->timestamp('created_at')
                ->useCurrent()
                ->comment('Thời điểm ghi nhận hành vi (bất biến)');

            $table->index(['hanh_dong', 'created_at']);
            $table->index(['loai_doi_tuong', 'ma_doi_tuong']);
            $table->index(['ma_nguoi_thuc_hien', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhat_ky_kiem_toan');
    }
};

