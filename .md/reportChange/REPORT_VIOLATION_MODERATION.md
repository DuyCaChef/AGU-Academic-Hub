# Báo cáo hoàn thiện hàng đợi báo cáo vi phạm

## 1. Tổng quan

Task này hoàn thiện luồng quản trị báo cáo vi phạm tài liệu cho Admin/Moderator. Trước khi triển khai, màn hình `Reports/Index.jsx` mới chỉ hiển thị danh sách, chưa có màn hình chi tiết, bộ lọc, thao tác xử lý thực tế, audit và notification.

Phạm vi đã thực hiện:

- Xem danh sách báo cáo.
- Lọc theo trạng thái, lý do và thời gian.
- Xem chi tiết báo cáo.
- Xem tài liệu bị báo cáo và người đăng.
- Xem người gửi báo cáo.
- Xem các báo cáo khác cùng tài liệu.
- Bác bỏ báo cáo.
- Tạm ẩn tài liệu để kiểm tra.
- Xóa mềm tài liệu.
- Ghi nhận lý do xử lý.
- Ghi nhận người xử lý và thời điểm xử lý.
- Thông báo kết quả cho người đăng và người báo cáo.
- Bổ sung dữ liệu mẫu để kiểm thử trên giao diện.
- Chuẩn hóa URL khu vực quản trị theo quy ước `/quan-tri`.

## 2. Quy ước trạng thái

### 2.1. Trạng thái báo cáo

| Giá trị database | Nhãn giao diện | Ý nghĩa |
|---|---|---|
| `CHO_XU_LY` | Chờ xử lý | Báo cáo chưa có quyết định cuối cùng |
| `DA_XU_LY` | Đã xử lý | Báo cáo dẫn đến thao tác ẩn hoặc xóa mềm tài liệu |
| `BAC_BO` | Bác bỏ | Báo cáo không đủ căn cứ; tài liệu được giữ nguyên trong quyết định bác bỏ |

Màu giao diện:

- `CHO_XU_LY`: vàng.
- `DA_XU_LY`: xanh lá.
- `BAC_BO`: xám.

### 2.2. Trạng thái tài liệu

| Giá trị | Ý nghĩa |
|---|---|
| `DA_DUYET` | Tài liệu đang được hiển thị bình thường |
| `TAM_AN_KIEM_TRA` | Tài liệu tạm ẩn để kiểm tra |
| `TU_CHOI` | Tài liệu bị từ chối theo nghiệp vụ kiểm duyệt khác |
| `ngay_xoa_tam` khác `NULL` | Tài liệu bị xóa mềm |

## 3. Ý nghĩa ba thao tác xử lý

### 3.1. Tạm ẩn tài liệu để kiểm tra

Action gửi lên backend:

```text
HIDE
```

Kết quả:

- Cập nhật `tai_lieu.trang_thai` thành `TAM_AN_KIEM_TRA`.
- Tài liệu không còn được xem như tài liệu công khai bình thường.
- Không xóa bản ghi tài liệu.
- Các báo cáo đang `CHO_XU_LY` cùng tài liệu được chuyển sang trạng thái xử lý tương ứng.

### 3.2. Gỡ tài liệu khỏi hệ thống

Action gửi lên backend:

```text
DELETE
```

Kết quả:

- Gọi Soft Delete trên model `TaiLieu`.
- Ghi thời điểm vào trường tùy biến `ngay_xoa_tam`.
- Tài liệu không còn xuất hiện trong truy vấn bình thường.
- Dữ liệu vẫn còn trong database và có thể khôi phục từ thùng rác.
- Các báo cáo đang `CHO_XU_LY` cùng tài liệu được đóng.

Đây là xóa mềm, không phải xóa vĩnh viễn.

### 3.3. Bác bỏ báo cáo, giữ tài liệu

Action gửi lên backend:

```text
DISMISS
```

Kết quả:

- Chỉ báo cáo hiện tại chuyển thành `BAC_BO`.
- Tài liệu không bị ẩn hoặc xóa bởi thao tác này.
- Các báo cáo liên quan khác đang chờ không bị tự động bác bỏ.
- Nếu tài liệu đã bị ẩn hoặc xóa mềm từ quyết định trước đó, thao tác bác bỏ không tự khôi phục tài liệu.

