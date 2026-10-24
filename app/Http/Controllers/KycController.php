<?php

namespace App\Http\Controllers;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Http\Requests\KycCuuSinhVienRequest;
use App\Models\Khoa;
use App\Models\NguoiDung;
use App\Models\YeuCauXacThuc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KycController extends Controller
{
    private const PRIVATE_STORAGE_DIR = 'private/kyc';

    /**
     * Màn hình nộp hồ sơ đăng ký tài khoản cho Cựu sinh viên.
     */
    public function hienThiFormDangKy(): Response
    {
        return Inertia::render('Auth/AlumniRegister', [
            'danh_sach_khoa' => Khoa::select('ma_khoa', 'ten_khoa', 'ky_hieu_khoa')->get(),
        ]);
    }

    /**
     * Lưu thông tin đăng ký cựu sinh viên và lưu ảnh vào private storage.
     */
    public function luuDangKyCuuSinhVien(KycCuuSinhVienRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('anh_minh_chung');

        DB::transaction(function () use ($validated, $file) {
            // 1. Tạo tài khoản người dùng ở trạng thái chờ duyệt
            $user = NguoiDung::create([
                'ma_khoa'        => $validated['ma_khoa'] ?? null,
                'ho_ten'         => $validated['ho_ten'],
                'email'          => strtolower($validated['email']),
                'mssv'           => strtoupper($validated['mssv']),
                'nam_tot_nghiep' => $validated['nam_tot_nghiep'],
                'mat_khau'       => Hash::make($validated['mat_khau']),
                'vai_tro'        => VaiTroNguoiDung::CUU_SINH_VIEN,
                'trang_thai'     => TrangThaiNguoiDung::CHUA_XAC_THUC,
                'diem_uy_tin'    => 100,
            ]);

            // 2. Băm tên file an toàn PII — tránh lộ thông tin cá nhân qua tên file
            $extension = $file->getClientOriginalExtension();
            $safeFileName = sprintf(
                'kyc_%d_%s_%s.%s',
                $user->ma_nguoi_dung,
                now()->format('YmdHis'),
                Str::random(16),
                $extension
            );

            // 3. Lưu vào private storage (không thể truy cập trực tiếp qua URL)
            $storedPath = $file->storeAs(self::PRIVATE_STORAGE_DIR, $safeFileName, 'local');

            if (!$storedPath) {
                throw new \RuntimeException('Không thể lưu trữ tệp minh chứng vào private storage.');
            }

            // 4. Lưu bản ghi yêu cầu xác thực
            YeuCauXacThuc::create([
                'ma_nguoi_dung'        => $user->ma_nguoi_dung,
                'loai_xac_thuc'        => LoaiXacThuc::CUU_SINH_VIEN,
                'loai_minh_chung'      => $validated['loai_minh_chung'],
                'mssv'                 => $user->mssv,
                'nam_tot_nghiep'       => $user->nam_tot_nghiep,
                'duong_dan_minh_chung' => $storedPath,
                'loai_tep'             => $file->getClientMimeType(),
                'trang_thai'           => TrangThaiXacThuc::CHO_DUYET,
            ]);
        });

        return redirect()->route('login')->with(
            'status',
            'Đã gửi hồ sơ đăng ký cựu sinh viên thành công! Quản trị viên sẽ đối soát và kích hoạt tài khoản của bạn.'
        );
    }

    /**
     * Hàng đợi duyệt KYC cho Admin/Moderator (Cấp Signed URL 30 phút).
     */
    public function hangDoiKyc(Request $request): Response
    {
        $danhSach = YeuCauXacThuc::with(['nguoiDung:ma_nguoi_dung,ho_ten,email,mssv,nam_tot_nghiep'])
            ->where('trang_thai', TrangThaiXacThuc::CHO_DUYET)
            ->orderBy('ma_yeu_cau_xac_thuc', 'asc')
            ->paginate(15)
            ->through(function ($item) {
                return [
                    'ma_xac_thuc'     => $item->ma_yeu_cau_xac_thuc,
                    'ma_nguoi_dung'   => $item->ma_nguoi_dung,
                    'ho_ten'          => $item->nguoiDung?->ho_ten,
                    'email'           => $item->nguoiDung?->email,
                    'mssv'            => $item->mssv,
                    'nam_tot_nghiep'  => $item->nam_tot_nghiep,
                    'loai_minh_chung' => $item->loai_minh_chung,
                    'ngay_nop'        => $item->created_at->format('d/m/Y H:i'),
                    // Signed URL có thời hạn 30 phút — chỉ Admin/Mod mới truy cập được
                    'url_xem_anh'     => URL::temporarySignedRoute(
                        'kyc.preview',
                        now()->addMinutes(30),
                        ['id' => $item->ma_yeu_cau_xac_thuc]
                    ),
                ];
            });

        return Inertia::render('Admin/Kyc/Index', [
            'kycQueue' => $danhSach,
        ]);
    }

    /**
     * Stream file nhị phân khi Signed URL hợp lệ.
     * Chỉ Admin hoặc Moderator mới được xem — middleware 'signed' kiểm tra chữ ký URL.
     */
    public function xemTruocMinhChung(Request $request, int $id): BinaryFileResponse
    {
        $nguoiXem = $request->user();

        if (!$nguoiXem->laQuanTriVien() && !$nguoiXem->laKiemDuyetVien()) {
            abort(403, 'Bạn không có thẩm quyền truy cập tệp minh chứng cá nhân này.');
        }

        $kyc = YeuCauXacThuc::findOrFail($id);

        if (!Storage::disk('local')->exists($kyc->duong_dan_minh_chung)) {
            abort(404, 'Tệp minh chứng không tồn tại trên hệ thống lưu trữ.');
        }

        $fullPath = Storage::disk('local')->path($kyc->duong_dan_minh_chung);

        return response()->file($fullPath, [
            'Content-Type'        => $kyc->loai_tep ?? mime_content_type($fullPath),
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, private, max-age=0',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
            'Content-Disposition' => 'inline; filename="kyc_minh_chung_' . $kyc->ma_yeu_cau_xac_thuc . '"',
        ]);
    }

    /**
     * Phê duyệt hồ sơ: Chuyển tài khoản sang ACTIVE.
     */
    public function pheDuyetHoSo(Request $request, int $id): RedirectResponse
    {
        $kyc = YeuCauXacThuc::with('nguoiDung')->findOrFail($id);

        if ($kyc->trang_thai !== TrangThaiXacThuc::CHO_DUYET) {
            return back()->with('error', 'Hồ sơ này đã được xử lý trước đó.');
        }

        DB::transaction(function () use ($kyc, $request) {
            $kyc->update([
                'trang_thai'      => TrangThaiXacThuc::DA_DUYET,
                'nguoi_duyet'     => $request->user()->ma_nguoi_dung,
                'thoi_gian_duyet' => now(),
                'ly_do_tu_choi'   => null,
            ]);

            // Kích hoạt tài khoản người dùng
            $kyc->nguoiDung()->update([
                'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
                'vai_tro'    => VaiTroNguoiDung::CUU_SINH_VIEN,
            ]);
        });

        return back()->with('success', "Đã phê duyệt tài khoản cựu sinh viên {$kyc->nguoiDung->ho_ten} thành công.");
    }

    /**
     * Từ chối hồ sơ kèm lý do.
     */
    public function tuChoiHoSo(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'ly_do_tu_choi' => ['required', 'string', 'min:6', 'max:500'],
        ], [
            'ly_do_tu_choi.required' => 'Bắt buộc phải nhập lý do từ chối hồ sơ.',
            'ly_do_tu_choi.min'      => 'Lý do từ chối tối thiểu 6 ký tự.',
        ]);

        $kyc = YeuCauXacThuc::with('nguoiDung')->findOrFail($id);

        if ($kyc->trang_thai !== TrangThaiXacThuc::CHO_DUYET) {
            return back()->with('error', 'Hồ sơ này đã được xử lý trước đó.');
        }

        $kyc->update([
            'trang_thai'      => TrangThaiXacThuc::TU_CHOI,
            'nguoi_duyet'     => $request->user()->ma_nguoi_dung,
            'thoi_gian_duyet' => now(),
            'ly_do_tu_choi'   => $request->input('ly_do_tu_choi'),
        ]);

        return back()->with('warning', "Đã từ chối hồ sơ của cựu sinh viên {$kyc->nguoiDung->ho_ten}.");
    }
}
