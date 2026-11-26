# Báo cáo triển khai Task 8: Quản lý thùng rác và khôi phục dữ liệu

## 1. Tổng quan

Task này mở rộng chức năng xóa mềm tài liệu thành một quy trình quản trị đầy đủ:

- Quản lý danh sách tài liệu đã xóa mềm.
- Lưu và hiển thị người thực hiện, lý do và nguồn xóa.
- Phân biệt xóa bởi tác giả, Moderator, Admin hoặc hệ thống.
- Lọc tài liệu trong thùng rác.
- Khôi phục có kiểm tra phiên bản và báo cáo vi phạm.
- Bảo toàn bản ghi và file vật lý tối thiểu 60 ngày; nếu không có thao tác thủ công, hệ thống giữ đến 90 ngày.
- Từ ngày thứ 60, Admin được phép xóa vĩnh viễn thủ công.
- Từ ngày thứ 90, hệ thống tự động xóa vĩnh viễn.
- Ghi audit log cho mọi thao tác xóa mềm, khôi phục và xóa vĩnh viễn.

## 2. Phạm vi quyền

| Tác nhân | Quyền |
|---|---|
| Tác giả | Xóa mềm tài liệu của chính mình nếu chưa có lượt tải và còn trong 24 giờ đầu |
| Moderator (`KIEM_DUYET_VIEN`) | Xem thùng rác, lọc dữ liệu, khôi phục tài liệu, xóa mềm từ luồng kiểm duyệt |
| Admin (`QUAN_TRI_VIEN`) | Toàn bộ quyền Moderator và xóa vĩnh viễn tài liệu từ ngày thứ 60 |
| Hệ thống | Tự động xóa vĩnh viễn tài liệu từ ngày thứ 90 qua scheduler |

## 3. Các file đã thay đổi và chức năng

### 3.1. Controller tài liệu

**File:** `app/Http/Controllers/DocumentController.php`

Đã bổ sung luồng tác giả tự xóa tài liệu:

- Validate lý do xóa.
- Chỉ cho phép tác giả sở hữu tài liệu thực hiện.
- Từ chối nếu tài liệu đã có lượt tải.
- Từ chối nếu tài liệu đã quá 24 giờ kể từ lúc tạo.
- Gán metadata:
  - `ma_nguoi_xoa`
  - `ly_do_xoa`
  - `nguon_xoa = TAC_GIA`
  - `han_luu_thung_rac = hiện tại + 60 ngày` (mốc Admin được xóa thủ công)
- Thực hiện soft delete trong transaction.
- Ghi log `SOFT_DELETE`.

### 3.2. Controller kiểm duyệt và thùng rác

**File:** `app/Http/Controllers/DocumentModerationController.php`

#### Danh sách tài liệu đã xóa

Method `deletedDocuments` hỗ trợ:

- Chỉ lấy tài liệu soft deleted bằng `onlyTrashed()`.
- Hiển thị:
  - Tác giả.
  - Người xóa.
  - Môn học.
  - Lý do xóa.
  - Nguồn xóa.
  - Thời gian xóa.
  - Trạng thái tài liệu.
- Phân trang và giữ query filter.
- Bộ lọc:
  - Từ ngày xóa.
  - Đến ngày xóa.
  - Người xóa.
  - Nguồn xóa.
  - Trạng thái tài liệu.

#### Khôi phục tài liệu

Method `restoreDocument` thực hiện:

1. Khóa bản ghi bằng `lockForUpdate`.
2. Tải quan hệ phiên bản gốc và các phiên bản liên quan.
3. Từ chối nếu phiên bản gốc không còn tồn tại.
4. Kiểm tra báo cáo vi phạm đang ở trạng thái `CHO_XU_LY`.
5. Yêu cầu xác nhận bổ sung nếu tài liệu đang có báo cáo vi phạm.
6. Khôi phục tài liệu:
   - `DA_DUYET` nếu không có báo cáo chờ xử lý.
   - `CHO_KIEM_TRA` nếu vẫn có báo cáo chờ xử lý.
7. Ghi log `RESTORE`.

#### Xóa vĩnh viễn thủ công

Method `permanentlyDeleteDocument`:

- Chỉ Admin được phép thực hiện.
- Từ chối nếu tài liệu chưa hết hạn lưu trữ.
- Ghi log `PERMANENT_DELETE`.
- Xóa file vật lý khỏi disk `local`.
- Gọi `forceDelete()` để xóa bản ghi vĩnh viễn.

#### Ghi nhận xóa mềm từ kiểm duyệt

Helper `softDeleteDocument` được sử dụng cho các quyết định xóa từ luồng Moderator/Admin:

- Ghi đúng nguồn `MODERATOR` hoặc `ADMIN`.
- Ghi người xóa và lý do.
- Đặt mốc Admin được xóa thủ công là 60 ngày.
- Ghi audit log `SOFT_DELETE`.

### 3.3. Controller yêu cầu gỡ tài liệu

**File:** `app/Http/Controllers/DocumentRemovalRequestController.php`

