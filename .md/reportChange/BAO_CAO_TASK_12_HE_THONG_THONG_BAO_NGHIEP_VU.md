# Báo cáo triển khai Task 12: Hệ thống thông báo nghiệp vụ

## 1. Tổng quan kiến trúc

Hệ thống thông báo nghiệp vụ được thiết kế và triển khai tuân thủ nghiêm ngặt mô hình **Modular Monolith 4 tầng** của AGU Academic Hub:

```text
app/Modules/{ModuleName}/
├── Application/       # Actions xử lý nghiệp vụ, kích hoạt & điều phối thông báo
├── Domain/            # Rules, Evaluator, Logic nghiệp vụ kiểm tra điều kiện thông báo
├── Infrastructure/   # Notifications classes (kế thừa Laravel Notification), Persistence
└── Presentation/     # Controllers, API endpoints, Form Requests, View Components
```

Mỗi module quản lý các lớp thông báo thuộc đúng thẩm quyền nghiệp vụ của mình:
- **Module `Identity`**: Thông báo KYC cựu sinh viên (tiếp nhận, duyệt, từ chối), thông báo đổi mật khẩu lần đầu và kích hoạt tài khoản sinh viên.
- **Module `Moderation`**: Thông báo báo cáo vi phạm mới, tài liệu đạt ngưỡng tự động chuyển chờ kiểm tra hoặc tự động ẩn.
- **Module `Documents`**: Thông báo yêu cầu gỡ tài liệu từ tác giả, thông báo tài liệu sắp hết hạn lưu trong thùng rác.
- **Module `Administration`**: Thông báo hoàn tất nạp danh sách sinh viên thô, cảnh báo phát hiện lỗi hoặc bản ghi trùng trong quá trình import.

---

## 2. Chi tiết triển khai theo từng nhóm đối tượng

### 2.1. Đối với Quản trị viên và Kiểm duyệt viên (Admin / Moderator)

| Nghiệp vụ | Notification Class | Module | Trigger | Nội dung thông báo |
|---|---|---|---|---|
| **1. Có hồ sơ Alumni KYC mới** | `NewAlumniKycNotification` | `Identity` | `SubmitAlumniKycAction` khi cựu sinh viên nộp KYC | **Tiêu đề:** `Có hồ sơ Alumni KYC mới`<br>**Nội dung:** Thông tin họ tên, MSSV, khóa tốt nghiệp của cựu sinh viên kèm link tới trang duyệt KYC. |
| **2. Có báo cáo vi phạm mới** | `NewViolationReportNotification` | `Moderation` | `DocumentReportRecalculator::createReport` khi có người dùng gửi báo cáo vi phạm | **Tiêu đề:** `Có báo cáo vi phạm mới`<br>**Nội dung:** Tên tài liệu, lý do vi phạm, người gửi báo cáo kèm link tới hàng đợi báo cáo. |
| **3. Tài liệu đạt ngưỡng tự động chuyển sang chờ kiểm tra** | `DocumentReviewRequiredNotification` | `Moderation` | `DocumentReportRecalculator::recalculate` khi tài liệu đạt ngưỡng báo cáo hợp lệ (≥ 3) hoặc ẩn tự động (≥ 5) | **Tiêu đề:** `Tài liệu đạt ngưỡng tự động chuyển sang chờ kiểm tra` (hoặc `Tài liệu đã được tự động ẩn`)<br>**Nội dung:** Số lượng báo cáo hợp lệ từ tài khoản uy tín và trạng thái chuyển hàng đợi. |
| **4. Có yêu cầu gỡ tài liệu mới** | `DocumentRemovalRequestSubmittedNotification` | `Documents` | `SubmitRemovalRequestAction` khi tác giả gửi yêu cầu gỡ tài liệu thuộc sở hữu cộng đồng | **Tiêu đề:** `Có yêu cầu gỡ tài liệu mới`<br>**Nội dung:** Tên tài liệu và lý do tác giả yêu cầu gỡ tài liệu. |
| **5. Có dữ liệu sinh viên mới được nạp** | `StudentRosterImportedNotification` | `Administration` | `ImportRawStudentsAction` khi nạp dữ liệu sinh viên thô | **Tiêu đề:** `Có dữ liệu sinh viên mới được nạp`<br>**Nội dung:** Tổng số sinh viên nạp, số bản ghi thêm mới, số bản ghi cập nhật. |
| **6. Có lỗi hoặc bản ghi trùng trong quá trình import** | `StudentRosterImportWarningNotification` | `Administration` | `ImportRawStudentsAction` khi phát hiện dữ liệu lỗi hoặc MSSV trùng lặp | **Tiêu đề:** `Có lỗi hoặc bản ghi trùng trong quá trình import`<br>**Nội dung:** Số lượng bản ghi lỗi (thiếu MSSV, thiếu họ tên) và số lượng bản ghi trùng lặp trong lô nạp. |
| **7. Có tài liệu sắp hết thời gian lưu trong thùng rác** | `DocumentsExpiringInTrashNotification` | `Documents` | `CheckExpiringTrashDocumentsAction` qua Command `documents:notify-expiring-trash` | **Tiêu đề:** `Có tài liệu sắp hết thời gian lưu trong thùng rác`<br>**Nội dung:** Danh sách và số lượng tài liệu xóa mềm sắp hết hạn lưu trữ (mốc 60 ngày hoặc 90 ngày tự động xóa vĩnh viễn). |

