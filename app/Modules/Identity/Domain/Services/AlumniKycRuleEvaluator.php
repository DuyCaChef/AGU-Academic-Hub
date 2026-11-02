<?php

namespace App\Modules\Identity\Domain\Services;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\DanhSachSinhVienRaw;
use App\Models\NguoiDung;
use App\Models\YeuCauXacThuc;

class AlumniKycRuleEvaluator
{
    // Kiểm tra xem MSSV có thuộc danh sách sinh viên hiện hành hay không
    public function isCurrentStudent(string $mssv): bool
    {
        return DanhSachSinhVienRaw::where('mssv', strtoupper($mssv))->exists();
    }

    // Đánh giá quyền truy cập hàng đợi duyệt và xem minh chứng KYC
    public function canReviewKyc(?NguoiDung $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->laQuanTriVien() || $user->laKiemDuyetVien();
    }

    // Kiểm tra tính hợp lệ của hồ sơ KYC để tiến hành phê duyệt
    public function canApprove(YeuCauXacThuc $kyc): bool
    {
        if ($kyc->trang_thai !== TrangThaiXacThuc::CHO_DUYET) {
            return false;
        }

        if ($kyc->loai_xac_thuc !== LoaiXacThuc::CUU_SINH_VIEN) {
            return false;
        }

        if (!$kyc->nguoiDung || $kyc->nguoiDung->vai_tro !== VaiTroNguoiDung::CUU_SINH_VIEN) {
            return false;
        }

        // Sinh viên hiện hành không được duyệt với tư cách cựu sinh viên
        if ($this->isCurrentStudent((string) $kyc->nguoiDung->mssv)) {
            return false;
        }

        return true;
    }

    // Kiểm tra tính hợp lệ của hồ sơ KYC để tiến hành từ chối
    public function canReject(YeuCauXacThuc $kyc): bool
    {
        return $this->canApprove($kyc);
    }
}
