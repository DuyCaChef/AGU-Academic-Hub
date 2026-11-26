# HƯỚNG DẪN VẬN HÀNH HỆ THỐNG THÔNG BÁO (NOTIFICATION OPERATIONS GUIDE)
## DỰ ÁN: AGU ACADEMIC HUB — CENTRALIZED NOTIFICATION MODULE

---

## 1. TỔNG QUAN VẬN HÀNH (OPERATIONAL OVERVIEW)

Hệ thống Thông báo (Notification Module) của AGU Academic Hub hoạt động dựa trên cơ chế **Xử lý sự kiện bất đồng bộ (Asynchronous Event Processing)** và cơ sở dữ liệu quan hệ MySQL kết hợp với Message Queue của Laravel.

Tài liệu này cung cấp hướng dẫn toàn diện cho Đội ngũ Vận hành Hệ thống (DevOps / SysAdmin) và Trưởng nhóm Kỹ thuật (Technical Lead) nhằm giám sát, tối ưu hóa hiệu năng, xử lý sự cố và duy trì độ tin cậy cao cho phân hệ thông báo.

---

## 2. QUẢN LÝ HÀNG ĐỢI (QUEUE WORKER MANAGEMENT)

### 2.1. Khởi chạy Queue Worker trong môi trường phát triển (Local / Dev)
Để lắng nghe và xử lý thông báo ngầm trong môi trường phát triển, chạy lệnh:
```bash
php artisan queue:work --queue=default,notifications --tries=3 --timeout=60
```
- `--queue=default,notifications`: Ưu tiên xử lý hàng đợi thông báo.
- `--tries=3`: Thử lại tối đa 3 lần nếu xảy ra sự cố mạng hoặc lỗi tạm thời trước khi đưa vào bảng `failed_jobs`.
- `--timeout=60`: Mỗi tác vụ có tối đa 60 giây để hoàn thành trước khi worker ngắt tiến trình.

### 2.2. Cấu hình Production với Supervisor (Linux Server)
Trong môi trường Production, bắt buộc phải sử dụng trình giám sát tiến trình (Process Monitor) như **Supervisor** để đảm bảo Queue Worker luôn tự khởi động lại khi gặp sự cố hoặc máy chủ khởi động lại.

Tệp cấu hình mẫu Supervisor: `/etc/supervisor/conf.d/agu-academic-hub-worker.conf`
```ini
[program:agu-notification-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/agu-academic-hub/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --queue=default,notifications
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/agu-academic-hub/storage/logs/notification-worker.log
stopwaitsecs=3600
```

Các lệnh quản trị Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start agu-notification-worker:*
sudo supervisorctl status
```

> [!IMPORTANT]
> **Khởi động lại Worker sau mỗi lần Deploy Code:**
> Mỗi khi cập nhật phiên bản ứng dụng mới, bắt buộc chạy lệnh:
> ```bash
> php artisan queue:restart
> ```
> Lệnh này sẽ ra tín hiệu an toàn cho các worker đang chạy hoàn tất job hiện tại và nạp lại toàn bộ code mới.

---

## 3. XỬ LÝ LỖI VÀ TÁC VỤ THẤT BẠI (FAILED JOBS MANAGEMENT)

Khi một tác vụ gửi thông báo thất bại sau 3 lần retry, Laravel sẽ tự động chuyển bản ghi vào bảng `failed_jobs`.

### 3.1. Kiểm tra danh sách các job thất bại
```bash
php artisan queue:failed
```
Đầu ra sẽ hiển thị: `ID`, `Connection`, `Queue`, `Class` (`App\Modules\Notifications\Infrastructure\Notifications\SystemNotification`), và `Failed At`.

### 3.2. Xem chi tiết nguyên nhân lỗi
```bash
# Xem log trong tệp storage/logs/laravel.log hoặc truy vấn bảng failed_jobs:
php artisan queue:failed:table
```

### 3.3. Thử lại job sau khi khắc phục nguyên nhân (Retry Jobs)
```bash
# Thử lại 1 job cụ thể theo UUID hoặc ID
php artisan queue:retry 5f83e582-766a-4d74-b52e-c3b036502da2

# Thử lại tất cả các job thất bại
php artisan queue:retry all
```

### 3.4. Xóa bỏ job lỗi vĩnh viễn (Flush Jobs)
Nếu job bị lỗi do dữ liệu nghiệp vụ không hợp lệ không thể gửi lại:
```bash
# Xóa 1 job cụ thể
php artisan queue:forget 5f83e582-766a-4d74-b52e-c3b036502da2

# Xóa toàn bộ các job thất bại
php artisan queue:flush
```

---

## 4. CHIẾN LƯỢC DỌN DẸP DỮ LIỆU ĐỊNH KỲ (DATA RETENTION & PRUNING)

Theo thời gian, bảng `notifications` sẽ tích lũy lượng lớn bản ghi cũ gây suy giảm hiệu năng truy vấn. Cần thiết lập chính sách lưu trữ (Data Retention Policy) như sau:

- **Thông báo đã đọc (`read_at IS NOT NULL`):** Lưu trữ trong 90 ngày.
- **Thông báo chưa đọc (`read_at IS NULL`):** Lưu trữ trong 180 ngày.
- **Mục công việc (`category = WORK_ITEM`) đã xử lý:** Lưu trữ trong 60 ngày.

### 4.1. Lệnh Artisan dọn dẹp thông báo cũ
Hệ thống hỗ trợ lệnh prune tự động của Laravel. Có thể cấu hình trong `app/Console/Kernel.php` hoặc `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;