Khi Admin duyệt yêu cầu gỡ tài liệu thuộc sở hữu cộng đồng:

- Tài liệu được đánh dấu người xóa là người xử lý yêu cầu.
- Nguồn xóa là `TAC_GIA`.
- Lý do xóa lấy từ nội dung yêu cầu gỡ.
- Đặt mốc Admin được xóa thủ công là 60 ngày.
- Ghi audit log `SOFT_DELETE`.

Luồng thông báo kết quả cho tác giả vẫn được giữ nguyên.

### 3.4. Model tài liệu

**File:** `app/Models/TaiLieu.php`

Đã bổ sung các thuộc tính:

- `ma_nguoi_xoa`
- `ly_do_xoa`
- `nguon_xoa`
- `han_luu_thung_rac`

Đã bổ sung cast datetime cho:

- `ngay_xoa_tam`
- `han_luu_thung_rac`

Đã bổ sung quan hệ:

- `nguoiXoa()`
- `lichSuThungRacs()`

### 3.5. Model audit thùng rác

**File:** `app/Models/LichSuThungRac.php`

Model đại diện cho bảng `lich_su_thung_rac`.

Thông tin audit gồm:

- Mã tài liệu.
- Người thực hiện.
- Hành động.
- Nguồn thực hiện.
- Lý do.
- Chi tiết JSON bổ sung.
- Thời gian tạo log.

Các hành động hiện có:

- `SOFT_DELETE`
- `RESTORE`
- `PERMANENT_DELETE`

### 3.6. Migration metadata tài liệu

**File:** `database/migrations/2026_10_30_000003_add_trash_metadata_to_tai_lieu.php`

Đã thêm vào bảng `tai_lieu`:

| Cột | Ý nghĩa |
|---|---|
| `ma_nguoi_xoa` | Người thực hiện xóa |
| `ly_do_xoa` | Lý do xóa |
| `nguon_xoa` | `TAC_GIA`, `MODERATOR`, `ADMIN`, `SYSTEM` |
| `han_luu_thung_rac` | Thời điểm Admin được phép xóa vĩnh viễn thủ công, mặc định sau 60 ngày |

Có index hỗ trợ truy vấn theo thời gian xóa và người xóa.

### 3.7. Migration audit log

**File:** `database/migrations/2026_10_30_000004_create_lich_su_thung_rac_table.php`

Tạo bảng `lich_su_thung_rac` để lưu vết không phụ thuộc vòng đời bản ghi tài liệu.

Điểm quan trọng:

- Audit được ghi trước khi xóa vĩnh viễn.
- Log vẫn tồn tại sau khi tài liệu và file vật lý bị xóa.
- Có index theo tài liệu, hành động và thời gian.

### 3.8. Giao diện thùng rác

**File:** `resources/js/Pages/Admin/Documents/Deleted.jsx`

Đã bổ sung:

- Bộ lọc theo ngày.
- Bộ lọc theo người xóa.
- Bộ lọc theo nguồn xóa.
- Bộ lọc theo trạng thái.
- Cột hiển thị người xóa và lý do.
- Cảnh báo khi khôi phục tài liệu có báo cáo vi phạm.
- Nút xóa vĩnh viễn chỉ hiển thị cho Admin.
- Nút xóa vĩnh viễn chỉ hiển thị cho Admin từ ngày thứ 60.
- Nội dung giao diện nêu rõ hệ thống tự động xóa từ ngày thứ 90.
- Xác nhận trước khi khôi phục hoặc xóa vĩnh viễn.

### 3.9. Route quản trị

**File:** `routes/documents.php`

Các route liên quan:

| Method | URL | Route name | Chức năng |
|---|---|---|---|
| `GET` | `/quan-tri/tai-lieu/da-xoa` | `admin.documents.deleted` | Danh sách thùng rác |
| `POST` | `/quan-tri/tai-lieu/{id}/khoi-phuc` | `admin.documents.restore` | Khôi phục |
| `DELETE` | `/quan-tri/tai-lieu/{id}/xoa-vinh-vien` | `admin.documents.permanent-delete` | Xóa vĩnh viễn |

Các route được bảo vệ bởi middleware:

```text
auth
role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN
```

### 3.10. Command xóa quá hạn

**File:** `app/Console/Commands/PurgeExpiredDocuments.php`

Command:

```powershell
php artisan documents:purge-trash
```

Chức năng:

- Tìm tài liệu đã soft delete ít nhất 90 ngày.
- Hỗ trợ tùy chọn số ngày:

```powershell
php artisan documents:purge-trash --days=90
```

- Ghi audit `PERMANENT_DELETE` với nguồn `SYSTEM`.
- Xóa file vật lý.
- Xóa bản ghi bằng `forceDelete()`.
- Xử lý theo batch 100 bản ghi.
- Mỗi tài liệu được xử lý trong transaction riêng.

### 3.10.1. Cấu hình hai mốc thời gian

**File:** `config/documents.php`

```php
'trash_admin_delete_after_days' => 60,
'trash_auto_purge_after_days' => 90,
```

