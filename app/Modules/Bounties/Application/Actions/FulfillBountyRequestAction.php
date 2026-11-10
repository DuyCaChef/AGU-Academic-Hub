<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiTaiLieu;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauTaiLieu;
use App\Modules\Bounties\Domain\Services\BountyRuleEvaluator;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;
use App\Modules\Documents\Domain\Services\DocumentDeduplicationEvaluator;
use App\Modules\Documents\Infrastructure\Storage\DocumentFileStorage;
use App\Modules\Notifications\Domain\Events\DocumentContributedToBountyEvent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

// Action tiếp nhận tài liệu đóng góp phản hồi yêu cầu (Hỗ trợ cả Chọn tài liệu có sẵn và Upload file mới)
class FulfillBountyRequestAction
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected BountyRuleEvaluator $ruleEvaluator,
        protected DocumentDeduplicationEvaluator $dedupEvaluator,
        protected DocumentFileStorage $storage
    ) {}

    public function execute(NguoiDung $user, int $maYeuCau, array $validated, ?UploadedFile $file = null): array
    {
        $yeuCau = $this->bountyRepository->findRequestById($maYeuCau);

        // 1. Kiểm tra điều kiện chung: Yêu cầu còn mở và không được tự nộp cho chính mình
        $check = $this->ruleEvaluator->canFulfill($user, $yeuCau);
        if (!$check['allowed']) {
            return [
                'success' => false,
                'message' => $check['message'],
            ];
        }

        // PHƯƠNG THỨC B: Tải lên tài liệu từ máy local
        if ($file !== null) {
            return $this->handleLocalUpload($user, $yeuCau, $validated, $file);
        }

        // PHƯƠNG THỨC A: Chọn tài liệu đã có trong kho cá nhân
        return $this->handleExistingDocument($user, $yeuCau, $validated);
    }

    // Xử lý nộp tài liệu có sẵn từ kho cá nhân (Method A)
    protected function handleExistingDocument(NguoiDung $user, YeuCauTaiLieu $yeuCau, array $validated): array
    {
        if (empty($validated['ma_tai_lieu'])) {
            return [
                'success' => false,
                'message' => 'Vui lòng chọn hoặc cung cấp mã ID tài liệu hợp lệ.',
            ];
        }

        $documentId = (int) $validated['ma_tai_lieu'];

        // Kiểm tra xem đã gửi tài liệu này cho yêu cầu chưa
        if ($this->bountyRepository->submissionExists($yeuCau->ma_yeu_cau, $documentId)) {
            return [
                'success' => false,
                'message' => 'Tài liệu này đã được gửi phản hồi trước đó.',
            ];
        }

        // Kiểm tra tài liệu tồn tại (bao gồm soft delete)
        $doc = TaiLieu::withTrashed()->find($documentId);
        $checkDoc = $this->ruleEvaluator->canFulfillWithDocument($user, $yeuCau, $doc);
        if (!$checkDoc['allowed']) {
            return [
                'success' => false,
                'message' => $checkDoc['message'],
            ];
        }

        $submission = $this->bountyRepository->createSubmission([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $documentId,
            'ma_nguoi_dong_gop' => $user->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
            'ghi_chu_dong_gop'  => $validated['ghi_chu_dong_gop'] ?? null,
        ]);

        DocumentContributedToBountyEvent::dispatch($submission, $yeuCau, $user);

        return [
            'success' => true,
            'message' => 'Đã gửi tài liệu phản hồi. Chờ người yêu cầu duyệt và giải ngân điểm!',
        ];
    }

    // Xử lý upload tài liệu mới từ máy local (Method B)
    protected function handleLocalUpload(NguoiDung $user, YeuCauTaiLieu $yeuCau, array $validated, UploadedFile $file): array
    {
        // 1. Kiểm tra giới hạn tải lên: Tối đa 10 lượt upload / ngày theo chuẩn SRS
        $todayUploads = TaiLieu::withTrashed()
            ->where('ma_nguoi_dang', $user->ma_nguoi_dung)
            ->whereDate('created_at', today())
            ->count();

        if ($todayUploads >= 10) {
            return [
                'success' => false,
                'message' => 'Bạn đã đạt giới hạn tối đa 10 lượt tải lên tài liệu trong ngày.',
            ];
        }

        // 2. Kiểm tra tính toàn vẹn file
        if ($file->getSize() <= 0) {
            return [
                'success' => false,
                'message' => 'Tệp tải lên bị rỗng (0 bytes). Vui lòng chọn tệp hợp lệ.',
            ];
        }

        // 3. Kiểm tra MIME thực tế và định dạng hợp lệ
        $allowedExtensions = ['pdf', 'docx', 'pptx', 'zip'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions, true)) {
            return [
                'success' => false,
                'message' => 'Định dạng tệp không được hỗ trợ. Chỉ chấp nhận PDF, DOCX, PPTX hoặc ZIP.',
            ];
        }

        // 4. Tính toán mã băm SHA-256 nhị phân của tệp tải lên
        $sha256Hash = hash_file('sha256', $file->getRealPath());

        // 5. Chặn trùng lặp nhị phân 100% trong cùng môn học
        $existing = $this->dedupEvaluator->findExactDuplicateBySha256($yeuCau->ma_mon_hoc, $sha256Hash);
        if ($existing) {
            return [
                'success' => false,
                'message' => 'Tài liệu có nội dung giống 100% đã tồn tại trong môn học này.',
            ];
        }

        // 6. Thực hiện lưu trữ file và tạo bản ghi trong Transaction
        $submission = DB::transaction(function () use ($user, $yeuCau, $validated, $file, $sha256Hash, $extension) {
            $storedPath = $this->storage->store($file, 'doc');
            $sizeKb = (int) ceil($file->getSize() / 1024);

            // Tạo tài liệu mới ở trạng thái CHO_DUYET để đưa vào quy trình kiểm duyệt
            $newDoc = TaiLieu::create([
                'ma_mon_hoc'    => $yeuCau->ma_mon_hoc,
                'ma_nguoi_dang' => $user->ma_nguoi_dung,
                'tieu_de'       => trim($validated['tieu_de']),
                'duong_dan_tep' => $storedPath,
                'dinh_dang_tep' => $extension,
                'dung_luong_kb' => $sizeKb,
                'ma_bam_file'   => $sha256Hash,
                'phan_loai'     => $validated['phan_loai'] ?? $yeuCau->phan_loai ?? 'KHAC',
                'phien_ban'     => '1.0',
                'luot_tai'      => 0,
                'luot_thich'    => 0,
                'trang_thai'    => 'CHO_DUYET', // Đưa vào hàng đợi kiểm duyệt!
            ]);

            // Tạo bản ghi đóng góp liên kết tài liệu với yêu cầu Bounty
            return $this->bountyRepository->createSubmission([
                'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
                'ma_tai_lieu'       => $newDoc->ma_tai_lieu,
                'ma_nguoi_dong_gop' => $user->ma_nguoi_dung,
                'trang_thai'        => 'CHO_DUYET',
                'ghi_chu_dong_gop'  => $validated['ghi_chu_dong_gop'] ?? null,
            ]);
        });

        DocumentContributedToBountyEvent::dispatch($submission, $yeuCau, $user);

        return [
            'success' => true,
            'message' => 'Đã tải lên và gửi tài liệu đóng góp thành công. Tài liệu đang chờ Ban kiểm duyệt phê duyệt trước khi người yêu cầu có thể nghiệm thu giải ngân.',
        ];
    }
}
