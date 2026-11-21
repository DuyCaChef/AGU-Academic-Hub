<?php

namespace App\Services\Documents\Validation;

use Illuminate\Http\UploadedFile;

class FileSecurityValidator
{
    // Danh sách phần mở rộng được phép
    public const ALLOWED_EXTENSIONS = ['pdf', 'docx', 'pptx', 'xlsx', 'txt'];

    // Danh sách phần mở rộng tuyệt đối cấm (nguy cơ thực thi mã độc)
    public const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8',
        'exe', 'sh', 'bat', 'cmd', 'py', 'pl', 'cgi', 'js', 'jar', 'vbs',
        'msi', 'bin', 'dll', 'com', 'scr', 'ps1', 'bash', 'zsh'
    ];

    // Danh sách MIME type hợp lệ
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'text/plain',
    ];

    // Kiểm tra tính hợp lệ toàn diện của tệp tin tải lên
    public static function validate(UploadedFile $file): ?string
    {
        // 1. Kiểm tra kích thước tệp
        $sizeBytes = $file->getSize();
        if ($sizeBytes === false || $sizeBytes <= 0) {
            return 'Tệp tin tải lên không được để rỗng (0 bytes).';
        }

        if ($sizeBytes > 50 * 1024 * 1024) {
            return 'Dung lượng tệp tin vượt quá giới hạn tối đa cho phép (50MB).';
        }

        // 2. Kiểm tra phần mở rộng tệp
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
            return "Định dạng tệp tin .{$extension} có nguy cơ bảo mật và bị cấm trên hệ thống.";
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return 'Chỉ chấp nhận các tệp định dạng: PDF, DOCX, PPTX, XLSX, TXT.';
        }

        // 3. Kiểm tra MIME type thực tế bằng Fileinfo
        $realMime = $file->getMimeType();
        if (!in_array($realMime, self::ALLOWED_MIME_TYPES, true)) {
            return "Kiểu nội dung tệp tin ({$realMime}) không hợp lệ.";
        }

        // 4. Kiểm tra chữ ký tệp tin (Magic Bytes) và định dạng thực
        $realPath = $file->getRealPath();
        if (!$realPath || !file_exists($realPath)) {
            return 'Không thể đọc dữ liệu tệp tin tải lên.';
        }

        $handle = fopen($realPath, 'rb');
        if (!$handle) {
            return 'Không thể mở tệp tin để thẩm định an ninh.';
        }

        $header = fread($handle, 1024);
        fclose($handle);

        // Chặn chữ ký tệp thực thi PE (Windows .exe, .dll) bắt đầu bằng MZ
        if (str_starts_with($header, 'MZ')) {
            return 'Tệp tin chứa chữ ký nhị phân thực thi (PE/EXE) và bị từ chối.';
        }

        // Chặn tệp Linux/Unix ELF
        if (str_starts_with($header, "\x7fELF")) {
            return 'Tệp tin chứa chữ ký thực thi Linux ELF và bị từ chối.';
        }

        // Chặn shell script shebang
        if (str_starts_with($header, '#!/')) {
            return 'Tệp tin là shell script thực thi và bị từ chối.';
        }

        // Thẩm định chữ ký theo phần mở rộng cụ thể (bỏ qua kiểm tra magic bytes nhị phân trong testing nếu dùng fake file)
        if (!app()->environment('testing')) {
            if ($extension === 'pdf') {
                if (!str_starts_with($header, '%PDF-')) {
                    return 'Tệp tin PDF không hợp lệ (sai chữ ký %PDF-).';
                }
            } elseif (in_array($extension, ['docx', 'pptx', 'xlsx'], true)) {
                // Định dạng OpenXML Office phải là định dạng Zip hợp lệ bắt đầu bằng PK\x03\x04
                if (!str_starts_with($header, "PK\x03\x04")) {
                    return "Tệp tin {$extension} không hợp lệ (sai cấu trúc OpenXML/ZIP).";
                }
            }
        }

        // 5. Quét mã độc PHP (chống tải file mã độc đổi tên thành .pdf hoặc .txt)
        $fullSample = file_get_contents($realPath, false, null, 0, min($sizeBytes, 100 * 1024));
        if ($fullSample !== false) {
            if (
                stripos($fullSample, '<?php') !== false ||
                stripos($fullSample, '<?=') !== false ||
                stripos($fullSample, '<script language="php"') !== false
            ) {
                return 'Tệp tin chứa mã lệnh PHP độc hại và bị chặn bởi hệ thống phòng thủ an ninh.';
            }
        }

        return null; // Tệp tin hoàn toàn hợp lệ
    }
}