Schedule::call(function () {
    // Xóa các thông báo đã đọc quá 90 ngày
    DB::table('notifications')
        ->whereNotNull('read_at')
        ->where('created_at', '<', now()->subDays(90))
        ->delete();

    // Xóa các thông báo chưa đọc quá 180 ngày
    DB::table('notifications')
        ->whereNull('read_at')
        ->where('created_at', '<', now()->subDays(180))
        ->delete();
})->dailyAt('02:00')->name('prune-old-notifications');
```

---

## 5. CẨM NANG XỬ LÝ SỰ CỐ THƯỜNG GẶP (TROUBLESHOOTING GUIDE)

### Sự cố 1: Người dùng phản ánh không nhận được thông báo
- **Nguyên nhân tiềm ẩn 1:** Queue Worker đang bị dừng hoặc bị treo.
  - *Kiểm tra:* Chạy `sudo supervisorctl status` hoặc `php artisan queue:failed`.
  - *Xử lý:* Khởi động lại worker bằng `php artisan queue:restart`.
- **Nguyên nhân tiềm ẩn 2:** Sự kiện nghiệp vụ bị bắn bên trong khối `DB::transaction()` và transaction đã bị rollback.
  - *Kiểm tra:* Kiểm tra log ứng dụng `storage/logs/laravel.log` tại thời điểm xảy ra giao dịch để xem có exception nào không.
  - *Xử lý:* Đảm bảo action phát event SAU KHI commit database.
- **Nguyên nhân tiềm ẩn 3:** Người nhận không khớp theo chính sách phân quyền (`NotificationRecipientPolicy`).
  - *Kiểm tra:* Kiểm tra tài khoản người nhận có đúng role (`SINH_VIEN`, `KIEM_DUYET_VIEN`) và đang ở trạng thái kích hoạt (`trang_thai = HOAT_DONG`) hay không.

### Sự cố 2: Số đếm thông báo chưa đọc (Badge) bị lệch so với thực tế
- **Hiện tượng:** Quả chuông hiển thị số 3 nhưng trong danh sách không thấy thông báo chưa đọc.
- **Nguyên nhân:** Cache trình duyệt của client hoặc do người dùng mở nhiều tab khác nhau.
- **Xử lý:**
  - Endpoint `/api/notifications/unread-count` luôn truy vấn trực tiếp thời gian thực (`whereNull('read_at')`).
  - Bấm nút "Đánh dấu tất cả đã đọc" (`POST /thong-bao/doc-tat-ca`) để đồng bộ lại trạng thái.

### Sự cố 3: Người dùng bị chuyển hướng về `/thong-bao` thay vì trang chi tiết
- **Nguyên nhân:** Tính năng bảo mật của hệ thống đã phát hiện `target_url` không hợp lệ (URL dẫn ra ngoài trang web, chứa tiền tố `http://`, `https://`, `//` hoặc script độc hại) và tự động thay thế bằng đường dẫn an toàn `/thong-bao`.
- **Xử lý:** Kiểm tra mã nguồn tạo `NotificationPayload`, đảm bảo `targetUrl` luôn là đường dẫn nội bộ bắt đầu bằng dấu `/` (ví dụ `/courses/12/documents/34`).

### Sự cố 4: Xuất hiện thông báo bị lặp nội dung (Duplication)
- **Nguyên nhân:** Một sự kiện bị dispatch nhiều lần hoặc queue worker bị timeout và tự retry khi tác vụ trước đó chưa kịp hoàn tất.
- **Xử lý:**
  - Kiểm tra xem payload có truyền `idempotencyKey` hay không.
  - `LaravelNotificationDispatcher` đã tích hợp cơ chế kiểm tra `idempotencyKey` trong JSON `data`. Hãy chắc chắn rằng Action nghiệp vụ truyền key cố định cho cùng một phiên sự kiện (ví dụ: `bounty_confirm_{id}_{contributorId}`).

### Sự cố 5: Lỗi Deadlock hoặc Timeout khi "Đánh dấu tất cả đã đọc"
- **Nguyên nhân:** Người dùng có hàng chục nghìn thông báo chưa đọc, dẫn đến việc khóa dòng (Row-level Locking) kéo dài trên bảng `notifications`.
- **Xử lý:**
  - `MarkAllNotificationsAsReadAction` sử dụng câu lệnh trực tiếp:
    ```sql
    UPDATE notifications 
    SET read_at = NOW() 
    WHERE notifiable_type = 'App\\Models\\NguoiDung' 
      AND notifiable_id = :userId 
      AND read_at IS NULL;
    ```
  - Đảm bảo chỉ mục đa cột (Composite Index) `(notifiable_type, notifiable_id, read_at)` trên bảng `notifications` luôn tồn tại để câu lệnh UPDATE thực hiện trong thời gian dưới 10ms.

