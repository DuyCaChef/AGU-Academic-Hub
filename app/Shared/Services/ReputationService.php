<?php

namespace App\Shared\Services;

use App\Enums\LoaiGiaoDichDiem;
use App\Models\GiaoDichDiem;
use App\Models\NguoiDung;
use App\Models\ViDiem;
use App\Models\YeuCauTaiLieu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReputationService
{

    // Khóa dòng ví điểm trong CSDL để chống Race Condition.
    public function khoaViDiem(int $maNguoiDung): ViDiem
    {
        return ViDiem::where('ma_nguoi_dung', $maNguoiDung)
            ->lockForUpdate()
            ->firstOrCreate(
                ['ma_nguoi_dung' => $maNguoiDung],
                ['diem_kha_dung' => 100, 'diem_tam_giu' => 0]
            );
    }

    // Đóng băng điểm thưởng khi sinh viên tạo yêu cầu tìm tài liệu.
    //Số dư khả dụng trừ đi, điểm tạm giữ tăng lên tương ứng.
    public function dongBangDiemBounty(NguoiDung $nguoiTao, int $diemThuong, ?string $khoaChongLap = null): GiaoDichDiem
    {
        if ($diemThuong <= 0) {
            throw ValidationException::withMessages([
                'diem_thuong' => 'Điểm thưởng phải lớn hơn 0.',
            ]);
        }

        $khoaChongLap = $khoaChongLap ?? (string) Str::uuid();

        // Kiểm tra Idempotency tránh lặp giao dịch
        $giaoDichCu = GiaoDichDiem::where('khoa_chong_trung', $khoaChongLap)->first();
        if ($giaoDichCu) {
            return $giaoDichCu;
        }

        $vi = $this->khoaViDiem($nguoiTao->ma_nguoi_dung);

        if ($vi->diem_kha_dung < $diemThuong) {
            throw ValidationException::withMessages([
                'diem_thuong' => "Số dư khả dụng của bạn ({$vi->diem_kha_dung} điểm) không đủ để treo thưởng {$diemThuong} điểm.",
            ]);
        }

        // Cập nhật số dư trong ví
        $vi->diem_kha_dung -= $diemThuong;
        $vi->diem_tam_giu += $diemThuong;
        $vi->save();

        // Ghi lại nhật ký biến động điểm
        return GiaoDichDiem::create([
            'ma_vi'             => $vi->ma_vi,
            'loai_giao_dich'    => LoaiGiaoDichDiem::TAM_GIU,
            'so_diem'           => $diemThuong,
            'thay_doi_kha_dung' => -$diemThuong,
            'thay_doi_tam_giu'  => $diemThuong,
            'loai_tham_chieu'   => YeuCauTaiLieu::class,
            'ma_tham_chieu'     => null, // Sẽ cập nhật sau khi YeuCauTaiLieu được tạo xong
            'khoa_chong_trung'  => $khoaChongLap,
            'mo_ta'             => "Đóng băng {$diemThuong} điểm cho yêu cầu tìm tài liệu",
            'thoi_gian_tao'     => now(),
        ]);
    }

    /**
     * Giải ngân điểm thưởng cho người đóng góp khi yêu cầu được xác nhận.
     * Người tạo: diem_tam_giu -= diem_thuong
     * Người đóng góp: diem_kha_dung += diem_thuong, diem_uy_tin += diem_thuong
     */
    public function giaiNganDiemBounty(YeuCauTaiLieu $yeuCau, NguoiDung $nguoiDongGop, ?string $khoaChongLap = null): void
    {
        $khoaChongLap = $khoaChongLap ?? "giai_ngan_yc_{$yeuCau->ma_yeu_cau}_nd_{$nguoiDongGop->ma_nguoi_dung}";

        $daGiaiNgan = GiaoDichDiem::where('khoa_chong_trung', $khoaChongLap . '_nguoi_tao')->exists();
        if ($daGiaiNgan) {
            return;
        }

        $diemThuong = $yeuCau->diem_thuong;

        // 1. Trừ điểm tạm giữ của người tạo yêu cầu
        $viNguoiTao = $this->khoaViDiem($yeuCau->ma_nguoi_tao);
        if ($viNguoiTao->diem_tam_giu < $diemThuong) {
            throw new \RuntimeException('Lỗi dữ liệu ví: Điểm tạm giữ không đủ để giải ngân.');
        }

        $viNguoiTao->diem_tam_giu -= $diemThuong;
        $viNguoiTao->save();

        GiaoDichDiem::create([
            'ma_vi'             => $viNguoiTao->ma_vi,
            'loai_giao_dich'    => LoaiGiaoDichDiem::GIAI_PHONG,
            'so_diem'           => $diemThuong,
            'thay_doi_kha_dung' => 0,
            'thay_doi_tam_giu'  => -$diemThuong,
            'loai_tham_chieu'   => YeuCauTaiLieu::class,
            'ma_tham_chieu'     => $yeuCau->ma_yeu_cau,
            'khoa_chong_trung'  => $khoaChongLap . '_nguoi_tao',
            'mo_ta'             => "Giải ngân {$diemThuong} điểm thưởng cho tài liệu hỗ trợ yêu cầu #{$yeuCau->ma_yeu_cau}",
            'thoi_gian_tao'     => now(),
        ]);

        // 2. Cộng điểm khả dụng cho người đã đóng góp tài liệu
        $viNguoiDongGop = $this->khoaViDiem($nguoiDongGop->ma_nguoi_dung);
        $viNguoiDongGop->diem_kha_dung += $diemThuong;
        $viNguoiDongGop->save();

        GiaoDichDiem::create([
            'ma_vi'             => $viNguoiDongGop->ma_vi,
            'loai_giao_dich'    => LoaiGiaoDichDiem::CONG_DIEM,
            'so_diem'           => $diemThuong,
            'thay_doi_kha_dung' => $diemThuong,
            'thay_doi_tam_giu'  => 0,
            'loai_tham_chieu'   => YeuCauTaiLieu::class,
            'ma_tham_chieu'     => $yeuCau->ma_yeu_cau,
            'khoa_chong_trung'  => $khoaChongLap . '_nguoi_nhan',
            'mo_ta'             => "Nhận {$diemThuong} điểm thưởng từ việc hỗ trợ tài liệu cho yêu cầu #{$yeuCau->ma_yeu_cau}",
            'thoi_gian_tao'     => now(),
        ]);

        // 3. Tăng điểm uy tín học thuật (diem_uy_tin) cho người đóng góp
        $nguoiDongGop->increment('diem_uy_tin', $diemThuong);
    }

    /**
     * Hoàn lại điểm tạm giữ về ví khả dụng khi người dùng hủy yêu cầu.
     * diem_tam_giu -= diem_thuong, diem_kha_dung += diem_thuong
     */
    public function hoanDiemBounty(YeuCauTaiLieu $yeuCau, ?string $khoaChongLap = null): GiaoDichDiem
    {
        $khoaChongLap = $khoaChongLap ?? "hoan_diem_yc_{$yeuCau->ma_yeu_cau}";

        $daHoan = GiaoDichDiem::where('khoa_chong_trung', $khoaChongLap)->first();
        if ($daHoan) {
            return $daHoan;
        }

        $diemThuong = $yeuCau->diem_thuong;
        $viNguoiTao = $this->khoaViDiem($yeuCau->ma_nguoi_tao);

        if ($viNguoiTao->diem_tam_giu < $diemThuong) {
            throw new \RuntimeException('Điểm tạm giữ trong ví không đủ để thực hiện hoàn tiền.');
        }

        $viNguoiTao->diem_tam_giu -= $diemThuong;
        $viNguoiTao->diem_kha_dung += $diemThuong;
        $viNguoiTao->save();

        return GiaoDichDiem::create([
            'ma_vi'             => $viNguoiTao->ma_vi,
            'loai_giao_dich'    => LoaiGiaoDichDiem::HOAN_TRA,
            'so_diem'           => $diemThuong,
            'thay_doi_kha_dung' => $diemThuong,
            'thay_doi_tam_giu'  => -$diemThuong,
            'loai_tham_chieu'   => YeuCauTaiLieu::class,
            'ma_tham_chieu'     => $yeuCau->ma_yeu_cau,
            'khoa_chong_trung'  => $khoaChongLap,
            'mo_ta'             => "Hoàn trả {$diemThuong} điểm do hủy yêu cầu tài liệu #{$yeuCau->ma_yeu_cau}",
            'thoi_gian_tao'     => now(),
        ]);
    }
}