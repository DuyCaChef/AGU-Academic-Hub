<?php 

namespace App\Http\Controllers;

use App\Enums\VaiTroNguoiDung;
use App\Http\Requests\StoreCourseReviewRequest;
use App\Http\Resources\CourseReviewResource;
use App\Models\DanhGiaMonHoc;
use App\Models\MonHoc;
use App\Shared\Services\ProfanityFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CourseReviewController extends Controller
{
    public function __construct(
        protected ProfanityFilterService $profanityFilter
    ) {}
    
    // Xem danh sách đánh giá của môn học (GET /mon-hoc/{ma_hoc_phan}/danh-gia).
    public function index (Request $request, string $ma_hoc_phan): Response|JsonResponse
    {
        // Tìm môn học dựa theo mã học phần
        $monHoc = MonHoc::where('ma_hoc_phan', $ma_hoc_phan)->firstOrFail();

        // Tinh toán điểm trung bình 3 tiêu chí của môn học
        $stats = DanhGiaMonHoc::where('ma_mon_hoc', $monHoc->ma_mon_hoc ?? $monHoc->getKey())
            ->selectRaw('
                COUNT(*) as tong_so_danh_gia,
                AVG(diem_do_kho) as avg_do_kho,
                AVG(diem_thuc_tien) as avg_thuc_tien,
                AVG(diem_khoi_luong) as avg_khoi_luong,
                AVG(diem_khoi_luong) as avg_ap_luc
            ')
            ->first();

        // Truy vấn danh sách đánh giá, eager load người dùng và khoa để tránh n+1 query
        $reviewsQuery = DanhGiaMonHoc::with(['nguoiDung.khoa'])
            ->withCount('binhChons')
            ->where('ma_mon_hoc', $monHoc->ma_mon_hoc ?? $monHoc->getKey())
            ->orderBy('ma_danh_gia', 'desc');

        $reviews = $reviewsQuery->paginate(10)->withQueryString();

        // Chuyển đổi qua Resource để che giấu mã người dùng ở tầng public
        $reviewsData = CourseReviewResource::collection($reviews);

        $thongKe = [
            'tong_so_danh_gia' => (int) ($stats->tong_so_danh_gia ?? 0),
            'avg_do_kho'       => round((float) ($stats->avg_do_kho ?? 0), 1),
            'avg_thuc_tien'    => round((float) ($stats->avg_thuc_tien ?? 0), 1),
            'avg_ap_luc'       => round((float) ($stats->avg_khoi_luong ?? $stats->avg_ap_luc ?? 0), 1),
            'avg_khoi_luong'   => round((float) ($stats->avg_khoi_luong ?? $stats->avg_ap_luc ?? 0), 1),
            'diem_tong_quan'   => round(((float) ($stats->avg_do_kho ?? 0) + (float) ($stats->avg_thuc_tien ?? 0) + (float) ($stats->avg_khoi_luong ?? $stats->avg_ap_luc ?? 0)) / 3, 1),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'mon_hoc'  => $monHoc,
                'thong_ke' => $thongKe,
                'reviews'  => $reviewsData,
            ]);
        }

        return Inertia::render('Reviews/CourseReviews', [
            'mon_hoc'  => [
                'ma_mon_hoc'  => $monHoc->ma_mon_hoc ?? $monHoc->getKey(),
                'ma_hoc_phan' => $monHoc->ma_hoc_phan,
                'ten_mon_hoc' => $monHoc->ten_mon_hoc,
                'so_tin_chi'  => $monHoc->so_tin_chi,
                'mo_ta'       => $monHoc->mo_ta,
            ],
            'thong_ke' => $thongKe,
            'reviews'  => $reviewsData,
        ]);
    }

    // Gửi bài đánh giá môn học mới (POST /mon-hoc/{ma_hoc_phan}/danh-gia).
    public function store(StoreCourseReviewRequest $request, string $ma_hoc_phan): RedirectResponse
    {
        $monHoc = MonHoc::where('ma_hoc_phan', $ma_hoc_phan)->firstOrFail();
        $user = $request->user();
        $userId = $user->ma_nguoi_dung ?? $user->getKey();

        // LỚP BẢO VỆ 2: Kiểm tra từ cấm tại controller trước khi persist
        // (FormRequest là lớp 1; controller là lớp 2 — tránh bypass qua API trực tiếp)
        $noiDung = trim((string) ($request->input('noi_dung') ?? $request->input('nhan_xet')));
        $violations = $this->profanityFilter->layDanhSachViPham($noiDung);
        if (!empty($violations)) {
            $badWords = implode(', ', array_map(fn($w) => "\"{$w}\"", $violations));
            return back()->withErrors([
                'nhan_xet' => "Nội dung nhận xét chứa từ ngữ không phù hợp chuẩn mực: {$badWords}.",
                'noi_dung' => "Nội dung nhận xét chứa từ ngữ không phù hợp chuẩn mực: {$badWords}.",
            ]);
        }

        $workloadScore = (int) ($request->input('diem_khoi_luong') ?? $request->input('diem_ap_luc_bai_tap'));
        $isAnonymous = (bool) ($request->input('an_danh') ?? $request->input('la_an_danh', true));

        // LỚP 1: Lưu trữ ma_nguoi_dung thực tế vào CSDL phục vụ quản trị và hậu kiểm
        DanhGiaMonHoc::create([
            'ma_mon_hoc'          => $monHoc->ma_mon_hoc ?? $monHoc->getKey(),
            'ma_nguoi_dung'       => $userId,
            'hoc_ky'              => $request->input('hoc_ky'),
            'diem_do_kho'         => (int) $request->input('diem_do_kho'),
            'diem_thuc_tien'      => (int) $request->input('diem_thuc_tien'),
            'diem_khoi_luong'     => $workloadScore,
            'noi_dung'            => $noiDung,
            'an_danh'             => $isAnonymous,
            'trang_thai'          => 'DA_DUYET',
        ]);

        return back()->with('success', 'Đánh giá môn học của bạn đã được đăng tải thành công.');
    }

    // Chỉnh sửa đánh giá của chính mình (PUT /danh-gia/{ma_danh_gia}).
    public function update(Request $request, int $ma_danh_gia): RedirectResponse
    {
        $danhGia = DanhGiaMonHoc::findOrFail($ma_danh_gia);
        $user = $request->user();
        $userId = $user->ma_nguoi_dung ?? $user->getKey();

        // Phân quyền: Chỉ chính tác giả mới được quyền sửa
        if ($danhGia->ma_nguoi_dung !== $userId && $danhGia->ma_nguoi_danh_gia !== $userId) {
            abort(403, 'Bạn không có quyền chỉnh sửa bài đánh giá này.');
        }

        $validated = $request->validate([
            'diem_do_kho'         => ['required', 'integer', 'min:1', 'max:5'],
            'diem_thuc_tien'      => ['required', 'integer', 'min:1', 'max:5'],
            'diem_khoi_luong'     => ['nullable', 'integer', 'min:1', 'max:5'],
            'diem_ap_luc_bai_tap' => ['nullable', 'integer', 'min:1', 'max:5'],
            'noi_dung'            => ['nullable', 'string', 'min:10', 'max:2000'],
            'nhan_xet'            => ['nullable', 'string', 'min:10', 'max:2000'],
            'an_danh'             => ['nullable', 'boolean'],
            'la_an_danh'          => ['nullable', 'boolean'],
        ], [
            'nhan_xet.required'   => 'Nội dung nhận xét không được để trống.',
            'nhan_xet.min'        => 'Nội dung nhận xét cần tối thiểu 10 ký tự.',
            'noi_dung.required'   => 'Nội dung nhận xét không được để trống.',
            'noi_dung.min'        => 'Nội dung nhận xét cần tối thiểu 10 ký tự.',
        ]);

        $noiDung = trim((string) ($validated['noi_dung'] ?? $validated['nhan_xet'] ?? $danhGia->noi_dung));
        if (mb_strlen($noiDung) < 10) {
            return back()->withErrors([
                'nhan_xet' => 'Nội dung nhận xét cần tối thiểu 10 ký tự.',
                'noi_dung' => 'Nội dung nhận xét cần tối thiểu 10 ký tự.',
            ]);
        }

        // Kiểm tra từ cấm cho nội dung chỉnh sửa
        $violations = $this->profanityFilter->layDanhSachViPham($noiDung);
        if (!empty($violations)) {
            $badWords = implode(', ', array_map(fn($w) => "\"{$w}\"", $violations));
            return back()->withErrors([
                'nhan_xet' => "Nội dung nhận xét chứa từ ngữ không phù hợp chuẩn mực: {$badWords}.",
                'noi_dung' => "Nội dung nhận xét chứa từ ngữ không phù hợp chuẩn mực: {$badWords}."
            ]);
        }

        $workloadScore = $validated['diem_khoi_luong'] ?? $validated['diem_ap_luc_bai_tap'] ?? $danhGia->diem_khoi_luong;
        $isAnonymous = $validated['an_danh'] ?? $validated['la_an_danh'] ?? $danhGia->an_danh;

        $danhGia->update([
            'diem_do_kho'     => $validated['diem_do_kho'],
            'diem_thuc_tien'  => $validated['diem_thuc_tien'],
            'diem_khoi_luong' => $workloadScore,
            'noi_dung'        => $noiDung,
            'an_danh'         => $isAnonymous,
        ]);

        return back()->with('success', 'Đã cập nhật bài đánh giá thành công.');
    }

    // Xóa mềm bài đánh giá (DELETE /danh-gia/{ma_danh_gia}).
    public function destroy(Request $request, int $ma_danh_gia): RedirectResponse
    {
        $danhGia = DanhGiaMonHoc::findOrFail($ma_danh_gia);
        $user = $request->user();
        $userId = $user->ma_nguoi_dung ?? $user->getKey();

        // Quyền xóa: Tác giả bài viết, Quản trị viên hoặc Kiểm duyệt viên
        $isAuthor = ($danhGia->ma_nguoi_dung === $userId || $danhGia->ma_nguoi_danh_gia === $userId);
        $isAdminOrMod = (method_exists($user, 'laQuanTriVien') && $user->laQuanTriVien())
            || (method_exists($user, 'laKiemDuyetVien') && $user->laKiemDuyetVien())
            || in_array($user->vai_tro, [VaiTroNguoiDung::QUAN_TRI_VIEN, VaiTroNguoiDung::KIEM_DUYET_VIEN]);

        if (!$isAuthor && !$isAdminOrMod) {
            abort(403, 'Bạn không có thẩm quyền xóa bài đánh giá này.');
        }

        $danhGia->delete(); // SoftDeletes tự động cập nhật ngay_xoa_tam

        return back()->with('success', 'Đã xóa bài đánh giá thành công.');
    }

    // Tương tác bình chọn hữu ích (POST /danh-gia/{ma_danh_gia}/huu-ich).
    public function binhChonHuuIch(Request $request, int $ma_danh_gia): RedirectResponse
    {
        $danhGia = DanhGiaMonHoc::findOrFail($ma_danh_gia);
        $user = $request->user();
        $userId = $user->ma_nguoi_dung ?? $user->getKey();

        // Không cho phép tự vote cho bài của chính mình
        if ($danhGia->ma_nguoi_dung === $userId || $danhGia->ma_nguoi_danh_gia === $userId) {
            return back()->with('warning', 'Bạn không thể tự bình chọn đánh giá của chính mình.');
        }

        $daVote = \App\Models\BinhChonDanhGia::where('ma_danh_gia', $danhGia->ma_danh_gia)
            ->where('ma_nguoi_dung', $userId)
            ->first();

        if ($daVote) {
            $daVote->delete();
            return back()->with('info', 'Đã bỏ bình chọn hữu ích.');
        }

        \App\Models\BinhChonDanhGia::create([
            'ma_danh_gia'   => $danhGia->ma_danh_gia,
            'ma_nguoi_dung' => $userId,
            'huu_ich'       => true,
        ]);

        return back()->with('success', 'Đã ghi nhận đánh giá hữu ích.');
    }

    // Báo cáo đánh giá vi phạm chuẩn mực (POST /danh-gia/{ma_danh_gia}/bao-cao).
    public function baoCaoViPham(Request $request, int $ma_danh_gia): RedirectResponse
    {
        $request->validate([
            'ly_do'   => ['required', 'string'],
            'chi_tiet'=> ['nullable', 'string', 'max:500'],
        ], [
            'ly_do.required' => 'Vui lòng cung cấp lý do báo cáo vi phạm.',
        ]);

        $danhGia = DanhGiaMonHoc::findOrFail($ma_danh_gia);
        $user = $request->user();
        $reporterId = $user->ma_nguoi_dung ?? $user->getKey();

        $lyDo = in_array($request->input('ly_do'), ['NGON_TU_KICH_DONG', 'BOI_NHO', 'QUAY_ROI', 'SPAM', 'KHAC'])
            ? $request->input('ly_do')
            : 'KHAC';

        \App\Models\BaoCaoDanhGia::updateOrCreate(
            [
                'ma_danh_gia'      => $danhGia->ma_danh_gia,
                'ma_nguoi_bao_cao' => $reporterId,
            ],
            [
                'ly_do'            => $lyDo,
                'chi_tiet'         => $request->input('chi_tiet') ?? $request->input('ly_do'),
                'trang_thai'       => 'CHO_XU_LY',
            ]
        );

        return back()->with('success', 'Báo cáo vi phạm đã được gửi tới Ban Kiểm duyệt.');
    }


}