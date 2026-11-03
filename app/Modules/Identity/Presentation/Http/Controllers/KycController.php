<?php

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Enums\TrangThaiXacThuc;
use App\Http\Controllers\Controller;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Identity\Application\Actions\ApproveKycAction;
use App\Modules\Identity\Application\Actions\RejectKycAction;
use App\Modules\Identity\Application\Actions\SubmitAlumniKycAction;
use App\Modules\Identity\Application\Queries\GetAlumniRegistrationFormQuery;
use App\Modules\Identity\Application\Queries\GetKycQueueQuery;
use App\Modules\Identity\Domain\Services\AlumniKycRuleEvaluator;
use App\Modules\Identity\Infrastructure\Persistence\KycRepository;
use App\Modules\Identity\Infrastructure\Storage\PrivateKycStorage;
use App\Modules\Identity\Presentation\Http\Requests\AlumniKycRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class KycController extends Controller
{
    public function __construct(
        protected AlumniKycRuleEvaluator $ruleEvaluator
    ) {}

    // Màn hình nộp hồ sơ đăng ký tài khoản cho Cựu sinh viên
    public function hienThiFormDangKy(GetAlumniRegistrationFormQuery $query): InertiaResponse
    {
        return Inertia::render('Auth/AlumniRegister', [
            'danh_sach_khoa' => $query->execute(),
        ]);
    }

    // Lưu thông tin đăng ký cựu sinh viên và lưu ảnh vào private storage
    public function luuDangKyCuuSinhVien(AlumniKycRequest $request, SubmitAlumniKycAction $action): RedirectResponse
    {
        $action->execute(
            $request->validated(),
            $request->file('anh_minh_chung')
        );

        return redirect()->route('login')->with(
            'status',
            'Đã gửi hồ sơ đăng ký cựu sinh viên thành công! Quản trị viên sẽ đối soát và kích hoạt tài khoản của bạn.'
        );
    }

    // Hàng đợi duyệt KYC cho Admin/Moderator (Cấp Signed URL 30 phút)
    public function hangDoiKyc(Request $request, GetKycQueueQuery $query): InertiaResponse
    {
        if (!$this->ruleEvaluator->canReviewKyc($request->user())) {
            abort(403, 'Bạn không có thẩm quyền truy cập hàng đợi duyệt KYC.');
        }

        return Inertia::render('Admin/Kyc/Index', [
            'kycQueue' => $query->execute(15),
        ]);
    }

    // Stream file nhị phân khi Signed URL hợp lệ (chỉ Admin/Mod xem)
    public function xemTruocMinhChung(
        Request $request,
        int $ma_xac_thuc,
        KycRepository $repository,
        PrivateKycStorage $storage
    ): Response {
        if (!$this->ruleEvaluator->canReviewKyc($request->user())) {
            abort(403, 'Bạn không có thẩm quyền truy cập tệp minh chứng cá nhân này.');
        }

        $kyc = $repository->findAlumniKycById($ma_xac_thuc);

        if (!$storage->exists($kyc->duong_dan_minh_chung)) {
            abort(404, 'Tệp minh chứng không tồn tại trên hệ thống lưu trữ.');
        }

        app(AuditLogger::class)->log(
            actor: $request->user(),
            action: HanhDongKiemToan::XEM_HO_SO_KYC,
            targetType: LoaiDoiTuongKiemToan::YEU_CAU_XAC_THUC,
            targetId: (string) $ma_xac_thuc,
            description: "Xem hồ sơ minh chứng KYC cựu sinh viên của {$kyc->nguoiDung?->ho_ten} (Mã KYC: #{$ma_xac_thuc}).",
            oldData: null,
            newData: ['ma_nguoi_dung' => $kyc->ma_nguoi_dung, 'duong_dan' => $kyc->duong_dan_minh_chung]
        );

        return $storage->response($kyc->duong_dan_minh_chung, [
            'Content-Type'        => $kyc->loai_tep ?? 'image/jpeg',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, private, max-age=0',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
            'Content-Disposition' => 'inline; filename="kyc_minh_chung_' . $kyc->ma_yeu_cau_xac_thuc . '"',
        ]);
    }

    // Phê duyệt hồ sơ: Chuyển tài khoản sang ACTIVE và khởi tạo ví điểm
    public function pheDuyetHoSo(
        Request $request,
        int $ma_xac_thuc,
        ApproveKycAction $action,
        KycRepository $repository
    ): RedirectResponse {
        if (!$this->ruleEvaluator->canReviewKyc($request->user())) {
            abort(403, 'Bạn không có thẩm quyền thực hiện thao tác duyệt hồ sơ KYC.');
        }

        $kyc = $repository->findAlumniKycById($ma_xac_thuc);

        if ($kyc->trang_thai !== TrangThaiXacThuc::CHO_DUYET) {
            return back()->with('error', 'Hồ sơ này đã được xử lý trước đó.');
        }

        $action->execute($ma_xac_thuc, $request->user());

        return back()->with('success', "Đã phê duyệt tài khoản cựu sinh viên {$kyc->nguoiDung->ho_ten} thành công.");
    }

    // Từ chối hồ sơ kèm lý do
    public function tuChoiHoSo(
        Request $request,
        int $ma_xac_thuc,
        RejectKycAction $action,
        KycRepository $repository
    ): RedirectResponse {
        if (!$this->ruleEvaluator->canReviewKyc($request->user())) {
            abort(403, 'Bạn không có thẩm quyền thực hiện thao tác duyệt hồ sơ KYC.');
        }

        $request->validate([
            'ly_do_tu_choi' => ['required', 'string', 'min:6', 'max:500'],
        ], [
            'ly_do_tu_choi.required' => 'Bắt buộc phải nhập lý do từ chối hồ sơ.',
            'ly_do_tu_choi.min'      => 'Lý do từ chối tối thiểu 6 ký tự.',
        ]);

        $kyc = $repository->findAlumniKycById($ma_xac_thuc);

        if ($kyc->trang_thai !== TrangThaiXacThuc::CHO_DUYET) {
            return back()->with('error', 'Hồ sơ này đã được xử lý trước đó.');
        }

        $action->execute($ma_xac_thuc, $request->input('ly_do_tu_choi'), $request->user());

        return back()->with('warning', "Đã từ chối hồ sơ của cựu sinh viên {$kyc->nguoiDung->ho_ten}.");
    }
}
