# Báo cáo triển khai Task 7: Xử lý yêu cầu gỡ tài liệu thuộc quyền sở hữu cộng đồng

## 1. Mục tiêu

Xây dựng quy trình để tác giả không thể tự xóa tài liệu có giá trị cộng đồng mà phải gửi yêu cầu cho Moderator/Admin xét duyệt.

Quy trình đáp ứng các yêu cầu:

- Tác giả gửi yêu cầu gỡ kèm lý do.
- Chỉ cho phép gửi khi tài liệu đạt điều kiện Community Ownership.
- Moderator/Admin xem danh sách yêu cầu gỡ.
- Xem tài liệu, tác giả, môn học và số lượt tải.
- Xem lý do tác giả yêu cầu gỡ.
- Duyệt hoặc từ chối yêu cầu.
- Bắt buộc nhập lý do khi từ chối.
- Ghi nhận người xử lý và thời điểm xử lý.
- Soft-delete tài liệu khi yêu cầu được duyệt.
- Thông báo kết quả cho tác giả.
- Không cho phép tài liệu cộng đồng bị xóa ngoài quy trình.

## 2. Quy tắc nghiệp vụ

### 2.1. Điều kiện Community Ownership

Mặc định tài liệu được xem là thuộc quyền sở hữu cộng đồng khi đạt tối thiểu:

```text
10 lượt tải
```

Giá trị được cấu hình tại:

```text
config/documents.php
```

Có thể thay đổi bằng biến môi trường:

```env
DOCUMENT_COMMUNITY_OWNERSHIP_DOWNLOAD_THRESHOLD=10
```

### 2.2. Quyền gửi yêu cầu

Chỉ tác giả của tài liệu mới được gửi yêu cầu gỡ.

Hệ thống từ chối khi:

- Người gửi không phải tác giả.
- Tài liệu có số lượt tải nhỏ hơn ngưỡng Community Ownership.
- Tài liệu đã có một yêu cầu `CHO_XU_LY`.
- Lý do không được nhập.

### 2.3. Quyết định của Moderator/Admin

| Thao tác | Trạng thái yêu cầu | Xử lý tài liệu |
|---|---|---|
| Duyệt | `DA_DUYET` | Soft-delete tài liệu |
| Từ chối | `TU_CHOI` | Giữ nguyên tài liệu |
| Chưa xử lý | `CHO_XU_LY` | Chưa thay đổi tài liệu |

Khi duyệt, hệ thống kiểm tra lại số lượt tải trong transaction. Điều này ngăn việc duyệt một yêu cầu đã không còn đáp ứng điều kiện Community Ownership.

Khi từ chối, trường `decision_reason` là bắt buộc.

## 3. Database

### 3.1. Migration mới

File:

```text
database/migrations/2026_10_30_000002_create_yeu_cau_go_bo_tai_lieu_table.php
```

Bảng mới:

```text
yeu_cau_go_bo_tai_lieu
```

Các trường chính:

| Trường | Ý nghĩa |
|---|---|
| `ma_yeu_cau_go_bo` | Khóa chính |
| `ma_tai_lieu` | Tài liệu cần gỡ |
| `ma_nguoi_yeu_cau` | Tác giả gửi yêu cầu |
| `ly_do` | Lý do yêu cầu gỡ |
| `trang_thai` | `CHO_XU_LY`, `DA_DUYET`, `TU_CHOI` |
| `ma_nguoi_xu_ly` | Moderator/Admin xử lý |
| `xu_ly_luc` | Thời điểm xử lý |
| `ly_do_tu_choi` | Lý do từ chối |
| `created_at`, `updated_at` | Thời gian tạo/cập nhật |

Index được thêm cho:

```text
(trang_thai, created_at)
(ma_tai_lieu, trang_thai)
```

Các khóa ngoại sử dụng `restrictOnDelete()` đối với tài liệu và người gửi để bảo toàn lịch sử yêu cầu.

### 3.2. Cấu hình ngưỡng

File:

```text
config/documents.php
```

Cấu hình:

```php
'community_ownership_download_threshold' => 10
```