Có thể tùy chỉnh qua biến môi trường:

```env
DOCUMENT_TRASH_ADMIN_DELETE_AFTER_DAYS=60
DOCUMENT_TRASH_AUTO_PURGE_AFTER_DAYS=90
```

Luồng thời gian chính thức:

```text
Ngày xóa mềm
├── +60 ngày: Admin được xóa vĩnh viễn thủ công
└── +90 ngày: System tự động xóa vĩnh viễn
```

### 3.11. Scheduler

**File:** `routes/console.php`

Đã đăng ký chạy command hằng ngày:

```php
Schedule::command('documents:purge-trash')->daily();
```

Có thể chạy scheduler trong môi trường phát triển bằng:

```powershell
php artisan schedule:work
```

### 3.12. Test

**File:** `tests/Feature/DocumentRemovalRequestTest.php`

Đã bổ sung test cho:

1. Khôi phục tài liệu đang có báo cáo vi phạm chờ xử lý.
2. Yêu cầu xác nhận trước khi khôi phục tài liệu có vi phạm.
3. Khôi phục chuyển trạng thái sang `CHO_KIEM_TRA`.
4. Xóa vĩnh viễn tài liệu quá hạn.
5. Xóa file vật lý khi purge.
6. Ghi audit log cho thao tác xóa vĩnh viễn.
7. Ghi metadata và audit log khi tài liệu bị xóa.

## 4. Luồng nghiệp vụ tổng quát

### 4.1. Xóa mềm

```text
Người dùng/Moderator/Admin yêu cầu xóa
        |
        v
Kiểm tra quyền và điều kiện
        |
        v
Lưu người xóa, lý do, nguồn, mốc Admin xóa thủ công sau 60 ngày
        |
        v
Soft delete tài liệu
        |
        v
Ghi audit SOFT_DELETE
```

### 4.2. Khôi phục

```text
Mở thùng rác
        |
        v
Chọn tài liệu và xác nhận
        |
        v
Kiểm tra phiên bản gốc
        |
        v
Kiểm tra báo cáo vi phạm đang chờ xử lý
        |
        +--> Có vi phạm: yêu cầu xác nhận, khôi phục vào CHO_KIEM_TRA
        |
        +--> Không có vi phạm: khôi phục vào DA_DUYET
        |
        v
Ghi audit RESTORE
```

### 4.3. Xóa vĩnh viễn

```text
Tài liệu hết hạn tự động sau 90 ngày
        |
        +--> Admin thao tác thủ công
        |
        +--> Scheduler/System xử lý tự động
        |
        v
Ghi audit PERMANENT_DELETE
        |
        v
Xóa file vật lý
        |
        v
Force delete bản ghi
```

## 5. Kiểm thử đã thực hiện

### Test chức năng liên quan

```powershell
php artisan test tests/Feature/DocumentRemovalRequestTest.php
```

Kết quả:

```text
6 tests passed
27 assertions passed
```

### Kiểm tra cú pháp PHP

Đã kiểm tra các controller và command liên quan bằng `php -l`, không phát hiện lỗi cú pháp.

### Build frontend

```powershell
npm.cmd run build
```

Build Vite thành công.

### Kiểm tra route

```powershell
php artisan route:list --name=admin.documents
```

Các route thùng rác, khôi phục và xóa vĩnh viễn được đăng ký thành công.

### Full test suite

Full test suite còn một số lỗi có sẵn ở nhóm Authentication/Profile do test tham chiếu `App\Models\User` và route `/login`, trong khi codebase hiện dùng model/ngữ cảnh người dùng riêng. Các lỗi này không phát sinh từ Task 8.

## 6. Hướng dẫn triển khai sau khi nhận code

Chạy migration:

```powershell
php artisan migrate
```

Chạy frontend:

```powershell
npm.cmd run dev
```

Chạy scheduler trên server:

```powershell
php artisan schedule:work
```

Hoặc cấu hình cron/task scheduler của máy chủ để Laravel scheduler được gọi thường xuyên.

## 7. Các giá trị trạng thái và nguồn xóa

### Nguồn xóa

```text
TAC_GIA
MODERATOR
ADMIN
SYSTEM
```

### Trạng thái tài liệu được sử dụng khi khôi phục

```text
DA_DUYET
CHO_KIEM_TRA
```

### Hành động audit

```text
SOFT_DELETE
RESTORE
PERMANENT_DELETE
```

## 8. Kết luận

Task 8 đã được triển khai đầy đủ ở các lớp:

- Database.
- Model và quan hệ Eloquent.
- Controller và nghiệp vụ.
- Authorization thông qua middleware và kiểm tra Admin.
- Giao diện quản trị.
- Scheduler và command tự động.
- Audit log.
- Feature test.

Quy trình mới bảo đảm tài liệu không bị mất ngay lập tức, cho Admin 30 ngày xử lý thủ công từ mốc 60 đến 90 ngày, có thể truy nguyên người/lý do xóa, có kiểm soát khi khôi phục và có cơ chế tự động xóa vĩnh viễn sau 90 ngày.
