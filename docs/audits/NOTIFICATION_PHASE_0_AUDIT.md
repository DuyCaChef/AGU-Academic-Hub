# BÁO CÁO KHẢO SÁT & SCOPE LOCK: HỆ THỐNG THÔNG BÁO DÙNG CHUNG (NOTIFICATION_PHASE_0_AUDIT)

- **Dự án**: AGU Academic Hub
- **Phân hệ**: Shared OOP Notification System
- **Thời điểm khảo sát**: 08/11/2026
- **Trạng thái**: Hoàn tất khảo sát kiến trúc – Khóa phạm vi triển khai (Scope Locked)

---

## 1. KHẢO SÁT HIỆN TRẠNG TOÀN DIỆN

### 1.1. Cấu hình Môi trường & Phiên bản
- **Laravel Framework**: `11.0.*` (`composer.json`: `"laravel/framework": "^11.0"`, `"inertiajs/inertia-laravel": "^2.0"`).
- **PHP**: `^8.2`.
- **Cơ sở dữ liệu**: MySQL 8.0 (Production / Local), SQLite in-memory (Testing).
- **Queue Connection**: `QUEUE_CONNECTION=database` (Đã có bảng `jobs`, `failed_jobs`, `job_batches` tại migration `2026_10_14_000011_create_database_queue_tables.php`).
- **Broadcast Connection**: Hiện tại đặt `BROADCAST_CONNECTION=log`. Chưa cài đặt Laravel Reverb.

### 1.2. Cấu trúc Thư mục và Kiến trúc Dự án
Dự án áp dụng kiến trúc **Modular Monolith** phân tầng chặt chẽ:
```
app/
├── Http/
│   ├── Controllers/NotificationController.php (Legacy basic API)
│   └── Middleware/CheckRole.php
├── Models/
│   ├── NguoiDung.php (Sử dụng Notifiable trait, morphMany ThongBao)
│   └── ThongBao.php (Kế thừa DatabaseNotification)
├── Modules/
│   ├── AcademicCatalog/
│   ├── Administration/
│   ├── Bounties/
│   ├── Documents/
│   ├── Identity/
│   ├── Moderation/
│   └── Reviews/
└── Shared/
    └── Support/Module.php (Base ServiceProvider)
```

### 1.3. Các Thành phần Thông báo ĐÃ TỒN TẠI
1. **Database Schema**:
   - Bảng `notifications` đã tồn tại từ migration `2026_10_14_000009_create_notifications_table.php`:
     - `id` (UUID - Primary Key)
     - `notifiable_type`, `notifiable_id` (Morphs tới `NguoiDung`)
     - `type` (Class string của Notification)
     - `title` (VARCHAR 255 nullable)
     - `message` (TEXT nullable)
     - `data` (TEXT json-encoded payload)
     - `read_at` (TIMESTAMP nullable)
     - `created_at`, `updated_at` (TIMESTAMPS)
     - Chỉ mục tối ưu: `notifications_unread_lookup_idx` (`notifiable_type, notifiable_id, read_at, created_at`).
2. **Model thực thể**:
   - `App\Models\ThongBao`: Kế thừa `Illuminate\Notifications\DatabaseNotification`. Tự động đồng bộ `title` và `message` từ `$data` khi tạo mới; cung cấp các accessors: `tieu_de`, `noi_dung`, `duong_dan`, `la_chua_doc`.
   - `App\Models\NguoiDung`: Khai báo quan hệ `notifications(): MorphMany` trỏ tới `ThongBao::class`.
3. **Controller hiện tại**:
   - `App\Http\Controllers\NotificationController`: Chứa 3 methods thô:
     - `index()`: Lấy tối đa 20 thông báo gần nhất, trả JSON.
     - `markAsRead($id)`: Đánh dấu đã đọc cho 1 thông báo.
     - `markAllAsRead()`: Đánh dấu đã đọc tất cả.