## 4. Model và quan hệ

### 4.1. Model yêu cầu gỡ

File:

```text
app/Models/YeuCauGoBoTaiLieu.php
```

Model ánh xạ bảng `yeu_cau_go_bo_tai_lieu`, định nghĩa:

- `$fillable`.
- Cast `xu_ly_luc` sang kiểu datetime.
- Quan hệ `taiLieu()`.
- Quan hệ `nguoiYeuCau()`.
- Quan hệ `nguoiXuLy()`.

### 4.2. Quan hệ trên tài liệu

File:

```text
app/Models/TaiLieu.php
```

Thêm quan hệ:

```php
yeuCauGoBos()
```

Quan hệ này trả về danh sách yêu cầu gỡ của tài liệu.

### 4.3. Quan hệ trên người dùng

File:

```text
app/Models/NguoiDung.php
```

Thêm quan hệ:

```php
yeuCauGoBoTaiLieus()
```

Quan hệ này truy xuất các yêu cầu do người dùng tạo.

## 5. Backend và controller

### 5.1. Tác giả gửi yêu cầu

File:

```text
app/Http/Controllers/DocumentController.php
```

Phương thức:

```php
requestRemoval()
```

Endpoint:

```text
POST /tai-lieu/{id}/yeu-cau-go-bo
```

Chức năng:

1. Validate lý do, tối đa 2.000 ký tự.
2. Tìm tài liệu.
3. Kiểm tra người gửi có phải tác giả hay không.
4. Kiểm tra số lượt tải đạt ngưỡng.
5. Khóa và kiểm tra yêu cầu đang chờ trong transaction.
6. Tạo yêu cầu mới ở trạng thái `CHO_XU_LY`.
7. Gửi notification cho Moderator/Admin.
8. Trả thông báo thành công cho tác giả.

### 5.2. Hàng đợi quản trị

File:

```text
app/Http/Controllers/DocumentRemovalRequestController.php
```

#### Danh sách hàng đợi

Phương thức:

```php
index()
```

Chức năng:

- Phân trang 15 bản ghi.
- Lọc theo trạng thái.
- Eager-load tài liệu, môn học, tác giả, người gửi yêu cầu và người xử lý.
- Dùng `withTrashed()` để yêu cầu đã duyệt vẫn hiển thị tài liệu đã soft-delete.

#### Xem chi tiết

Phương thức:

```php
show()
```

Hiển thị hồ sơ yêu cầu và dữ liệu liên quan:

- Tiêu đề tài liệu.
- Môn học.
- Tác giả.
- Email người gửi.
- Số lượt tải.
- Ngưỡng Community Ownership.
- Lý do yêu cầu gỡ.
- Trạng thái xử lý.

#### Duyệt hoặc từ chối

Phương thức:

```php
decide()
```

Endpoint:

```text
PUT /quan-tri/yeu-cau-go-tai-lieu/{id}/quyet-dinh
```

Validation:

```text
action: APPROVE hoặc REJECT
decision_reason: bắt buộc khi REJECT, tối đa 2.000 ký tự
```

Quy trình xử lý:

1. Khóa bản ghi bằng `lockForUpdate()`.
2. Không cho xử lý lại yêu cầu đã hoàn tất.
3. Kiểm tra lại điều kiện Community Ownership nếu duyệt.
4. Ghi trạng thái.
5. Ghi người xử lý từ tài khoản đăng nhập, không nhận từ client.
6. Ghi thời điểm xử lý.
7. Ghi lý do từ chối.
8. Soft-delete tài liệu nếu được duyệt.
9. Gửi notification cho tác giả sau khi transaction hoàn tất.

## 6. Routes và phân quyền

File:

```text
routes/documents.php
```

### Route tác giả

```text
POST /tai-lieu/{id}/yeu-cau-go-bo
documents.request-removal
```

Được bảo vệ bởi:

```text
auth, verified
```

### Route Moderator/Admin

```text
GET  /quan-tri/yeu-cau-go-tai-lieu
GET  /quan-tri/yeu-cau-go-tai-lieu/{id}
PUT  /quan-tri/yeu-cau-go-tai-lieu/{id}/quyet-dinh
```

