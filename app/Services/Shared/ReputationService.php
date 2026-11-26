<?php

namespace App\Services\Shared;

use App\Models\GiaoDichDiem;
use App\Models\NguoiDung;
use App\Models\ViDiem;
use App\Models\YeuCauTaiLieu;
use App\Services\Wallet\WalletLedgerEvaluator;
use App\Services\Wallet\ReputationEvaluator;

// Service tương thích ngược, ủy quyền xử lý sang ReputationEvaluator thuộc Module Bounties
class ReputationService
{
    public function __construct(
        protected WalletLedgerEvaluator $evaluator
    ) {}

    // Khóa dòng ví điểm trong CSDL để chống Race Condition
    public function khoaViDiem(int $maNguoiDung): ViDiem
    {
        return $this->evaluator->khoaViDiem($maNguoiDung);
    }

    // Đóng băng điểm thưởng khi sinh viên tạo yêu cầu tìm tài liệu
    public function dongBangDiemBounty(NguoiDung $nguoiTao, int $diemThuong, ?string $khoaChongLap = null): GiaoDichDiem
    {
        return $this->evaluator->dongBangDiemBounty($nguoiTao, $diemThuong, $khoaChongLap);
    }

    // Giải ngân điểm thưởng cho người đóng góp khi yêu cầu được xác nhận
    public function giaiNganDiemBounty(YeuCauTaiLieu $yeuCau, NguoiDung $nguoiDongGop, ?string $khoaChongLap = null): void
    {
        $this->evaluator->giaiNganDiemBounty($yeuCau, $nguoiDongGop, $khoaChongLap);
    }

    // Hoàn lại điểm tạm giữ về ví khả dụng khi người dùng hủy yêu cầu
    public function hoanDiemBounty(YeuCauTaiLieu $yeuCau, ?string $khoaChongLap = null): GiaoDichDiem
    {
        return $this->evaluator->hoanDiemBounty($yeuCau, $khoaChongLap);
    }

    // Cộng điểm cống hiến tri thức khi tải lên tài liệu học tập
    public function congDiemDongGopTaiLieu(NguoiDung $nguoiDung, \App\Models\TaiLieu $taiLieu, int $diem = 10, ?string $khoaChongLap = null): ?GiaoDichDiem
    {
        return $this->evaluator->congDiemDongGopTaiLieu($nguoiDung, $taiLieu, $diem, $khoaChongLap);
    }
}