## 4. Luồng nghiệp vụ chính

### 4.1. Mở hàng đợi

1. Người dùng đăng nhập bằng vai trò `KIEM_DUYET_VIEN` hoặc `QUAN_TRI_VIEN`.
2. Truy cập:

   ```text
   /quan-tri/bao-cao
   ```

3. Middleware `auth` kiểm tra đăng nhập.
4. Middleware `role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN` kiểm tra quyền.
5. Controller truy vấn báo cáo kèm tài liệu và người báo cáo.
6. Inertia render trang `Admin/Reports/Index`.

Người dùng thông thường bị từ chối với HTTP 403. Người chưa đăng nhập được chuyển đến trang đăng nhập.

### 4.2. Lọc danh sách

Các tham số hỗ trợ:

| Tham số | Ý nghĩa |
|---|---|
| `status` | Lọc `trang_thai_xu_ly` |
| `reason` | Lọc `ly_do` |
| `from` | Ngày bắt đầu, lọc theo `created_at` |
| `to` | Ngày kết thúc, lọc theo `created_at` |

Giao diện đã phân biệt rõ:

- `Từ ngày`.
- `Đến ngày`.

Kết quả được phân trang 15 bản ghi và giữ lại query filter khi chuyển trang.

### 4.3. Xem chi tiết

Từ danh sách, người dùng nhấn `Xem chi tiết` và truy cập:

```text
/quan-tri/bao-cao/{id}
```

Trang chi tiết hiển thị:

- Mã báo cáo.
- Tài liệu bị báo cáo.
- Người đăng tài liệu.
- Người gửi báo cáo.
- Lý do báo cáo.
- Nội dung chi tiết vi phạm.
- Link xem tài liệu.
- Các báo cáo khác cùng `ma_tai_lieu`.
- Người xử lý và thời điểm xử lý nếu đã có.

Tài liệu được truy vấn bằng `withTrashed()` để trang chi tiết vẫn xem được khi tài liệu đã bị xóa mềm.

### 4.4. Xử lý báo cáo

Request:

```text
PUT /quan-tri/bao-cao/{id}/giai-quyet
```

Payload:

```json
{
  "action": "HIDE|DELETE|DISMISS",
  "resolution_reason": "Lý do và căn cứ xử lý"
}
```

Backend validation:

- `action` bắt buộc và chỉ nhận `HIDE`, `DELETE`, `DISMISS`.
- `resolution_reason` bắt buộc.
- `resolution_reason` là chuỗi.
- Độ dài tối đa 2.000 ký tự.

Toàn bộ cập nhật được thực hiện trong `DB::transaction()`. Nếu có lỗi trong quá trình xử lý, các cập nhật trước đó được rollback.

### 4.5. Xử lý lại báo cáo đã có kết quả

Theo quyết định nghiệp vụ hiện tại, báo cáo đã `DA_XU_LY` hoặc `BAC_BO` vẫn có thể mở lại và thay đổi quyết định.

Giao diện hiển thị cảnh báo:

```text
Báo cáo đã có kết quả. Thao tác mới sẽ thay thế quyết định trước đó.
```

Khi xử lý lại:

- `trang_thai_xu_ly` được cập nhật theo action mới.
- `ma_nguoi_xu_ly` được cập nhật theo người thao tác hiện tại.
- `ly_do_xu_ly` được cập nhật theo lý do mới.
- `xu_ly_luc` được cập nhật theo thời điểm mới.

Hiện tại bảng báo cáo chỉ lưu quyết định mới nhất, chưa lưu lịch sử nhiều lần xử lý. Nếu cần audit đầy đủ từng lần thay đổi, cần bổ sung bảng lịch sử xử lý riêng.

## 5. Audit và dữ liệu lưu trữ

Migration bổ sung ba trường vào `bao_cao_vi_pham`:

| Trường | Kiểu | Ý nghĩa |
|---|---|---|
| `ma_nguoi_xu_ly` | foreign key nullable | Người thực hiện quyết định |
| `ly_do_xu_ly` | text nullable | Lý do/căn cứ xử lý |
| `xu_ly_luc` | timestamp nullable | Thời điểm xử lý |

`ma_nguoi_xu_ly` tham chiếu đến `nguoi_dung.ma_nguoi_dung` và dùng `nullOnDelete()`.