---

### 2.2. Đối với Sinh viên đang học

| Nghiệp vụ | Notification Class | Module | Trigger / Cơ chế bảo vệ | Nội dung thông báo |
|---|---|---|---|---|
| **8. Thông báo yêu cầu đổi mật khẩu lần đầu** | `FirstTimePasswordChangeRequiredNotification` | `Identity` | `AuthenticateUserAction` khi sinh viên đăng nhập lần đầu bằng mật khẩu khởi tạo | **Tiêu đề:** `Thông báo yêu cầu đổi mật khẩu lần đầu`<br>**Nội dung:** Chào mừng sinh viên đến với AGU Academic Hub, yêu cầu đổi mật khẩu khởi tạo để kích hoạt tài khoản. |
| **9. Thông báo kích hoạt tài khoản thành công** | `StudentAccountActivatedNotification` | `Identity` | `ChangeFirstTimePasswordAction` sau khi sinh viên đổi mật khẩu thành công | **Tiêu đề:** `Thông báo kích hoạt tài khoản thành công`<br>**Nội dung:** Chúc mừng sinh viên đã kích hoạt tài khoản thành công và sẵn sàng sử dụng các tài nguyên học thuật. |
| **10. Không gửi yêu cầu hoặc thông báo KYC vì sinh viên không thuộc luồng KYC** | *Chặn nghiệp vụ (Rule Guard)* | `Identity` | - `AlumniKycRuleEvaluator::isCurrentStudent`: Chặn tuyệt đối sinh viên hiện hành nộp KYC cựu sinh viên.<br>- Không gửi bất kỳ KYC notification nào cho sinh viên đang học. | Không áp dụng (Sinh viên không thuộc luồng xác thực KYC). |

---

### 2.3. Đối với Cựu sinh viên

| Nghiệp vụ | Notification Class | Module | Trigger | Nội dung thông báo |
|---|---|---|---|---|
| **11. Đã tiếp nhận hồ sơ KYC** | `AlumniKycReceivedNotification` | `Identity` | `SubmitAlumniKycAction` ngay sau khi lưu trữ hồ sơ và minh chứng KYC | **Tiêu đề:** `Đã tiếp nhận hồ sơ KYC`<br>**Nội dung:** Xác nhận hệ thống đã tiếp nhận hồ sơ thành công. |
| **12. Hồ sơ đang chờ xử lý** | `AlumniKycReceivedNotification` | `Identity` | Đi kèm thông báo tiếp nhận hồ sơ KYC | **Nội dung:** Nêu rõ hồ sơ đang ở trạng thái chờ duyệt và Ban quản trị sẽ tiến hành đối soát. |
| **13. Hồ sơ được duyệt** | `AlumniKycDecisionNotification` | `Identity` | `ApproveKycAction` khi Admin/Moderator phê duyệt hồ sơ | **Tiêu đề:** `Hồ sơ KYC đã được duyệt`<br>**Nội dung:** Thông báo hồ sơ KYC đã được phê duyệt, tài khoản được kích hoạt. |
| **14. Hồ sơ bị từ chối kèm lý do** | `AlumniKycDecisionNotification` | `Identity` | `RejectKycAction` khi Admin/Moderator từ chối hồ sơ kèm lý do | **Tiêu đề:** `Hồ sơ KYC bị từ chối`<br>**Nội dung:** Nêu rõ lý do từ chối cụ thể từ kiểm duyệt viên để cựu sinh viên nắm bắt và nộp lại nếu cần. |
| **15. Được phép đăng nhập sau khi hồ sơ được duyệt** | `AlumniKycDecisionNotification` & `AuthenticateUserAction` | `Identity` | - Khi duyệt: Thông báo nêu rõ cựu sinh viên hiện được phép đăng nhập.<br>- Khi cố đăng nhập lúc đang chờ: Hiển thị thông báo rõ ràng "Bạn sẽ được phép đăng nhập sau khi hồ sơ được duyệt." | **Thông điệp:** "Hồ sơ cựu sinh viên của bạn đang chờ xử lý. Bạn sẽ được phép đăng nhập sau khi hồ sơ được duyệt." |

---

## 3. Quản lý thông báo và giao diện người dùng (UI & API)

