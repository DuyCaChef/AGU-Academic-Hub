<?php

namespace App\Modules\Documents\Application\Queries;

use App\Modules\Documents\Domain\Services\DocumentDeduplicationEvaluator;

class CheckDuplicateDocumentQuery
{
    public function __construct(
        protected DocumentDeduplicationEvaluator $evaluator
    ) {}

    // Kiểm tra trùng lặp mã băm trước khi tải lên (Pre-upload Check API)
    public function execute(int $courseId, string $sha256Hash): array
    {
        $duplicate = $this->evaluator->findExactDuplicateBySha256($courseId, $sha256Hash);

        if ($duplicate) {
            return [
                'is_duplicate'   => true,
                'duplicate_type' => 'EXACT_SHA256',
                'message'        => 'Tài liệu với nội dung nhị phân này đã tồn tại trong môn học.',
                'document'       => [
                    'ma_tai_lieu' => $duplicate->ma_tai_lieu,
                    'tieu_de'     => $duplicate->tieu_de,
                    'luot_tai'    => $duplicate->luot_tai,
                ],
            ];
        }

        return [
            'is_duplicate'   => false,
            'duplicate_type' => 'NONE',
            'message'        => 'Tài liệu hợp lệ, không phát hiện trùng lặp.',
        ];
    }
}