Model `BaoCaoViPham` đã bổ sung:

- Các trường mới vào `$fillable`.
- Cast `xu_ly_luc` thành datetime.
- Quan hệ `nguoiXuLy()`.
- Quan hệ `baoCaoLienQuan()`.

Người xử lý luôn lấy từ:

```php
$request->user()->getKey()
```

Client không thể tự truyền người xử lý để giả mạo audit.

## 6. Xử lý báo cáo liên quan

Các báo cáo liên quan được xác định theo:

```text
ma_tai_lieu
```

Quy tắc:

- `HIDE`: đóng tất cả báo cáo đang `CHO_XU_LY` của cùng tài liệu.
- `DELETE`: đóng tất cả báo cáo đang `CHO_XU_LY` của cùng tài liệu.
- `DISMISS`: chỉ đóng báo cáo hiện tại.

Báo cáo đã có trạng thái khác `CHO_XU_LY` không bị cập nhật khi xử lý nhóm báo cáo liên quan.

## 7. Notification

Khi xử lý báo cáo, hệ thống gửi `ViolationReportResolvedNotification` qua kênh `database`.

Đối tượng nhận:

- Người đăng tài liệu.
- Người gửi các báo cáo được xử lý.

Danh sách người nhận được lọc bỏ null và loại trùng theo `ma_nguoi_dung`.

Payload notification gồm:

```json
{
  "title": "Báo cáo vi phạm đã được xử lý",
  "message": "...",
  "report_id": 123,
  "document_id": 456,
  "action": "ẩn để kiểm tra|xóa mềm|bác bỏ"
}
```

## 8. Giao diện đã triển khai

### 8.1. Danh sách báo cáo

File: [resources/js/Pages/Admin/Reports/Index.jsx](resources/js/Pages/Admin/Reports/Index.jsx)

Đã bổ sung:

- Header hàng đợi.
- Tổng số báo cáo.
- Bộ lọc trạng thái.
- Bộ lọc lý do.
- Bộ lọc `Từ ngày`.
- Bộ lọc `Đến ngày`.
- Nút lọc.
- Bảng danh sách.
- Link xem chi tiết.
- Phân trang.
- Trạng thái rỗng.
- Badge màu theo trạng thái.

### 8.2. Chi tiết báo cáo

File: [resources/js/Pages/Admin/Reports/Show.jsx](resources/js/Pages/Admin/Reports/Show.jsx)

Đã bổ sung:

- Thông tin báo cáo.
- Thông tin tài liệu.
- Người đăng.
- Người báo cáo.
- Nội dung vi phạm.
- Link xem tài liệu.
- Danh sách báo cáo liên quan.
- Ô nhập lý do xử lý.
- Giới hạn 2.000 ký tự.
- Bộ đếm ký tự.
- Lỗi validation inline.
- Trạng thái focus và lỗi rõ ràng.
- Ba nút xử lý.
- Cảnh báo khi báo cáo đã có quyết định trước đó.

Tên nút hiện tại:

- `Tạm ẩn tài liệu để kiểm tra`.
- `Gỡ tài liệu khỏi hệ thống`.
- `Bác bỏ báo cáo, giữ tài liệu`.

Phần mô tả dài bên dưới từng nút đã được loại bỏ để giao diện gọn hơn.

### 8.3. Sidebar quản trị

File: [resources/js/Layouts/AdminLayout.jsx](resources/js/Layouts/AdminLayout.jsx)

Đã cập nhật fallback URL:

- Hàng đợi báo cáo: `/quan-tri/bao-cao`.
- Thùng rác tài liệu: `/quan-tri/tai-lieu/da-xoa`.

## 9. Route và URL

File: [routes/documents.php](routes/documents.php)

Các route quản trị báo cáo:

| Method | URL | Route name | Chức năng |
|---|---|---|---|
| GET | `/quan-tri/bao-cao` | `admin.reports.index` | Danh sách/lọc |
| GET | `/quan-tri/bao-cao/{id}` | `admin.reports.show` | Chi tiết |
| PUT | `/quan-tri/bao-cao/{id}/giai-quyet` | `admin.reports.resolve` | Xử lý |

Các route tài liệu liên quan:

| Method | URL | Route name | Chức năng |
|---|---|---|---|
| GET | `/quan-tri/tai-lieu/da-xoa` | `admin.documents.deleted` | Danh sách tài liệu đã xóa mềm |
| POST | `/quan-tri/tai-lieu/{id}/khoi-phuc` | `admin.documents.restore` | Khôi phục tài liệu |

Tên route nội bộ vẫn giữ prefix `admin.*` để không phá vỡ các lệnh `route(...)` đang dùng trong React. URL thực tế đã chuẩn hóa theo quy ước `/quan-tri`.

File [routes/kyc.php](routes/kyc.php) cũng được chuẩn hóa route redirect phụ từ `/admin/kyc` sang `/quan-tri/kyc`.

## 10. Dữ liệu mẫu kiểm thử

File: [database/seeders/BaoCaoViPhamSeeder.php](database/seeders/BaoCaoViPhamSeeder.php)

Seeder tạo:

- Ba tài khoản người gửi báo cáo:
  - `baocao1@agu.edu.vn`
  - `baocao2@agu.edu.vn`
  - `baocao3@agu.edu.vn`
- Mật khẩu chung: `Demo@123456`.
- Năm báo cáo mẫu.
- Nhiều lý do:
  - `BAN_QUYEN`
  - `SAI_NOI_DUNG`
  - `TRUNG_LAP`
  - `MA_DOC`
  - `KHAC`
- Dữ liệu ngày cách nhau 0, 2, 5, 8 và 12 ngày.
- Nhiều báo cáo trên cùng tài liệu để kiểm tra nhóm liên quan.

Seeder dùng `updateOrInsert` cho tài khoản và kiểm tra bản ghi trùng trước khi thêm báo cáo, nên có thể chạy lại mà không tạo trùng cùng tài liệu, người báo cáo và lý do.

Đã đăng ký seeder trong:

File: [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php)

Chạy riêng trong Git Bash:

```bash
php artisan db:seed --class='Database\Seeders\BaoCaoViPhamSeeder'
```

