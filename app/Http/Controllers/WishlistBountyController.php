<?php

namespace App\Http\Controllers;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauTaiLieu;
use App\Shared\Services\ReputationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WishlistBountyController extends Controller
{
    public function __construct(
        protected ReputationService $reputationService
    ) {}

    /**
     * Danh sách yêu cầu tìm tài liệu (Wishlist) (GET /yeu-cau-tai-lieu).
     */
    public function index(Request $request): Response|JsonResponse
    {
        // Chỉ hiển thị wishlist của các Môn học đang hiệu lực thực tế.
        $query = YeuCauTaiLieu::whereHas('monHoc.khoa')->with([
            'monHoc:ma_mon_hoc,ma_hoc_phan,ten_mon_hoc',
            'nguoiTao:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien,diem_uy_tin',
        ])->withCount('dapUngs');

        // Lọc theo trạng thái
        $trangThai = $request->input('trang_thai', TrangThaiYeuCauTaiLieu::MO->value);
        if ($trangThai !== 'TAT_CA') {
            $query->where('trang_thai', $trangThai);
        }

        // Lọc theo môn học
        if ($request->filled('ma_mon_hoc')) {
            $query->where('ma_mon_hoc', $request->input('ma_mon_hoc'));
        }

        // Lọc theo phân loại tài liệu
        if ($request->filled('phan_loai')) {
            $query->where('phan_loai', $request->input('phan_loai'));
        }

        // Sắp xếp: Ưu tiên điểm thưởng cao nhất hoặc mới nhất
        $sapXep = $request->input('sap_xep', 'moi_nhat');
        if ($sapXep === 'diem_cao') {
            $query->orderByDesc('diem_thuong');
        } else {
            $query->orderByDesc('ma_yeu_cau');
        }

        $danhSachYeuCau = $query->paginate(12)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($danhSachYeuCau);
        }

        return Inertia::render('Wishlist/Index', [
            'requests' => $danhSachYeuCau,
            'filters'  => $request->only(['trang_thai', 'ma_mon_hoc', 'phan_loai', 'sap_xep']),
            // Chỉ cho chọn Môn học đang hiệu lực trong bộ lọc/tạo yêu cầu.
            'courses'  => MonHoc::whereHas('khoa')
                ->select('ma_mon_hoc', 'ma_hoc_phan', 'ten_mon_hoc')
                ->orderBy('ten_mon_hoc')
                ->get(),
        ]);
    }

    /**
     * Xem chi tiết yêu cầu & các bài nộp phản hồi (GET /yeu-cau-tai-lieu/{ma_yeu_cau}).
     */
    public function show(Request $request, int $ma_yeu_cau): Response|JsonResponse
    {
        $yeuCau = YeuCauTaiLieu::with([
            'monHoc',
            'nguoiTao:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien,diem_uy_tin',
            'taiLieuDapUng',
            'dapUngs.nguoiDongGop:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien,diem_uy_tin',
            'dapUngs.taiLieu:ma_tai_lieu,tieu_de,ten_tep_goc,dung_luong_byte,loai_tep,luot_tai',
        ])->findOrFail($ma_yeu_cau);

        $currentUser = $request->user();
        $isCreator = $currentUser && ($currentUser->ma_nguoi_dung === $yeuCau->ma_nguoi_tao);

        if ($request->wantsJson()) {
            return response()->json([
                'request'    => $yeuCau,
                'is_creator' => $isCreator,
            ]);
        }

        return Inertia::render('Wishlist/Show', [
            'item'       => $yeuCau,
            'is_creator' => $isCreator,
        ]);
    }

    /**
     * Tạo yêu cầu tìm tài liệu mới kèm đóng băng điểm thưởng (POST /yeu-cau-tai-lieu).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_mon_hoc'     => ['required', 'integer', 'exists:mon_hoc,ma_mon_hoc'],
            'tieu_de'        => ['required', 'string', 'max:255'],
            'mo_ta'          => ['nullable', 'string'],
            'ten_giang_vien' => ['nullable', 'string', 'max:255'],
            'phan_loai'      => ['required', 'in:DE_THI,BAI_GIANG,DO_AN,BAI_TAP,KHAC'],
            'diem_thuong'    => ['required', 'integer', 'min:10', 'max:1000'],
        ]);

        // Không cho tạo yêu cầu mới cho Môn học thuộc Khoa đã vô hiệu hóa.
        if (!MonHoc::whereKey($validated['ma_mon_hoc'])->whereHas('khoa')->exists()) {
            return back()->withErrors([
                'ma_mon_hoc' => 'Môn học hiện không khả dụng vì Khoa đang bị vô hiệu hóa.',
            ]);
        }

        $user = $request->user();
        $bounty = (int) $validated['diem_thuong'];

        DB::transaction(function () use ($validated, $user, $bounty) {
            // 1. Đóng băng điểm thưởng từ ví người tạo
            $tx = $this->reputationService->dongBangDiemBounty($user, $bounty);

            // 2. Tạo bản ghi YeuCauTaiLieu
            $docRequest = YeuCauTaiLieu::create([
                'ma_mon_hoc'     => $validated['ma_mon_hoc'],
                'ma_nguoi_tao'   => $user->ma_nguoi_dung,
                'tieu_de'        => trim($validated['tieu_de']),
                'mo_ta'          => $validated['mo_ta'] ?? null,
                'ten_giang_vien' => $validated['ten_giang_vien'] ?? null,
                'phan_loai'      => $validated['phan_loai'],
                'diem_thuong'    => $bounty,
                'trang_thai'     => TrangThaiYeuCauTaiLieu::MO,
            ]);

            // Cập nhật lại ma_tham_chieu vào bản ghi GiaoDichDiem
            $tx->update(['ma_tham_chieu' => $docRequest->ma_yeu_cau]);
        });

        return redirect()->route('document-requests.index')
            ->with('success', "Đã tạo yêu cầu tìm tài liệu và đóng băng {$bounty} điểm thưởng.");
    }

    /**
     * Đóng góp tài liệu giải quyết yêu cầu (POST /yeu-cau-tai-lieu/{ma_yeu_cau}/dong-gop).
     */
    public function fulfill(Request $request, int $ma_yeu_cau): RedirectResponse
    {
        $validated = $request->validate([
            'ma_tai_lieu'      => ['required', 'integer', 'exists:tai_lieu,ma_tai_lieu'],
            'ghi_chu_dong_gop' => ['nullable', 'string', 'max:500'],
        ]);

        $yeuCau = YeuCauTaiLieu::findOrFail($ma_yeu_cau);
        $user = $request->user();

        // Không cho phép nộp nếu yêu cầu không ở trạng thái MO
        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::MO) {
            return back()->with('error', 'Yêu cầu này không còn mở để tiếp nhận tài liệu.');
        }

        // Không cho phép người tạo tự giải quyết yêu cầu của chính mình để gian lận điểm
        if ($yeuCau->ma_nguoi_tao === $user->ma_nguoi_dung) {
            return back()->with('error', 'Bạn không thể tự gửi tài liệu để nhận điểm thưởng của chính mình.');
        }

        $documentId = (int) $validated['ma_tai_lieu'];

        // Kiểm tra xem đã gửi tài liệu này cho yêu cầu chưa
        $exists = DapUngYeuCauTaiLieu::where('ma_yeu_cau', $yeuCau->ma_yeu_cau)
            ->where('ma_tai_lieu', $documentId)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Tài liệu này đã được gửi phản hồi trước đó.');
        }

        DapUngYeuCauTaiLieu::create([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $documentId,
            'ma_nguoi_dong_gop' => $user->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
            'ghi_chu_dong_gop'  => $validated['ghi_chu_dong_gop'] ?? null,
        ]);

        return back()->with('success', 'Đã gửi tài liệu phản hồi. Chờ người yêu cầu duyệt và giải ngân điểm!');
    }

    /**
     * Xác nhận tài liệu hợp lệ -> Giải ngân Bounty Points (POST /yeu-cau-tai-lieu/{ma_yeu_cau}/xac-nhan).
     */
    public function confirm(Request $request, int $ma_yeu_cau): RedirectResponse
    {
        $validated = $request->validate([
            'ma_dap_ung' => ['required', 'integer', 'exists:dap_ung_yeu_cau_tai_lieu,ma_dap_ung'],
        ]);

        $yeuCau = YeuCauTaiLieu::findOrFail($ma_yeu_cau);
        $user = $request->user();

        // Quyền xác nhận: Chỉ Người tạo hoặc Quản trị viên/Kiểm duyệt viên
        $canConfirm = ($yeuCau->ma_nguoi_tao === $user->ma_nguoi_dung) || $user->laQuanTriVien() || $user->laKiemDuyetVien();
        if (!$canConfirm) {
            abort(403, 'Bạn không có quyền xác nhận giải ngân cho yêu cầu này.');
        }

        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::MO) {
            return back()->with('error', 'Yêu cầu này đã được hoàn thành hoặc đã bị hủy.');
        }

        $dapUng = DapUngYeuCauTaiLieu::with(['nguoiDongGop'])
            ->where('ma_yeu_cau', $yeuCau->ma_yeu_cau)
            ->findOrFail($validated['ma_dap_ung']);

        DB::transaction(function () use ($yeuCau, $dapUng, $user) {
            // 1. Giải ngân điểm qua ReputationService
            $this->reputationService->giaiNganDiemBounty($yeuCau, $dapUng->nguoiDongGop);

            // 2. Cập nhật trạng thái bản ghi đáp ứng được chọn
            $dapUng->update([
                'trang_thai'      => 'CHAP_NHAN',
                'nguoi_duyet'     => $user->ma_nguoi_dung,
                'thoi_gian_duyet' => now(),
            ]);

            // 3. Từ chối các bản đáp ứng khác chưa được chọn (nếu có)
            DapUngYeuCauTaiLieu::where('ma_yeu_cau', $yeuCau->ma_yeu_cau)
                ->where('ma_dap_ung', '!=', $dapUng->ma_dap_ung)
                ->where('trang_thai', 'CHO_DUYET')
                ->update(['trang_thai' => 'TU_CHOI']);

            // 4. Đóng yêu cầu sang DA_HOAN_THANH
            $yeuCau->update([
                'trang_thai'          => TrangThaiYeuCauTaiLieu::DA_HOAN_THANH,
                'ma_tai_lieu_dap_ung' => $dapUng->ma_tai_lieu,
                'nguoi_xac_nhan'      => $user->ma_nguoi_dung,
                'thoi_gian_xac_nhan'  => now(),
            ]);
        });

        return back()->with('success', "Xác nhận thành công! Đã giải ngân {$yeuCau->diem_thuong} điểm cho {$dapUng->nguoiDongGop->ho_ten}.");
    }

    /**
     * Hủy yêu cầu và hoàn lại điểm thưởng vào ví (DELETE /yeu-cau-tai-lieu/{ma_yeu_cau}).
     */
    public function destroy(Request $request, int $ma_yeu_cau): RedirectResponse
    {
        $yeuCau = YeuCauTaiLieu::findOrFail($ma_yeu_cau);
        $user = $request->user();

        // Quyền hủy: Chính chủ người tạo hoặc Quản trị viên
        $canCancel = ($yeuCau->ma_nguoi_tao === $user->ma_nguoi_dung) || $user->laQuanTriVien();
        if (!$canCancel) {
            abort(403, 'Bạn không có quyền hủy yêu cầu này.');
        }

        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::MO) {
            return back()->with('error', 'Chỉ có thể hủy yêu cầu đang ở trạng thái Mở (MO).');
        }

        DB::transaction(function () use ($yeuCau) {
            // 1. Hoàn trả điểm qua ReputationService
            $this->reputationService->hoanDiemBounty($yeuCau);

            // 2. Chuyển trạng thái yêu cầu sang DA_HUY
            $yeuCau->update([
                'trang_thai' => TrangThaiYeuCauTaiLieu::DA_HUY,
            ]);

            // 3. Kích hoạt Soft Delete
            $yeuCau->delete();
        });

        return redirect()->route('document-requests.index')
            ->with('success', "Đã hủy yêu cầu và hoàn lại {$yeuCau->diem_thuong} điểm vào ví của bạn.");
    }
}