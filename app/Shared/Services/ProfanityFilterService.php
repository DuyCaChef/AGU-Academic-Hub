<?php

namespace App\Shared\Services;

use App\Modules\Reviews\Domain\Services\ProfanityFilterEvaluator;

// Service tương thích ngược, ủy quyền xử lý sang Domain Evaluator của Module Reviews
class ProfanityFilterService
{
    public function __construct(
        protected ProfanityFilterEvaluator $evaluator
    ) {}

    // Kiểm tra xem chuỗi văn bản có chứa từ cấm hay không
    public function coTuCam(?string $vanBan): bool
    {
        return $this->evaluator->coTuCam($vanBan);
    }

    // Trích xuất danh sách các từ ngữ vi phạm xuất hiện trong văn bản
    public function layDanhSachViPham(?string $vanBan): array
    {
        return $this->evaluator->layDanhSachViPham($vanBan);
    }

    // Che từ cấm trong chuỗi bằng ký tự thay thế (mặc định: ***)
    public function locNoiDung(?string $vanBan, string $kyTuThayThe = '***'): string
    {
        return $this->evaluator->locNoiDung($vanBan, $kyTuThayThe);
    }

    // Xóa cache từ cấm khi Admin cập nhật danh sách
    public function xoaCache(): void
    {
        $this->evaluator->xoaCache();
    }
}
