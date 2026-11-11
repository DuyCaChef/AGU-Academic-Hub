<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Models\LanNopTaiLieuThayThe;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauChinhSuaDongGop;
use App\Modules\Bounties\Domain\Services\BountyRuleEvaluator;
use App\Modules\Documents\Domain\Services\DocumentDeduplicationEvaluator;
use App\Modules\Documents\Infrastructure\Storage\DocumentFileStorage;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use App\Modules\Notifications\Domain\Enums\NotificationCategory;
use App\Modules\Notifications\Domain\Enums\NotificationType;
use App\Modules\Notifications\Infrastructure\Notifications\SystemNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

// Action cho người đóng góp nộp phiên bản tài liệu thay thế (Phần 6.3)
class SubmitReplacementDocumentAction
{
    public function __construct(
        protected BountyRuleEvaluator $ruleEvaluator,
        protected DocumentFileStorage $storage,
        protected DocumentDeduplicationEvaluator $dedupEvaluator
    ) {}

    public function execute(
        NguoiDung $user,
        int $maYeuCauChinhSua,
        array $validated,
        ?UploadedFile $file = null
    ): array {
        $yccs = YeuCauChinhSuaDongGop::with(['yeuCau', 'nguoiYeuCau', 'dapUng'])->findOrFail($maYeuCauChinhSua);

        $check = $this->ruleEvaluator->canSubmitReplacement($user, $yccs);
        if (!$check['allowed']) {
            return [
                'success' => false,
                'message' => $check['message'],
            ];
        }

        $yeuCau = $yccs->yeuCau;
        $maMonHoc = $yeuCau->ma_mon_hoc;

        // Xử lý nộp file tải lên từ máy tính (Method B)
        if ($file !== null) {
            if ($file->getSize() <= 0) {
                return [
                    'success' => false,
                    'message' => 'Tệp tải lên rỗng (0 bytes). Vui lòng chọn tệp hợp lệ.',
                ];
            }

            $allowedExtensions = ['pdf', 'docx', 'pptx', 'zip'];
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions, true)) {
                return [
                    'success' => false,
                    'message' => 'Định dạng tệp không được hỗ trợ. Chỉ chấp nhận PDF, DOCX, PPTX hoặc ZIP.',
                ];
            }

            $sha256Hash = hash_file('sha256', $file->getRealPath());
            $sizeKb = (int) ceil($file->getSize() / 1024);
            $storedPath = $this->storage->store($file, 'doc');

            $lanNop = DB::transaction(function () use ($user, $yccs, $maMonHoc, $validated, $storedPath, $extension, $sizeKb, $sha256Hash) {
                // Tạo tài liệu mới (không ghi đè file cũ)
                $newDoc = TaiLieu::create([
                    'ma_mon_hoc'    => $maMonHoc,
                    'ma_nguoi_dang' => $user->ma_nguoi_dung,
                    'tieu_de'       => trim($validated['tieu_de'] ?? "Tài liệu thay thế - Yêu cầu #{$yccs->ma_yeu_cau}"),
                    'duong_dan_tep' => $storedPath,
                    'dinh_dang_tep' => $extension,
                    'dung_luong_kb' => $sizeKb,
                    'ma_bam_file'   => $sha256Hash,
                    'phan_loai'     => $validated['phan_loai'] ?? 'KHAC',
                    'phien_ban'     => '2.0',
                    'luot_tai'      => 0,
                    'luot_thich'    => 0,
                    'trang_thai'    => 'DA_DUYET', // Tự động duyệt vì là phiên bản chỉnh sửa trực tiếp giữa A và B
                ]);

                // Tạo bản ghi lần nộp thay thế
                return LanNopTaiLieuThayThe::create([
                    'ma_yeu_cau_chinh_sua'        => $yccs->ma_yeu_cau_chinh_sua,
                    'ma_tai_lieu'                 => $newDoc->ma_tai_lieu,
                    'ma_nguoi_nop'                => $user->ma_nguoi_dung,
                    'ghi_chu'                     => $validated['ghi_chu'] ?? null,
                    'la_phien_ban_duoc_chap_nhan' => false,
                ]);
            });
        } elseif (!empty($validated['ma_tai_lieu'])) {
            // Nộp tài liệu có sẵn từ kho (Method A)
            $documentId = (int) $validated['ma_tai_lieu'];
            $doc = TaiLieu::findOrFail($documentId);

            if ($doc->ma_nguoi_dang !== $user->ma_nguoi_dung) {
                return [
                    'success' => false,
                    'message' => 'Bạn chỉ có thể nộp tài liệu do chính mình đăng tải.',
                ];
            }

            $lanNop = DB::transaction(function () use ($user, $yccs, $documentId, $validated) {
                return LanNopTaiLieuThayThe::create([
                    'ma_yeu_cau_chinh_sua'        => $yccs->ma_yeu_cau_chinh_sua,
                    'ma_tai_lieu'                 => $documentId,
                    'ma_nguoi_nop'                => $user->ma_nguoi_dung,
                    'ghi_chu'                     => $validated['ghi_chu'] ?? null,
                    'la_phien_ban_duoc_chap_nhan' => false,
                ]);
            });
        } else {
            return [
                'success' => false,
                'message' => 'Vui lòng chọn tệp tin tải lên hoặc chọn tài liệu có sẵn.',
            ];
        }

        // Bắn thông báo cho người tạo yêu cầu (A)
        $nguoiYeuCau = $yccs->nguoiYeuCau;
        if ($nguoiYeuCau) {
            $payload = new NotificationPayload(
                type: NotificationType::CORRECTION_REPLACEMENT_SUBMITTED,
                category: NotificationCategory::PERSONAL,
                title: 'Có phiên bản tài liệu thay thế mới',
                message: "Người đóng góp đã gửi phiên bản thay thế mới cho yêu cầu #{$yccs->ma_yeu_cau}. Vui lòng kiểm tra và nghiệm thu.",
                targetType: 'YEU_CAU_TAI_LIEU',
                targetId: (string) $yccs->ma_yeu_cau,
                actionUrl: "/yeu-cau-tai-lieu/{$yccs->ma_yeu_cau}"
            );
            $nguoiYeuCau->notify(new SystemNotification($payload));
        }

        return [
            'success' => true,
            'message' => 'Đã gửi phiên bản thay thế thành công. Đang chờ người tạo yêu cầu nghiệm thu.',
            'lan_nop' => $lanNop,
        ];
    }
}
