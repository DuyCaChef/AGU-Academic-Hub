<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cấu hình giám sát và quản lý dung lượng lưu trữ (Storage Management)
    |--------------------------------------------------------------------------
    |
    | Thiết lập hạn mức, ngưỡng cảnh báo, và định mức file lớn bất thường
    | phục vụ vận hành hệ thống AGU Academic Hub.
    |
    */

    // Hạn mức lưu trữ tổng thể được cấp phát (tính bằng MB). Mặc định: 10240 MB = 10 GB
    'quota_mb' => (int) env('STORAGE_QUOTA_MB', 10240),

    // Ngưỡng phần trăm cảnh báo vàng (Warning threshold). Mặc định: 80%
    'warning_threshold_percent' => (int) env('STORAGE_WARNING_THRESHOLD_PERCENT', 80),

    // Ngưỡng phần trăm cảnh báo đỏ nguy cấp (Critical threshold). Mặc định: 90%
    'critical_threshold_percent' => (int) env('STORAGE_CRITICAL_THRESHOLD_PERCENT', 90),

    // Ngưỡng xác định file có dung lượng lớn bất thường (tính bằng KB). Mặc định: 20480 KB = 20 MB
    'large_file_threshold_kb' => (int) env('STORAGE_LARGE_FILE_THRESHOLD_KB', 20480),

    // Disk lưu trữ tài liệu chung
    'document_disk' => env('DOCUMENT_DISK', 'local'),

    // Disk và thư mục lưu trữ private minh chứng Alumni KYC
    'kyc_disk' => env('KYC_DISK', 'local'),
    'kyc_path' => env('KYC_STORAGE_DIR', 'private/kyc'),
];

