<?php

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\VaiTroNguoiDung;
use App\Models\DanhSachSinhVienRaw;
use App\Models\Khoa;
use App\Models\NguoiDung;

// Repository xử lý các truy vấn xác thực người dùng, tìm kiếm dữ liệu thô và cập nhật hồ sơ
class IdentityRepository
{
    // Tìm người dùng theo Email hoặc Mã số sinh viên
    public function findUserByLogin(string $loginInput): ?NguoiDung
    {
        $clean = trim($loginInput);

        return NguoiDung::where('email', strtolower($clean))
            ->orWhere('mssv', strtoupper($clean))
            ->first();
    }

    // Tìm kiếm trong bảng dữ liệu thô sinh viên của nhà trường
    public function findRawStudent(string $loginInput): ?DanhSachSinhVienRaw
    {
        $clean = trim($loginInput);

        return DanhSachSinhVienRaw::where('email_sv', strtolower($clean))
            ->orWhere('mssv', strtoupper($clean))
            ->first();
    }

    // Tra cứu ID khoa từ mã hoặc ký hiệu khoa
    public function resolveFacultyId(?string $maKhoa): ?int
    {
        if (empty($maKhoa)) {
            return null;
        }

        if (is_numeric($maKhoa)) {
            return (int) $maKhoa;
        }

        return Khoa::where('ky_hieu_khoa', strtoupper(trim($maKhoa)))->value('ma_khoa');
    }

    // Tự động khởi tạo tài khoản từ dữ liệu thô khi sinh viên đăng nhập lần đầu
    public function createUserFromRaw(DanhSachSinhVienRaw $rawStudent, string $password): NguoiDung
    {
        $existing = NguoiDung::where('mssv', strtoupper($rawStudent->mssv))->first();
        if ($existing) {
            return $existing;
        }

        $maKhoaId = $this->resolveFacultyId($rawStudent->ma_khoa);

        return NguoiDung::create([
            'ho_ten'     => $rawStudent->ho_ten,
            'mssv'       => strtoupper($rawStudent->mssv),
            'email'      => $rawStudent->email_sv ?? (strtolower($rawStudent->mssv) . '@student.agu.edu.vn'),
            'mat_khau'   => $password, // model NguoiDung cast 'mat_khau' => 'hashed'
            'ma_khoa'    => $maKhoaId,
            'vai_tro'    => VaiTroNguoiDung::SINH_VIEN,
            'trang_thai' => TrangThaiNguoiDung::CHUA_XAC_THUC,
            'diem_uy_tin'=> 100,
        ]);
    }

    // Đăng ký tài khoản sinh viên mới
    public function registerStudent(array $data): NguoiDung
    {
        return NguoiDung::create([
            'ho_ten'    => $data['ho_ten'],
            'mssv'      => strtoupper($data['mssv']),
            'email'     => strtolower($data['email']),
            'mat_khau'  => $data['mat_khau'],
        ]);
    }

    // Cập nhật mật khẩu tài khoản
    public function updatePassword(NguoiDung $user, string $newPassword): bool
    {
        return $user->update([
            'mat_khau' => $newPassword,
        ]);
    }

    // Cập nhật thông tin hồ sơ người dùng
    public function updateProfile(NguoiDung $user, array $data): bool
    {
        if (isset($data['name'])) {
            $data['ho_ten'] = $data['name'];
            unset($data['name']);
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->xac_thuc_email_luc = null;
        }

        return $user->save();
    }

    // Xóa mềm tài khoản người dùng
    public function deleteAccount(NguoiDung $user): bool
    {
        return (bool) $user->delete();
    }
}

