<?php

namespace App\Services\Documents;

use App\Models\NguoiDung;
use App\Models\TaiLieu;

class DocumentAuthorRemovalEvaluator
{
    // Đánh giá quyền tự xóa mềm của tác giả (trong 24 giờ đầu và lượt tải = 0)
    public function canAuthorSoftDelete(TaiLieu $document, NguoiDung $user): array
    {
        if ($document->ma_nguoi_dang !== $user->getKey()) {
            return ['allowed' => false, 'status' => 403, 'message' => 'Chỉ tác giả mới được xóa tài liệu.'];
        }

        if ($document->luot_tai > 0) {
            return ['allowed' => false, 'status' => 422, 'message' => 'Tài liệu đã có lượt tải và không thể tự xóa.'];
        }

        if ($document->created_at?->lt(now()->subDay())) {
            return ['allowed' => false, 'status' => 422, 'message' => 'Chỉ có thể tự xóa tài liệu trong 24 giờ đầu.'];
        }

        return ['allowed' => true, 'status' => 200, 'message' => 'Hợp lệ'];
    }

    // Đánh giá điều kiện gửi đơn xin gỡ tài liệu
    // Logic: Cho phép nộp đơn khi tác giả KHÔNG còn quyền tự xóa mềm
    // Quyền tự xóa mềm: luot_tai == 0 VÀ created_at trong vòng 24 giờ đầu
    // => Nếu luot_tai > 0 HOẶC đã quá 24 giờ => không tự xóa được => CHO PHÉP nộp đơn gỡ
    public function canRequestRemoval(TaiLieu $document, NguoiDung $user): array
    {
        // Kiểm tra quyền sở hữu: chỉ tác giả mới được nộp đơn
        if ($document->ma_nguoi_dang !== $user->getKey()) {
            return ['allowed' => false, 'status' => 403, 'message' => 'Chỉ tác giả của tài liệu mới được gửi yêu cầu gỡ.'];
        }

        // Xác định tác giả còn quyền tự xóa mềm không (trong 24 giờ đầu và chưa có lượt tải)
        $canSelfDelete = ($document->luot_tai == 0) && ($document->created_at?->gte(now()->subDay()));

        // Nếu tác giả còn quyền tự xóa mềm thì không cần nộp đơn
        if ($canSelfDelete) {
            return [
                'allowed' => false,
                'status' => 422,
                'message' => 'Bạn vẫn có thể tự xóa tài liệu này trong 24 giờ đầu khi chưa có lượt tải.',
            ];
        }

        // Từ đây: tác giả không còn quyền tự xóa mềm => cho phép nộp đơn gỡ tới Moderator
        // Thêm cảnh báo thông tin nếu tài liệu chưa đạt ngưỡng sở hữu cộng đồng (mang tính tham khảo)
        $threshold = config('documents.community_ownership_download_threshold', 10);
        $advisory = $document->luot_tai < $threshold
            ? " (Lưu ý: tài liệu chưa đạt {$threshold} lượt tải — chưa thuộc sở hữu cộng đồng, nhưng bạn vẫn có thể gửi đơn để Moderator xem xét.)"
            : '';

        return ['allowed' => true, 'status' => 200, 'message' => "Hợp lệ{$advisory}"];
    }
}

