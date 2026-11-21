<?php

namespace App\Services\Documents\Actions;

use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Services\Documents\Storage\DocumentFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateDocumentVersionAction
{
    public function __construct(
        protected DocumentFileStorage $storage
    ) {}

    // Tạo phiên bản nâng cấp (v1.1, v2.0...) cho tài liệu gốc đã tồn tại
    public function execute(int $originalDocId, array $data, UploadedFile $file, NguoiDung $uploader): TaiLieu
    {
        $original = TaiLieu::query()->findOrFail($originalDocId);

        // Xác định tài liệu gốc chuẩn xác (tránh chuỗi cha-con đa tầng)
        $rootDoc = $original->ma_tai_lieu_goc ? TaiLieu::query()->find($original->ma_tai_lieu_goc) : $original;
        if (!$rootDoc) {
            $rootDoc = $original;
        }

        $sha256Hash = hash_file('sha256', $file->getRealPath());

        return DB::transaction(function () use ($rootDoc, $original, $data, $file, $uploader, $sha256Hash) {
            $storedPath = $this->storage->store($file, 'ver');
            $extension = strtolower($file->getClientOriginalExtension());
            $sizeKb = (int) ceil($file->getSize() / 1024);

            return TaiLieu::create([
                'ma_mon_hoc'      => $rootDoc->ma_mon_hoc,
                'ma_nguoi_dang'   => $uploader->getKey(),
                'ma_tai_lieu_goc' => $rootDoc->getKey(),
                'tieu_de'         => $data['tieu_de'] ?? $rootDoc->tieu_de,
                'mo_ta'           => $data['mo_ta'] ?? $rootDoc->mo_ta,
                'duong_dan_tep'   => $storedPath,
                'dinh_dang_tep'   => $extension,
                'dung_luong_kb'   => $sizeKb,
                'ma_bam_file'     => $sha256Hash,
                'phan_loai'       => $data['phan_loai'] ?? $rootDoc->phan_loai,
                'phien_ban'       => $data['phien_ban'] ?? '1.1',
                'luot_tai'        => 0,
                'trang_thai'      => 'DA_DUYET',
            ]);
        });
    }
}