### 3.1. Model `ThongBao` (`app/Models/ThongBao.php`)
- Kế thừa `Illuminate\Notifications\DatabaseNotification`.
- Tự động bắt sự kiện `creating` để điền đồng bộ 2 cột `title` và `message` vào bảng cơ sở dữ liệu `notifications` từ mảng dữ liệu `$data`.
- Cung cấp các thuộc tính ảo:
  - `tieu_de`: Lấy tiêu đề thông báo.
  - `noi_dung`: Lấy nội dung thông báo.
  - `duong_dan`: Lấy đường dẫn điều hướng của thông báo (`url` hoặc `action_url`).
  - `la_chua_doc`: Kiểm tra trạng thái chưa đọc.
- Model `NguoiDung` kết nối quan hệ `notifications(): MorphMany` trả về `ThongBao`.

### 3.2. Notification Controller & API (`app/Http/Controllers/NotificationController.php`)
Đăng ký các tuyến đường bảo vệ trong `routes/web.php` với middleware `auth`:
- `GET /thong-bao` (`notifications.index`): Trả về danh sách thông báo gần nhất và `unread_count`.
- `POST /thong-bao/{id}/doc` (`notifications.read`): Đánh dấu một thông báo là đã đọc.
- `POST /thong-bao/doc-tat-ca` (`notifications.read-all`): Đánh dấu tất cả thông báo của người dùng là đã đọc.

### 3.3. Dữ liệu chia sẻ toàn cục Inertia (`app/Http/Middleware/HandleInertiaRequests.php`)
- Bổ sung `auth.unread_notifications_count` vào dữ liệu chia sẻ toàn cục xuống giao diện React, cho phép hiển thị badge thông báo ngay khi tải trang mà không cần gọi API phụ.

### 3.4. Component Dropdown thông báo (`resources/js/Components/NotificationDropdown.jsx`)
- Hiển thị nút chuông với badge số lượng thông báo chưa đọc động (`99+` nếu vượt quá).
- Hỗ trợ 2 biến thể giao diện:
  - `variant="student"` cho `StudentHeader.jsx` (Cổng sinh viên / cựu sinh viên).
  - `variant="admin"` cho `AdminLayout.jsx` (Topbar Bảng điều khiển quản trị viên).
- Dropdown tương tác mượt mà:
  - Hiển thị danh sách thông báo phân loại theo icon (Thành công, Cảnh báo, Vi phạm, Thông tin).
  - Đánh dấu đã đọc khi bấm vào thông báo và điều hướng tới trang liên quan.
  - Nút "Đã đọc tất cả" với icon CheckCheck.
  - Trạng thái rỗng (Empty State) và trạng thái đang tải (Loading Spinner).
  - Tự động đóng khi nhấn phím Escape hoặc click ra ngoài.

### 3.5. Cron Job & Artisan Command
- **Command:** `php artisan documents:notify-expiring-trash --days=7`
- **Lập lịch:** Đã khai báo `Schedule::command('documents:notify-expiring-trash')->daily();` trong `routes/console.php`.

---

## 4. Kết quả kiểm thử

### 4.1. Bộ kiểm thử thông báo chuyên biệt (`tests/Feature/BusinessNotificationWorkflowTest.php`)
Tất cả 14 test case bao phủ toàn bộ 15 yêu cầu thông báo đều chạy thành công:
1. `test_admin_and_moderator_receive_notification_on_new_alumni_kyc`: PASS
2. `test_admin_and_moderator_receive_notification_on_new_violation_report`: PASS
3. `test_admin_and_moderator_notified_when_document_reaches_review_threshold`: PASS
4. `test_admin_and_moderator_notified_when_removal_request_submitted`: PASS
5. `test_admin_notified_on_successful_raw_students_import`: PASS
6. `test_admin_notified_on_errors_or_duplicates_during_raw_students_import`: PASS
7. `test_admin_and_moderator_notified_on_expiring_trash_documents`: PASS
8. `test_current_student_receives_first_time_password_change_notification`: PASS
9. `test_current_student_receives_account_activated_notification_after_password_change`: PASS
10. `test_current_student_cannot_submit_kyc_and_does_not_receive_kyc_notifications`: PASS
11. `test_alumni_receives_approval_notification_stating_allowed_login`: PASS
12. `test_alumni_receives_rejection_notification_with_reason`: PASS
13. `test_alumni_pending_kyc_receives_clear_message_on_login_attempt`: PASS
14. `test_user_can_fetch_notifications_and_mark_as_read`: PASS

### 4.2. Toàn bộ Test Suite hệ thống
- **145 passed (764 assertions)**, thời gian chạy ~10.28s.
- Không có bất kỳ lỗi hồi quy nào.

### 4.3. Kiểm thử Frontend Vite Build
- Biên dịch production thành công trong 13.66s không có lỗi hay cảnh báo.
- `public/build/assets/NotificationDropdown-*.js` được đóng gói thành công.