4. **Các lớp Notification rải rác**:
   - `Identity`: `AlumniKycDecisionNotification`, `AlumniKycReceivedNotification`, `FirstTimePasswordChangeRequiredNotification`, `NewAlumniKycNotification`, `StudentAccountActivatedNotification`.
   - `Moderation`: `DocumentReviewRequiredNotification`, `NewViolationReportNotification`, `ViolationReportResolvedNotification`.
   - `Documents`: `DocumentRemovalRequestResolvedNotification`, `DocumentRemovalRequestSubmittedNotification`, `DocumentsExpiringInTrashNotification`.
   - `Administration`: `StudentRosterImportedNotification`, `StudentRosterImportWarningNotification`.
5. **Giao diện Frontend**:
   - `resources/js/Components/NotificationDropdown.jsx`: Component chuông thông báo (Bell Icon, Unread Badge, Dropdown danh sách, Đã đọc 1 cái, Đã đọc tất cả).
   - Đang được gắn tại `resources/js/Components/layouts/StudentHeader.jsx` (dùng cho Student/Alumni) và `resources/js/Layouts/AdminLayout.jsx` (dùng cho Admin/Moderator).

---

## 2. NHỮNG GÌ CHƯA TỒN TẠI VÀ THIẾU HỤT NGHIỆP VỤ

1. **Chưa có Notification Module chuẩn OOP phân tầng**:
   - Chưa có thư mục `app/Modules/Notifications/`.
   - Chưa có các Interface và Dispatcher: `NotificationDispatcherInterface`, `DatabaseNotificationDispatcher`.
   - Chưa có tầng Application hoàn chỉnh: Thiếu `GetNotificationsQuery` (phân trang, lọc `all/unread/read`), `GetUnreadNotificationCountQuery`, `MarkNotificationAsReadAction`, `MarkAllNotificationsAsReadAction`, `DeleteNotificationAction`.
   - Chưa có tầng Domain: Thiếu `NotificationType` enum, `NotificationCategory` enum (phân tách `PERSONAL` vs `WORK_ITEM`), `NotificationRecipientResolver` (xác định chính xác đối tượng nhận theo quyền RBAC).
2. **Thiếu cơ chế Event-Driven thống nhất**:
   - Toàn bộ codebase hiện **hoàn toàn chưa có Domain Events** cho các nghiệp vụ:
     - Bounty: `DocumentContributedToBountyEvent`, `BountyFulfillmentConfirmedEvent`, `BountyCancelledEvent`.
     - Document: `DocumentApprovedEvent`, `DocumentRejectedEvent`, `DocumentUploadedEvent`.
     - Review: `ReviewReportedEvent`.
     - KYC: `KycSubmittedEvent`, `KycApprovedEvent`, `KycRejectedEvent`.
   - Các action hiện tại hoặc là gọi trực tiếp `$user->notify(...)` bên trong DB transaction (tiềm ẩn nguy cơ gửi thông báo khi rollback), hoặc hoàn toàn chưa phát thông báo (như phân hệ Bounty).
3. **Chưa có Trang Trung tâm Thông báo (Notification Center Page)**:
   - Chưa có trang `resources/js/Pages/Notifications/Index.jsx` cho phép xem toàn bộ thông báo với phân trang, bộ lọc tab (Tất cả, Chưa đọc, Đã đọc), nút xóa từng thông báo, và điều hướng an toàn.
4. **Chưa có API xóa thông báo và đếm thông báo chưa đọc độc lập**:
   - Thiếu `DELETE /thong-bao/{id}` (hoặc `DELETE /notifications/{id}`).
   - Thiếu `GET /thong-bao/so-luong-chua-doc` (hoặc `GET /notifications/unread-count`).
5. **Hạ tầng Realtime (Broadcast / Reverb)**:
   - Chưa có cấu hình Laravel Reverb / WebSocket. Hiện tại `BROADCAST_CONNECTION=log`. Cần thiết kế Notification Dispatcher sao cho có sẵn socket adapter để mở rộng phát sóng realtime trong tương lai mà không làm hỏng tầng lưu trữ Database cốt lõi.

---