Được bảo vệ bởi:

```text
auth, role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN
```

## 7. Notification

### 7.1. Notification yêu cầu mới

File:

```text
app/Notifications/DocumentRemovalRequestSubmittedNotification.php
```

Gửi đến:

- Moderator.
- Admin.

Thông tin gồm:

- Tiêu đề thông báo.
- Tên tài liệu.
- ID yêu cầu.
- ID tài liệu.

### 7.2. Notification kết quả xử lý

File:

```text
app/Notifications/DocumentRemovalRequestResolvedNotification.php
```

Gửi đến tác giả sau khi yêu cầu được xử lý.

Nội dung khác nhau theo kết quả:

- Duyệt: thông báo tài liệu đã được gỡ khỏi kho cộng đồng.
- Từ chối: thông báo lý do từ chối.

Notification sử dụng kênh database hiện có của hệ thống.

## 8. Frontend quản trị

### 8.1. Danh sách yêu cầu

File:

```text
resources/js/Pages/Admin/Documents/RemovalRequests/Index.jsx
```

Chức năng:

- Hiển thị tổng số yêu cầu.
- Lọc theo trạng thái.
- Hiển thị tài liệu.
- Hiển thị tác giả.
- Hiển thị số lượt tải.
- Hiển thị lý do yêu cầu.
- Link sang trang chi tiết.
- Phân trang.

Badge trạng thái:

| Trạng thái | Màu |
|---|---|
| `DA_DUYET` | Xanh lá |
| `TU_CHOI` | Đỏ |
| `CHO_XU_LY` | Vàng |

### 8.2. Chi tiết và xử lý

File:

```text
resources/js/Pages/Admin/Documents/RemovalRequests/Show.jsx
```

Chức năng:

- Xem đầy đủ thông tin tài liệu.
- Xem tác giả và người gửi.
- Xem số lượt tải so với ngưỡng.
- Xem lý do tác giả yêu cầu gỡ.
- Nhập lý do quyết định.
- Duyệt và gỡ mềm.
- Từ chối yêu cầu.
- Chặn thao tác từ frontend nếu từ chối nhưng không có lý do.
- Hiển thị trạng thái đã xử lý.

Ô nhập lý do được chuẩn hóa:

- Viền 1px.
- Focus viền xanh AGU.
- Ring nhẹ khi focus.
- Con trỏ nhập liệu hiển thị rõ.
- Có thể kéo giãn chiều cao.

## 9. Menu Admin và UI dùng chung

### 9.1. Menu sidebar

File:

```text
resources/js/Layouts/AdminLayout.jsx
```

Đã thêm menu:

```text
Yêu cầu gỡ tài liệu
```

Menu hiển thị cho cả Moderator và Admin.

Đã sửa layout sidebar:

- Khu vực menu có thể cuộn độc lập.
- Footer chứa nút “Về Cổng Sinh Viên” và “Đăng xuất” không bị che.
- Footer sử dụng `shrink-0`.
- Sidebar sử dụng `overflow-hidden`.

### 9.2. Thanh cuộn sidebar

File:

```text
resources/css/app.css
```

Đã bổ sung class:

```css
.admin-sidebar-scroll
```

Đặc điểm:

- Thanh cuộn mảnh.
- Nền trong suốt.
- Thumb bo tròn.
- Màu slate đồng bộ với sidebar.
- Hover đậm hơn.
- Có hỗ trợ cả Firefox và Chrome/Edge.

### 9.3. Quy tắc form dùng chung

File:

```text
resources/css/app.css
```

Trong `@layer base`, các thẻ `input`, `textarea`, `select` được chuẩn hóa:

- Border mặc định 1px.
- Background trắng.
- Màu chữ theo token hệ thống.
- Caret theo màu chữ.
- Focus border màu brand primary.
- Focus shadow nhẹ.
- Tắt outline mặc định của trình duyệt.

Mục tiêu là tránh lỗi viền đen dày và con trỏ khó nhận biết trên các form mới.

## 10. Tài liệu kỹ thuật đã cập nhật

