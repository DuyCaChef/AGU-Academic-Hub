<?php

namespace Tests\Feature;

use App\Enums\LoaiGiaoDichDiem;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\GiaoDichDiem;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\ViDiem;
use App\Models\YeuCauTaiLieu;
use App\Shared\Services\ReputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistBountyTest extends TestCase
{
    use RefreshDatabase;

    protected ReputationService $reputationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reputationService = app(ReputationService::class);
    }

    public function test_dong_bang_diem_thanh_cong_khi_tao_yeu_cau(): void
    {
        $khoa = Khoa::create(['ten_khoa' => 'Khoa CNTT', 'ky_hieu_khoa' => 'CNTT']);
        $user = NguoiDung::factory()->create(['ma_khoa' => $khoa->ma_khoa]);
        // Mặc định user mới có 100 điểm khả dụng
        $vi = $user->viDiem;
        $this->assertEquals(100, $vi->diem_kha_dung);
        $this->assertEquals(0, $vi->diem_tam_giu);

        // Đóng băng 50 điểm
        $tx = $this->reputationService->dongBangDiemBounty($user, 50);

        $vi->refresh();
        $this->assertEquals(50, $vi->diem_kha_dung);
        $this->assertEquals(50, $vi->diem_tam_giu);
        $this->assertEquals(LoaiGiaoDichDiem::TAM_GIU, $tx->loai_giao_dich);
        $this->assertEquals(-50, $tx->thay_doi_kha_dung);
        $this->assertEquals(50, $tx->thay_doi_tam_giu);
    }

    public function test_khong_the_dong_bang_diem_neu_khong_du_so_du(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $khoa = Khoa::create(['ten_khoa' => 'Khoa CNTT', 'ky_hieu_khoa' => 'CNTT']);
        $user = NguoiDung::factory()->create(['ma_khoa' => $khoa->ma_khoa]);

        // Thử đóng băng 200 điểm trong khi chỉ có 100 điểm
        $this->reputationService->dongBangDiemBounty($user, 200);
    }

    public function test_giai_ngan_diem_thanh_cong_cho_nguoi_dong_gop(): void
    {
        $khoa = Khoa::create(['ten_khoa' => 'Khoa CNTT', 'ky_hieu_khoa' => 'CNTT']);
        $nguoiTao = NguoiDung::factory()->create(['ma_khoa' => $khoa->ma_khoa]);
        $nguoiDongGop = NguoiDung::factory()->create(['ma_khoa' => $khoa->ma_khoa, 'diem_uy_tin' => 100]);

        // Đóng băng 50 điểm từ người tạo
        $this->reputationService->dongBangDiemBounty($nguoiTao, 50);

        $monHoc = MonHoc::factory()->create(['ma_khoa' => $khoa->ma_khoa]);
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $monHoc->ma_mon_hoc,
            'ma_nguoi_tao' => $nguoiTao->ma_nguoi_dung,
            'tieu_de'      => 'Tìm đề thi CSDL',
            'phan_loai'    => 'DE_THI',
            'diem_thuong'  => 50,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Giải ngân 50 điểm cho người đóng góp
        $this->reputationService->giaiNganDiemBounty($yeuCau, $nguoiDongGop);

        // Kiểm tra ví người tạo: điểm tạm giữ bị trừ 50
        $viNguoiTao = $nguoiTao->viDiem->fresh();
        $this->assertEquals(50, $viNguoiTao->diem_kha_dung);
        $this->assertEquals(0, $viNguoiTao->diem_tam_giu);

        // Kiểm tra ví người đóng góp: được cộng 50 điểm khả dụng
        $viNguoiDongGop = $nguoiDongGop->viDiem->fresh();
        $this->assertEquals(150, $viNguoiDongGop->diem_kha_dung);

        // Kiểm tra điểm uy tín tăng 50
        $nguoiDongGop->refresh();
        $this->assertEquals(150, $nguoiDongGop->diem_uy_tin);
    }

    public function test_hoan_diem_thanh_cong_khi_huy_yeu_cau(): void
    {
        $khoa = Khoa::create(['ten_khoa' => 'Khoa CNTT', 'ky_hieu_khoa' => 'CNTT']);
        $nguoiTao = NguoiDung::factory()->create(['ma_khoa' => $khoa->ma_khoa]);

        // Đóng băng 60 điểm
        $this->reputationService->dongBangDiemBounty($nguoiTao, 60);

        $monHoc = MonHoc::factory()->create(['ma_khoa' => $khoa->ma_khoa]);
        $yeuCau = YeuCauTaiLieu::create([
            'ma_mon_hoc'   => $monHoc->ma_mon_hoc,
            'ma_nguoi_tao' => $nguoiTao->ma_nguoi_dung,
            'tieu_de'      => 'Cần tìm tài liệu',
            'phan_loai'    => 'BAI_GIANG',
            'diem_thuong'  => 60,
            'trang_thai'   => TrangThaiYeuCauTaiLieu::MO,
        ]);

        // Hoàn điểm
        $tx = $this->reputationService->hoanDiemBounty($yeuCau);

        $vi = $nguoiTao->viDiem->fresh();
        $this->assertEquals(100, $vi->diem_kha_dung);
        $this->assertEquals(0, $vi->diem_tam_giu);
        $this->assertEquals(LoaiGiaoDichDiem::HOAN_TRA, $tx->loai_giao_dich);
        $this->assertEquals(60, $tx->thay_doi_kha_dung);
        $this->assertEquals(-60, $tx->thay_doi_tam_giu);
    }

    public function test_luong_controller_store_confirm_destroy(): void
    {
        $khoa = Khoa::create(['ten_khoa' => 'Khoa CNTT', 'ky_hieu_khoa' => 'CNTT']);
        $userA = NguoiDung::factory()->create(['ma_khoa' => $khoa->ma_khoa]);
        $monHoc = MonHoc::factory()->create(['ma_khoa' => $khoa->ma_khoa]);

        // 1. Tạo yêu cầu qua Controller
        $response = $this->actingAs($userA)->post(route('document-requests.store'), [
            'ma_mon_hoc'     => $monHoc->ma_mon_hoc,
            'tieu_de'        => 'Đề thi cuối kỳ Kỹ thuật Lập trình',
            'mo_ta'          => 'Cần tìm đề K23',
            'ten_giang_vien' => 'ThS. Nguyễn Văn A',
            'phan_loai'      => 'DE_THI',
            'diem_thuong'    => 50,
        ]);

        $response->assertRedirect(route('document-requests.index'));
        $this->assertDatabaseHas('yeu_cau_tai_lieu', [
            'ma_mon_hoc'   => $monHoc->ma_mon_hoc,
            'ma_nguoi_tao' => $userA->ma_nguoi_dung,
            'diem_thuong'  => 50,
            'trang_thai'   => 'MO',
        ]);

        $yeuCau = YeuCauTaiLieu::first();
        $this->assertEquals(50, $userA->viDiem->fresh()->diem_tam_giu);

        // 2. Hủy yêu cầu qua Controller
        $cancelResponse = $this->actingAs($userA)->delete(route('document-requests.destroy', $yeuCau->ma_yeu_cau));
        $cancelResponse->assertRedirect(route('document-requests.index'));

        // Kiểm tra đã hoàn lại 50 điểm
        $this->assertEquals(100, $userA->viDiem->fresh()->diem_kha_dung);
        $this->assertEquals(0, $userA->viDiem->fresh()->diem_tam_giu);
    }

    // Kiểm tra trang ví điểm hiển thị đầy đủ props wallet và transactions
    public function test_authenticated_user_can_view_wallet_index_with_data_and_transactions(): void
    {
        $khoa = Khoa::create(['ten_khoa' => 'Khoa Su Pham', 'ky_hieu_khoa' => 'SP']);
        $user = NguoiDung::factory()->create(['ma_khoa' => $khoa->ma_khoa]);

        $response = $this->actingAs($user)->get(route('wallet.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Wallet/Index')
            ->has('wallet')
            ->has('transactions')
            ->has('filters')
            ->where('wallet.diem_kha_dung', 100)
            ->where('wallet.diem_tam_giu', 0)
        );
    }

    // Kiểm tra bộ lọc giao dịch ví theo loại
    public function test_wallet_index_filters_transactions_by_type(): void
    {
        $khoa = Khoa::create(['ten_khoa' => 'Khoa Su Pham', 'ky_hieu_khoa' => 'SP']);
        $user = NguoiDung::factory()->create(['ma_khoa' => $khoa->ma_khoa]);

        // Tạo 1 giao dịch tạm giữ
        $this->reputationService->dongBangDiemBounty($user, 30);

        $response = $this->actingAs($user)->get(route('wallet.index', ['type' => 'FROZEN']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Wallet/Index')
            ->where('filters.type', 'FROZEN')
            ->has('transactions.data', 1)
        );
    }

    // Kiểm tra API lấy tài liệu cá nhân phục vụ Document Picker
    public function test_authenticated_user_can_get_my_documents(): void
    {
        $khoa = Khoa::create(['ten_khoa' => 'Khoa Kinh Te', 'ky_hieu_khoa' => 'KT']);
        $user = NguoiDung::factory()->create(['ma_khoa' => $khoa->ma_khoa]);
        $monHoc = MonHoc::factory()->create(['ma_khoa' => $khoa->ma_khoa]);

        TaiLieu::create([
            'ma_mon_hoc'    => $monHoc->ma_mon_hoc,
            'ma_nguoi_dang' => $user->getKey(),
            'tieu_de'       => 'Tai lieu cua toi de nop bounty',
            'duong_dan_tep' => 'documents/my_doc.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => 100,
            'ma_bam_file'   => hash('sha256', 'my_doc_content'),
            'phan_loai'     => 'BAI_GIANG',
            'luot_tai'      => 0,
            'trang_thai'    => 'DA_DUYET',
        ]);

        $response = $this->actingAs($user)->getJson(route('documents.my-documents'));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'tieu_de' => 'Tai lieu cua toi de nop bounty',
        ]);
    }
}