## 3. CÁC THÀNH PHẦN CẦN TÁI SỬ DỤNG
- **Bảng `notifications`**: Giữ nguyên 100%, không chạy migration tạo bảng mới hoặc sửa schema gây xung đột.
- **Model `ThongBao`**: Tiếp tục kế thừa `DatabaseNotification`, tái sử dụng các accessors hiện có.
- **Model `NguoiDung` & RBAC Helper Methods**:
  - `laQuanTriVien(): bool`
  - `laSuperAdmin(): bool`
  - `laAdminThuong(): bool`
  - `laKiemDuyetVien(): bool`
  - `vai_tro` enum (`SINH_VIEN`, `CUU_SINH_VIEN`, `KIEM_DUYET_VIEN`, `QUAN_TRI_VIEN`).
- **Component `NotificationDropdown.jsx`**: Tái sử dụng và hoàn thiện để kết nối đồng bộ với Notification Center mới.
- **Các Layouts**: `StudentLayout.jsx` (qua `StudentHeader.jsx`) và `AdminLayout.jsx`.

---

## 4. MA TRẬN RỦI RO & GIẢI PHÁP KIẾN TRÚC

| Rủi ro kỹ thuật / nghiệp vụ | Mức độ | Biện pháp giải quyết triệt để |
|---|---|---|
| **Rủi ro gửi thông báo khi Transaction Rollback** | CAO | Dispatch event sau khi transaction commit thành công bằng cách sử dụng `DB::afterCommit(...)` hoặc dispatch Event bên ngoài closure transaction. |
| **Rủi ro spam thông báo quản trị cho Admin (Admin Inbox Exhaustion)** | CAO | Xây dựng `NotificationRecipientPolicy` & `RecipientResolver`: Chỉ gửi cho Admin/Mod có thẩm quyền tương ứng (ví dụ: KYC chỉ gửi người có quyền KYC; vi phạm chỉ gửi ban kiểm duyệt; không gửi đại trà mọi log). |
| **Rủi ro trộn lẫn thông báo cá nhân và tác vụ công việc** | TRUNG BÌNH | Phân loại rõ `category: PERSONAL | WORK_ITEM` trong payload data, giúp giao diện hiển thị icon, màu sắc và phân nhóm rõ ràng mà không cần tạo 2 bảng database. |
| **Rủi ro lộ dữ liệu nhạy cảm / PII / File riêng tư** | CAO | `NotificationResource` lọc chặt chẽ dữ liệu đầu ra; Notification payload chỉ chứa ID, Title tóm tắt, URL nội bộ đã được ký danh; Tuyệt đối không lưu mật khẩu, ảnh CCCD, đường dẫn file nhạy cảm. |
| **Rủi ro Open Redirect** | TRUNG BÌNH | Validate `target_url` chỉ chấp nhận đường dẫn tương đối nội bộ (bắt đầu bằng `/`), từ chối scheme `http://` hoặc `https://` trỏ ra domain ngoài. |
| **Rủi ro gửi trùng lặp khi Queue Retry** | TRUNG BÌNH | Áp dụng Idempotency Key hoặc kiểm tra sự tồn tại của notification cùng type, target_id và recipient trong khoảng thời gian cửa sổ trước khi dispatch. |
| **Rủi ro vỡ giao diện / vỡ route cũ** | THẤP | Giữ nguyên các route `/thong-bao`, `/thong-bao/{id}/doc`, `/thong-bao/doc-tat-ca` để tương thích 100% với `NotificationDropdown.jsx`, đồng thời bổ sung các route mới chuẩn REST. |

---

## 5. ĐỀ XUẤT THIẾT KẾ KIẾN TRÚC CUỐI CÙNG (FINAL ARCHITECTURE)

