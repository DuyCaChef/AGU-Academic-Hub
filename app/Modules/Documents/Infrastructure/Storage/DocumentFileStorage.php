<?php

namespace App\Modules\Documents\Infrastructure\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DocumentFileStorage
{
    // Thư mục lưu trữ tài liệu nội bộ
    public const DOCUMENTS_DIR = 'documents';

    // Lưu tệp tải lên với tên an toàn
    public function store(UploadedFile $file, ?string $prefix = null): string
    {
        $extension = $file->getClientOriginalExtension();
        $safeName = sprintf(
            '%s%s_%s.%s',
            $prefix ? "{$prefix}_" : '',
            now()->format('YmdHis'),
            Str::random(12),
            $extension
        );

        return $file->storeAs(self::DOCUMENTS_DIR, $safeName, 'local');
    }

    // Kiểm tra tệp có tồn tại trên đĩa cứng không
    public function exists(string $path): bool
    {
        return Storage::disk('local')->exists($path);
    }

    // Lấy đường dẫn vật lý thực tế của tệp
    public function fullPath(string $path): string
    {
        return Storage::disk('local')->path($path);
    }

    // Xóa tệp khỏi đĩa cứng
    public function delete(string $path): bool
    {
        return Storage::disk('local')->delete($path);
    }

    // Tạo response xem trước nội dung PDF trực tuyến (Protected Inline Stream)
    public function previewResponse(string $path, string $displayName): Response
    {
        return Storage::disk('local')->response($path, $displayName, [
            'Content-Disposition' => 'inline; filename="' . $displayName . '"',
        ]);
    }

    // Tạo response tải tệp về máy
    public function downloadResponse(string $path, string $displayName): Response
    {
        return Storage::disk('local')->download($path, $displayName);
    }
}
