<?php

namespace App\Modules\Documents\Application\Actions;

use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Documents\Domain\Services\DocumentDeduplicationEvaluator;
use App\Modules\Documents\Infrastructure\Storage\DocumentFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UploadDocumentAction
{
    public function __construct(
        protected DocumentDeduplicationEvaluator $dedupEvaluator,
        protected DocumentFileStorage $storage
    ) {}

    // Tiếp nhận và xử lý tải lên tài liệu mới đi qua Pipeline lọc trùng SHA-256
    public function execute(array $data, UploadedFile $file, NguoiDung $uploader): TaiLieu
    {
        // 1. Tính toán mã băm SHA-256 nhị phân của tệp tải lên
        $sha256Hash = hash_file('sha256', $file->getRealPath());

        // 2. Chặn trùng lặp 100% trong cùng môn học
        $courseId = (int) $data['ma_mon_hoc'];
        $existing = $this->dedupEvaluator->findExactDuplicateBySha256($courseId, $sha256Hash);

        if ($existing) {
            throw new ConflictHttpException('Tài liệu có nội dung giống 100% đã tồn tại trong môn học này.');
        }

        return DB::transaction(function () use ($data, $file, $uploader, $sha256Hash) {
            // 3. Lưu trữ tệp vật lý
            $storedPath = $this->storage->store($file, 'doc');
            $extension = strtolower($file->getClientOriginalExtension());
            $sizeKb = (int) ceil($file->getSize() / 1024);

            // 4. Tạo bản ghi tài liệu mới
            return TaiLieu::create([
                'ma_mon_hoc'    => $data['ma_mon_hoc'],
                'ma_nguoi_dang' => $uploader->getKey(),
                'tieu_de'       => $data['tieu_de'],
                'mo_ta'         => $data['mo_ta'] ?? null,
                'duong_dan_tep' => $storedPath,
                'dinh_dang_tep' => $extension,
                'dung_luong_kb' => $sizeKb,
                'ma_bam_file'   => $sha256Hash,
                'phan_loai'     => $data['phan_loai'] ?? 'KHAC',
                'phien_ban'     => '1.0',
                'luot_tai'      => 0,
                'trang_thai'    => 'DA_DUYET',
            ]);
        });
    }
}