Module mới sẽ được tổ chức tại `app/Modules/Notifications/`:
```
app/Modules/Notifications/
├── Application/
│   ├── Actions/
│   │   ├── MarkNotificationAsReadAction.php
│   │   ├── MarkAllNotificationsAsReadAction.php
│   │   └── DeleteNotificationAction.php
│   ├── Contracts/
│   │   ├── NotificationDispatcherInterface.php
│   │   └── NotificationRecipientResolverInterface.php
│   ├── DTOs/
│   │   └── NotificationPayload.php
│   └── Queries/
│       ├── GetNotificationsQuery.php
│       └── GetUnreadNotificationCountQuery.php
├── Domain/
│   ├── Enums/
│   │   ├── NotificationType.php
│   │   └── NotificationCategory.php (PERSONAL, WORK_ITEM)
│   ├── Events/
│   │   ├── DocumentContributedToBountyEvent.php
│   │   ├── BountyFulfillmentConfirmedEvent.php
│   │   ├── BountyCancelledEvent.php
│   │   ├── DocumentModeratedEvent.php
│   │   ├── ReviewReportedEvent.php
│   │   └── KycDecisionEvent.php
│   └── Services/
│       ├── NotificationRecipientPolicy.php
│       └── NotificationPayloadBuilder.php
├── Infrastructure/
│   ├── Adapters/
│   │   └── LaravelNotificationDispatcher.php
│   ├── Channels/
│   │   └── SafeDatabaseChannel.php
│   ├── Listeners/
│   │   ├── SendBountyNotificationListener.php
│   │   ├── SendDocumentModerationNotificationListener.php
│   │   ├── SendReviewReportNotificationListener.php
│   │   └── SendKycNotificationListener.php
│   └── Notifications/
│       └── SystemNotification.php (Universal OOP Database Notification)
├── Presentation/
│   ├── Http/
│   │   ├── Controllers/NotificationController.php
│   │   ├── Requests/GetNotificationsRequest.php
│   │   └── Resources/NotificationResource.php
│   └── routes.php
└── NotificationsServiceProvider.php
```

---

## 6. DANH SÁCH FILE DỰ KIẾN TÁC ĐỘNG

### 6.1. File mới
- Toàn bộ module `app/Modules/Notifications/` (Service Provider, Actions, Queries, Contracts, Domain Enums & Events, Listeners, Controller, Resource, routes).
- Trang giao diện mới: `resources/js/Pages/Notifications/Index.jsx` (Notification Center).
- Test suites mới: `tests/Feature/NotificationModuleIntegrationTest.php`.

### 6.2. File chỉnh sửa tích hợp
- `app/Providers/AppServiceProvider.php`: Đăng ký `NotificationsServiceProvider`.
- `routes/web.php`: Trỏ tuyến đường thông báo sang NotificationController của module mới.
- `app/Modules/Bounties/Application/Actions/FulfillBountyRequestAction.php`: Phát event khi nộp tài liệu thành công.
- `app/Modules/Bounties/Application/Actions/ConfirmBountyFulfillmentAction.php`: Phát event khi giải ngân thành công.
- `app/Modules/Bounties/Application/Actions/CancelBountyRequestAction.php`: Phát event khi hủy yêu cầu & hoàn điểm.
- `app/Modules/Moderation/Application/Actions/DecideDocumentReviewAction.php`: Phát event khi duyệt/từ chối tài liệu.
- `resources/js/Components/NotificationDropdown.jsx`: Bổ sung nút bấm "Xem tất cả thông báo" dẫn tới trang `route('notifications.center')`.

### 6.3. Danh sách Migration dự kiến
- **KHÔNG CẦN BẤT KỲ MIGRATION MỚI NÀO**: Bảng `notifications` và các bảng `jobs` hiện có đã đáp ứng 100% yêu cầu.

---

## 7. KẾT LUẬN & CHUYỂN BƯỚC

Phân tích hiện trạng cho thấy nền tảng cơ sở dữ liệu đã sẵn sàng. Chúng ta sẽ tiến hành ngay **Phase 1: Notification Core**, tiếp theo là **Phase 2: Event-Driven Architecture**, **Phase 3: Business Integration**, **Phase 4: Frontend Notification Center**, **Phase 5: Realtime & Hardening**, và hoàn tất đầy đủ 6 tài liệu bàn giao theo yêu cầu.