Lưu ý phải đặt namespace trong dấu nháy đơn để Git Bash không làm mất dấu `\`.

Tài khoản Moderator mẫu:

```text
Email: kiemduyet@agu.edu.vn
Mật khẩu: Demo@123456
```

## 11. Danh sách file đã thực hiện

### File mới

| File | Vai trò |
|---|---|
| [app/Notifications/ViolationReportResolvedNotification.php](app/Notifications/ViolationReportResolvedNotification.php) | Notification kết quả xử lý |
| [database/migrations/2026_10_29_000004_add_moderation_resolution_fields_to_bao_cao_vi_pham.php](database/migrations/2026_10_29_000004_add_moderation_resolution_fields_to_bao_cao_vi_pham.php) | Bổ sung audit fields |
| [database/seeders/BaoCaoViPhamSeeder.php](database/seeders/BaoCaoViPhamSeeder.php) | Dữ liệu mẫu báo cáo |
| [resources/js/Pages/Admin/Reports/Show.jsx](resources/js/Pages/Admin/Reports/Show.jsx) | Màn hình chi tiết và xử lý |

### File cập nhật

| File | Thay đổi |
|---|---|
| [app/Http/Controllers/DocumentModerationController.php](app/Http/Controllers/DocumentModerationController.php) | Danh sách, lọc, chi tiết, xử lý, notification, soft delete/restore |
| [app/Models/BaoCaoViPham.php](app/Models/BaoCaoViPham.php) | Fillable, cast và quan hệ |
| [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php) | Đăng ký report seeder |
| [resources/js/Layouts/AdminLayout.jsx](resources/js/Layouts/AdminLayout.jsx) | Chuẩn hóa fallback URL |
| [resources/js/Pages/Admin/Reports/Index.jsx](resources/js/Pages/Admin/Reports/Index.jsx) | Hoàn thiện danh sách, lọc, badge trạng thái |
| [routes/documents.php](routes/documents.php) | Route quản trị báo cáo và tài liệu |
| [routes/kyc.php](routes/kyc.php) | Chuẩn hóa route phụ KYC |

## 12. Kiểm thử đã thực hiện

### Đã đạt

```text
php -l app/Http/Controllers/DocumentModerationController.php
php -l app/Models/BaoCaoViPham.php
php -l app/Notifications/ViolationReportResolvedNotification.php
php -l database/seeders/BaoCaoViPhamSeeder.php
php artisan migrate --pretend
php artisan route:list --name=admin.reports
npm.cmd run build
vendor/bin/pint --test ...
git diff --check
```

Kết quả:

- PHP syntax không lỗi.
- Migration giả lập thành công.
- Route báo cáo tồn tại đúng URL `/quan-tri`.
- Frontend build thành công.
- Pint kiểm tra các file PHP thay đổi thành công.
- Seeder chạy thành công và tạo 5 báo cáo mẫu.

### Test suite hiện có

Đã chạy `php artisan test`. Một số test authentication/profile có lỗi có sẵn do project hiện tại thiếu route `/login` và model `app/Models/User.php`; các lỗi này không phát sinh từ task hàng đợi báo cáo.

Chưa có feature test chuyên biệt cho `DocumentModerationController`. Nên bổ sung test tự động cho:

- Quyền truy cập Moderator/Admin.
- Bộ lọc.
- Xem chi tiết.
- Bác bỏ.
- Ẩn tài liệu.
- Xóa mềm.
- Xử lý báo cáo liên quan.
- Audit người xử lý/thời điểm/lý do.
- Notification.
- Xử lý lại báo cáo đã có kết quả.

## 13. Hướng dẫn nghiệm thu trên giao diện

1. Chạy migration:

   ```bash
   php artisan migrate
   ```

2. Tạo dữ liệu mẫu:

   ```bash
   php artisan db:seed --class='Database\Seeders\BaoCaoViPhamSeeder'
   ```

3. Khởi động ứng dụng:

   ```bash
   php artisan serve
   npm.cmd run dev
   ```

4. Đăng nhập:

   ```text
   Email: kiemduyet@agu.edu.vn
   Mật khẩu: Demo@123456
   ```

5. Mở:

   ```text
   http://localhost:8000/quan-tri/bao-cao
   ```

6. Kiểm tra lần lượt:

   - Năm báo cáo mẫu.
   - Bộ lọc trạng thái.
   - Bộ lọc lý do.
   - Bộ lọc `Từ ngày`/`Đến ngày`.
   - Trang chi tiết.
   - Báo cáo liên quan.
   - Thiếu lý do xử lý.
   - Tạm ẩn tài liệu.
   - Gỡ tài liệu khỏi hệ thống.
   - Bác bỏ báo cáo, giữ tài liệu.
   - Mở lại báo cáo đã xử lý và đổi quyết định.

## 14. Lưu ý và hướng phát triển tiếp theo

- Bảng `bao_cao_vi_pham` hiện lưu quyết định mới nhất, chưa lưu lịch sử từng lần xử lý. Nếu yêu cầu audit pháp lý hoặc truy vết đầy đủ, nên thêm bảng `bao_cao_vi_pham_lich_su_xu_ly`.
- Notification đã được ghi vào database, nhưng giao diện trung tâm notification cho người dùng chưa nằm trong phạm vi task này.
- Nên bổ sung feature test chuyên biệt trước khi đưa vào production.
- Nên kiểm tra lại controller `deletedDocuments()` để dùng đúng tên cột soft delete tùy biến `ngay_xoa_tam` khi sắp xếp danh sách thùng rác.

## 15. Lưu trữ báo cáo đã hoàn tất

Báo cáo `DA_XU_LY` hoặc `BAC_BO` có thể được lưu trữ khỏi hàng đợi bằng icon thùng rác. Đây là soft delete báo cáo, không phải xóa vĩnh viễn.

- Báo cáo `CHO_XU_LY` không hiển thị icon lưu trữ.
- Giao diện yêu cầu xác nhận trước khi lưu trữ.
- Backend kiểm tra lại trạng thái, nên không thể gọi API để lưu trữ báo cáo chưa xử lý.
- Dữ liệu audit, tài liệu và notification không bị thay đổi.
- Báo cáo đã lưu trữ không còn xuất hiện trong danh sách mặc định.
- Có thể truy vấn bằng `BaoCaoViPham::onlyTrashed()` khi cần xây dựng màn hình lịch sử.

Route:

```text
DELETE /quan-tri/bao-cao/{id}
```

Route name:

```text
admin.reports.archive
```

Migration bổ sung trường `ngay_xoa_tam` và model `BaoCaoViPham` dùng `SoftDeletes`.