File:

```text
.md/TaiLieuKyThuat.md
```

Đã bổ sung mô tả:

- Endpoint gửi yêu cầu gỡ.
- Ngưỡng Community Ownership.
- Hàng đợi Moderator/Admin.
- Quy trình duyệt và từ chối.
- Soft-delete khi duyệt.
- Lưu người xử lý và thời điểm.
- Gửi thông báo cho tác giả.

## 11. Kiểm thử

File:

```text
tests/Feature/DocumentRemovalRequestTest.php
```

Các case đã kiểm thử:

1. Tác giả không thể gửi yêu cầu khi tài liệu mới có 9 lượt tải.
2. Tác giả gửi yêu cầu hợp lệ khi tài liệu đạt 10 lượt tải.
3. Moderator nhận notification khi có yêu cầu mới.
4. Admin duyệt yêu cầu và tài liệu bị soft-delete.
5. Từ chối không có lý do bị validation error.
6. Từ chối có lý do ghi nhận đúng trạng thái, người xử lý, thời điểm và lý do.
7. Tác giả nhận notification khi yêu cầu được xử lý.

Kết quả targeted test:

```text
4 tests passed
16 assertions
```

Các lệnh xác nhận:

```bash
php artisan test tests/Feature/DocumentRemovalRequestTest.php
vendor/bin/pint.bat --test ...
npm.cmd run build
```

Frontend production build đã thành công.

Full test suite hiện còn một số test auth/profile cũ tham chiếu `App\Models\User` và `/login`, trong khi codebase hiện dùng `NguoiDung` và luồng xác thực riêng. Các lỗi này tồn tại ngoài phạm vi Task 7.

## 12. Dữ liệu demo phục vụ kiểm thử giao diện

Đã tạo 5 yêu cầu demo trong database:

| ID | Trạng thái | Lượt tải |
|---:|---|---:|
| 1 | `CHO_XU_LY` | 10 |
| 2 | `CHO_XU_LY` | 17 |
| 3 | `CHO_XU_LY` | 24 |
| 4 | `TU_CHOI` | 31 |
| 5 | `DA_DUYET` | 38 |

Có thể mở giao diện tại:

```text
/quan-tri/yeu-cau-go-tai-lieu
```

## 13. Danh sách file

### File mới

- `app/Http/Controllers/DocumentRemovalRequestController.php`
- `app/Models/YeuCauGoBoTaiLieu.php`
- `app/Notifications/DocumentRemovalRequestSubmittedNotification.php`
- `app/Notifications/DocumentRemovalRequestResolvedNotification.php`
- `config/documents.php`
- `database/migrations/2026_10_30_000002_create_yeu_cau_go_bo_tai_lieu_table.php`
- `resources/js/Pages/Admin/Documents/RemovalRequests/Index.jsx`
- `resources/js/Pages/Admin/Documents/RemovalRequests/Show.jsx`
- `tests/Feature/DocumentRemovalRequestTest.php`

### File đã cập nhật

- `app/Http/Controllers/DocumentController.php`
- `app/Models/NguoiDung.php`
- `app/Models/TaiLieu.php`
- `resources/css/app.css`
- `resources/js/Layouts/AdminLayout.jsx`
- `routes/documents.php`
- `.md/TaiLieuKyThuat.md`

## 14. Kết luận

Task 7 đã hoàn thiện quy trình quản lý yêu cầu gỡ tài liệu thuộc quyền sở hữu cộng đồng từ lúc tác giả gửi yêu cầu đến khi Moderator/Admin ra quyết định.

Giải pháp bảo đảm:

- Tài liệu đạt giá trị cộng đồng không bị tác giả xóa trực tiếp.
- Mọi quyết định gỡ đều đi qua hàng đợi xét duyệt.
- Có kiểm tra điều kiện ở cả lúc gửi và lúc duyệt.
- Có lưu vết người xử lý, thời gian và lý do.
- Có thông báo hai chiều cho các bên liên quan.
- Tài liệu chỉ bị soft-delete, vẫn có thể truy xuất và khôi phục theo quyền quản trị.
