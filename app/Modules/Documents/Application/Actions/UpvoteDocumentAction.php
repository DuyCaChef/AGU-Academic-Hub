<?php

namespace App\Modules\Documents\Application\Actions;

use App\Models\BinhChonTaiLieu;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Documents\Infrastructure\Persistence\DocumentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpvoteDocumentAction
{
    public function __construct(
        protected DocumentRepository $repository
    ) {}

    /**
     * Bình chọn / Thích tài liệu (Chặn tác giả tự like và chống spam like đa lần)
     *
     * @return array{success: bool, action: string, has_upvoted: bool, upvotes: int, message: string}
     * @throws ValidationException
     */
    public function execute(int $documentId, NguoiDung $user): array
    {
        $document = $this->repository->findById($documentId);
        $userId = (int) ($user->ma_nguoi_dung ?? $user->getKey());

        // 1. Ràng buộc: Tác giả không được tự like tài liệu của chính mình
        if ((int) $document->ma_nguoi_dang === $userId) {
            throw ValidationException::withMessages([
                'message' => 'Bạn không thể tự thích tài liệu của chính mình.',
            ]);
        }

        // 2. Xử lý Toggle Like bằng Transaction an toàn & chống Race Condition / Spam
        return DB::transaction(function () use ($document, $userId) {
            $existing = BinhChonTaiLieu::where('ma_tai_lieu', $document->ma_tai_lieu)
                ->where('ma_nguoi_dung', $userId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                // Đã thích trước đó -> Bỏ thích (Unlike)
                $existing->delete();
                $document->decrement('luot_thich');
                $document->refresh();

                return [
                    'success'     => true,
                    'action'      => 'unliked',
                    'has_upvoted' => false,
                    'upvotes'     => max(0, (int) $document->luot_thich),
                    'message'     => 'Đã bỏ thích tài liệu.',
                ];
            }

            // Chưa thích -> Ghi nhận thích mới
            BinhChonTaiLieu::create([
                'ma_tai_lieu'   => $document->ma_tai_lieu,
                'ma_nguoi_dung' => $userId,
            ]);

            $document->increment('luot_thich');
            $document->refresh();

            return [
                'success'     => true,
                'action'      => 'liked',
                'has_upvoted' => true,
                'upvotes'     => (int) $document->luot_thich,
                'message'     => 'Đã thích tài liệu thành công.',
            ];
        });
    }
}
