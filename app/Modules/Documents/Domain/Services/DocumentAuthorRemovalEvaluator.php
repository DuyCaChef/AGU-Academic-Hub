<?php

namespace App\Modules\Documents\Domain\Services;

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

    // Đánh giá điều kiện gửi đơn xin gỡ tài liệu thuộc sở hữu cộng đồng (lượt tải >= ngưỡng)
    public function canRequestRemoval(TaiLieu $document, NguoiDung $user): array
    {
        if ($document->ma_nguoi_dang !== $user->getKey()) {
            return ['allowed' => false, 'status' => 403, 'message' => 'Chỉ tác giả của tài liệu mới được gửi yêu cầu gỡ.'];
        }

        $threshold = config('documents.community_ownership_download_threshold', 10);
        if ($document->luot_tai < $threshold) {
            return [
                'allowed' => false,
                'status' => 422,
                'message' => "Tài liệu chỉ thuộc quyền sở hữu cộng đồng từ {$threshold} lượt tải.",
            ];
        }

        return ['allowed' => true, 'status' => 200, 'message' => 'Hợp lệ'];
    }
}

