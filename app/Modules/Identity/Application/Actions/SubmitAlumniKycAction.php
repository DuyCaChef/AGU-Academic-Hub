<?php

namespace App\Modules\Identity\Application\Actions;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\NguoiDung;
use App\Models\YeuCauXacThuc;
use App\Modules\Identity\Domain\Services\AlumniKycRuleEvaluator;
use App\Modules\Identity\Domain\ValueObjects\KycProofFile;
use App\Modules\Identity\Infrastructure\Storage\PrivateKycStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SubmitAlumniKycAction
{
    public function __construct(
        protected AlumniKycRuleEvaluator $ruleEvaluator,
        protected PrivateKycStorage $storage
    ) {}

    // Tiếp nhận và lưu trữ hồ sơ đăng ký KYC cựu sinh viên
    public function execute(array $data, UploadedFile $file): NguoiDung
    {
        // 1. Kiểm tra không cho phép sinh viên hiện hành nộp KYC cựu sinh viên
        if ($this->ruleEvaluator->isCurrentStudent((string) $data['mssv'])) {
            throw ValidationException::withMessages([
                'mssv' => 'MSSV này thuộc danh sách sinh viên đang học. Sinh viên hiện hành không nộp hồ sơ KYC cựu sinh viên.',
            ]);
        }

        return DB::transaction(function () use ($data, $file) {
            // 2. Tạo tài khoản người dùng ở trạng thái chờ xác thực
            $user = NguoiDung::create([
                'ma_khoa'        => $data['ma_khoa'] ?? null,
                'ho_ten'         => $data['ho_ten'],
                'email'          => strtolower($data['email']),
                'mssv'           => strtoupper($data['mssv']),
                'nam_tot_nghiep' => $data['nam_tot_nghiep'],
                'mat_khau'       => Hash::make($data['mat_khau']),
                'vai_tro'        => VaiTroNguoiDung::CUU_SINH_VIEN,
                'trang_thai'     => TrangThaiNguoiDung::CHUA_XAC_THUC,
                'diem_uy_tin'    => 100,
            ]);

            // 3. Băm tên file PII-safe và lưu vào private storage
            $safeFileName = KycProofFile::generateSafeFileName(
                $user->ma_nguoi_dung,
                $file->getClientOriginalExtension()
            );

            $storedPath = $this->storage->store($file, $safeFileName);

            // 4. Lưu bản ghi yêu cầu xác thực
            YeuCauXacThuc::create([
                'ma_nguoi_dung'        => $user->ma_nguoi_dung,
                'loai_xac_thuc'        => LoaiXacThuc::CUU_SINH_VIEN,
                'loai_minh_chung'      => $data['loai_minh_chung'],
                'mssv'                 => $user->mssv,
                'nam_tot_nghiep'       => $user->nam_tot_nghiep,
                'duong_dan_minh_chung' => $storedPath,
                'loai_tep'             => $file->getClientMimeType(),
                'trang_thai'           => TrangThaiXacThuc::CHO_DUYET,
            ]);

            return $user;
        });
    }
}
