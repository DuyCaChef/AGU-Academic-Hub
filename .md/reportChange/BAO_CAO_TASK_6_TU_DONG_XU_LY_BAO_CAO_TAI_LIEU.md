# Báo cáo triển khai Task 6: Tự động xử lý tài liệu bị nhiều báo cáo

## 1. Mục tiêu

Triển khai cơ chế giám sát và xử lý tự động tài liệu bị báo cáo nhiều lần:

- Đếm số báo cáo hợp lệ trên từng tài liệu.
- Không cho một tài khoản báo cáo trùng cùng một tài liệu.
- Chỉ tính báo cáo từ tài khoản đạt mức uy tín yêu cầu.
- Tự động đưa tài liệu vào hàng đợi kiểm tra.
- Tự động ẩn tài liệu khi đạt ngưỡng nguy cơ cao.
- Thông báo cho Moderator/Admin.
- Cho phép Moderator/Admin xem nội dung, xem các báo cáo liên quan và đưa ra quyết định.
- Lưu lý do và thời điểm tài liệu được chuyển trạng thái tự động hoặc được xử lý thủ công.

## 2. Phạm vi đã hoàn thành

Task đã bổ sung đầy đủ các lớp:

1. **Database**: trường đếm báo cáo, lý do tự động, thời điểm tự động và ràng buộc chống trùng.
2. **Domain/service**: tính báo cáo hợp lệ, áp dụng ngưỡng và gửi thông báo.
3. **HTTP/controller**: nhận báo cáo, hàng đợi kiểm tra, xem chi tiết, xem trước tệp và xử lý quyết định.
4. **Route**: endpoint cho người dùng, Moderator và Admin.
5. **Frontend**: hàng đợi kiểm tra, trang chi tiết tài liệu, preview PDF và các nút quyết định.
6. **Test**: kiểm thử chống trùng, điểm uy tín, ngưỡng 3/5 và notification.

## 3. Quy tắc nghiệp vụ

### 3.1. Tài khoản uy tín

Mặc định tài khoản được xem là uy tín khi:

```text
diem_uy_tin >= 100
```

Ngưỡng được cấu hình tại `config/moderation.php` và có thể thay đổi bằng biến môi trường:

```env
MODERATION_TRUSTED_REPORTER_REPUTATION=100
```

### 3.2. Chống báo cáo trùng

Một tài khoản chỉ được gửi một báo cáo cho một tài liệu. Ràng buộc được áp dụng ở hai lớp:

- Service kiểm tra trước khi tạo báo cáo.
- Unique index database trên:

```text
(ma_tai_lieu, ma_nguoi_bao_cao)
```

Nếu gửi lại, hệ thống trả lỗi:

```text
Bạn đã báo cáo tài liệu này. Mỗi tài khoản chỉ được gửi một báo cáo cho một tài liệu.
```

### 3.3. Báo cáo hợp lệ

Một báo cáo được tính hợp lệ khi:

- Báo cáo chưa bị `BAC_BO`.
- Người báo cáo có `diem_uy_tin` đạt ngưỡng cấu hình.

Số báo cáo hợp lệ được lưu vào:

```text
tai_lieu.so_bao_cao_hop_le
```

### 3.4. Ngưỡng tự động

| Điều kiện | Trạng thái tài liệu | Ý nghĩa |
|---|---|---|
| Dưới 3 báo cáo hợp lệ | Giữ trạng thái hiện tại | Chưa đưa vào hàng đợi tự động |
| Từ 3 báo cáo hợp lệ | `CHO_KIEM_TRA` | Cần Moderator/Admin kiểm tra |
| Từ 5 báo cáo hợp lệ | `TAM_AN_KIEM_TRA` | Tự động ẩn do đạt ngưỡng nguy cơ |

Các ngưỡng được cấu hình tại:

```php
config('moderation.review_report_threshold')
config('moderation.auto_hide_report_threshold')
```

Có thể thay đổi bằng:

```env
MODERATION_REVIEW_REPORT_THRESHOLD=3
MODERATION_AUTO_HIDE_REPORT_THRESHOLD=5
```

## 4. Luồng nghiệp vụ hoàn chỉnh

```text
Người dùng gửi báo cáo
        ↓
Kiểm tra đăng nhập và dữ liệu đầu vào
        ↓
Kiểm tra tài khoản đã báo cáo tài liệu này chưa
        ↓
Tạo báo cáo ở trạng thái CHO_XU_LY
        ↓
Tính lại số báo cáo hợp lệ
        ↓
Nếu đạt 3: chuyển CHO_KIEM_TRA
        ↓
Nếu đạt 5: chuyển TAM_AN_KIEM_TRA
        ↓
Gửi notification cho Moderator/Admin
        ↓
Đưa tài liệu vào hàng đợi kiểm tra riêng
        ↓
Moderator/Admin xem báo cáo và nội dung tệp
        ↓
Giữ nguyên / Khôi phục / Ẩn / Xóa mềm
```

## 5. Các trạng thái chính

### 5.1. Trạng thái báo cáo

| Giá trị | Ý nghĩa |
|---|---|
| `CHO_XU_LY` | Báo cáo đang chờ xử lý |
| `DA_XU_LY` | Báo cáo đã được xử lý bằng quyết định ẩn hoặc xóa mềm |
| `BAC_BO` | Báo cáo bị bác bỏ và không còn được tính hợp lệ |

### 5.2. Trạng thái tài liệu

| Giá trị | Ý nghĩa |
|---|---|
| `DA_DUYET` | Tài liệu được hiển thị bình thường |
| `CHO_KIEM_TRA` | Đạt ngưỡng báo cáo, chờ Moderator/Admin kiểm tra |
| `TAM_AN_KIEM_TRA` | Tài liệu đang bị ẩn để kiểm tra hoặc đã tự động ẩn |
| `TU_CHOI` | Tài liệu bị từ chối theo nghiệp vụ kiểm duyệt khác |
| `ngay_xoa_tam != NULL` | Tài liệu bị xóa mềm |

## 6. Danh sách file đã thêm

### 6.1. `config/moderation.php`

File cấu hình:

- Điểm uy tín tối thiểu của người báo cáo.
- Ngưỡng đưa tài liệu vào hàng đợi.
- Ngưỡng tự động ẩn tài liệu.

### 6.2. `database/migrations/2026_10_30_000001_add_report_monitoring_fields_to_tai_lieu.php`

Bổ sung vào `tai_lieu`:

| Cột | Ý nghĩa |
|---|---|
| `so_bao_cao_hop_le` | Số báo cáo hợp lệ hiện tại |
| `ly_do_trang_thai` | Lý do tự động hoặc thủ công khi chuyển trạng thái |
| `tu_dong_chuyen_luc` | Thời điểm tài liệu được tự động chuyển trạng thái |

Bổ sung unique index vào `bao_cao_vi_pham`:

```text
bao_cao_tai_lieu_nguoi_duy_nhat
```

Migration cũng cập nhật enum `tai_lieu.trang_thai` để hỗ trợ:

```text
CHO_KIEM_TRA
```

### 6.3. `app/Shared/Services/DocumentReportService.php`

Service trung tâm của task.

Chức năng:

- Tạo báo cáo mới trong transaction.
- Khóa tài liệu khi tính toán để hạn chế race condition.
- Kiểm tra báo cáo trùng.
- Tính số báo cáo hợp lệ.
- Áp dụng ngưỡng 3/5.
- Lưu lý do và thời điểm tự động chuyển trạng thái.
- Gửi notification cho tài khoản Moderator/Admin.
- Tính lại số báo cáo sau khi báo cáo bị bác bỏ hoặc được xử lý.

### 6.4. `app/Notifications/DocumentReviewRequiredNotification.php`

Notification database gửi cho:

- Người có vai trò `KIEM_DUYET_VIEN`.
- Người có vai trò `QUAN_TRI_VIEN`.

Notification chứa:

- Tiêu đề.
- Tên tài liệu.
- Số báo cáo hợp lệ.
- Mã tài liệu.
- Thông tin tài liệu đã tự động ẩn hay chỉ chuyển chờ kiểm tra.

### 6.5. `resources/js/Pages/Admin/Documents/ReviewDetail.jsx`

Trang chi tiết kiểm tra tài liệu.

Hiển thị:

- Tiêu đề và thông tin tài liệu.
- Môn học, mã học phần và người đăng.
- Định dạng, dung lượng và số báo cáo hợp lệ.
- Danh sách tất cả báo cáo liên quan.
- Người gửi báo cáo.
- Lý do và mô tả chi tiết báo cáo.
- Preview trực tiếp PDF.
- Nút mở hoặc tải tệp đối với định dạng không preview được.
- Ô nhập lý do xử lý.
- Bốn nút quyết định.

### 6.6. `resources/js/Pages/Admin/Documents/ReviewQueue.jsx`

Hàng đợi tài liệu bị nhiều báo cáo.

Đã bổ sung:

- Link vào trang chi tiết.
- Link xem báo cáo và tệp.
- Môn học, định dạng và dung lượng.
- Số báo cáo hợp lệ.
- Lý do tự động chuyển trạng thái.
- Ô nhập lý do quyết định dạng textarea.
- Textarea tự động giãn chiều cao theo nội dung.

## 7. Danh sách file đã sửa

### 7.1. `app/Http/Controllers/DocumentReportController.php`

Triển khai endpoint nhận báo cáo:

```text
POST /bao-cao/tai-lieu
```

Validation:

- `ma_tai_lieu`: bắt buộc, phải tồn tại.
- `ly_do`: một trong các giá trị báo cáo hợp lệ.
- `chi_tiet_loi`: không bắt buộc, tối đa 2.000 ký tự.

Sau khi validation, controller gọi `DocumentReportService`.

### 7.2. `app/Http/Controllers/DocumentModerationController.php`

Bổ sung các chức năng:

- `reviewQueue()`: lấy hàng đợi tài liệu `CHO_KIEM_TRA` và `TAM_AN_KIEM_TRA`.
- `reviewDocument()`: lấy chi tiết tài liệu và các báo cáo liên quan.
- `previewDocument()`: stream tệp từ private storage để preview/tải.
- `decideDocument()`: xử lý bốn quyết định trên hàng đợi.
- Cập nhật `resolveReport()` để tính lại số báo cáo hợp lệ sau khi báo cáo được xử lý.

Controller dùng `withTrashed()` khi xem chi tiết để Admin vẫn có thể kiểm tra tài liệu đã bị xóa mềm.

### 7.3. `app/Models/TaiLieu.php`

Bổ sung `$fillable` và cast cho:

- `so_bao_cao_hop_le`.
- `ly_do_trang_thai`.
- `tu_dong_chuyen_luc`.

### 7.4. `app/Models/BaoCaoViPham.php`

Bổ sung phương thức:

```php
laHopLe()
```

Phương thức xác định báo cáo có được tính vào ngưỡng hay không.

### 7.5. `database/migrations/2026_10_13_072856_create_tai_lieu_table.php`

Bổ sung trạng thái:

```text
CHO_KIEM_TRA
```

### 7.6. `routes/documents.php`

Bổ sung route:

| Method | URL | Chức năng |
|---|---|---|
| `POST` | `/bao-cao/tai-lieu` | Gửi báo cáo |
| `GET` | `/quan-tri/tai-lieu/cho-kiem-tra` | Hàng đợi kiểm tra |
| `GET` | `/quan-tri/tai-lieu/{id}/kiem-tra` | Chi tiết kiểm tra |
| `GET` | `/quan-tri/tai-lieu/{id}/xem-tep` | Preview/stream tệp |
| `PUT` | `/quan-tri/tai-lieu/{id}/quyet-dinh` | Quyết định tài liệu |

Các route quản trị yêu cầu:

```text
auth
role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN
```

### 7.7. `resources/js/Layouts/AdminLayout.jsx`

Bổ sung menu:

```text
Kiểm tra tài liệu
```

Menu dẫn đến hàng đợi tài liệu bị nhiều báo cáo.

### 7.8. `resources/js/Pages/Admin/Documents/Deleted.jsx`

Hiển thị đúng cột soft delete tùy biến:

```text
ngay_xoa_tam
```

### 7.9. `tests/Feature/DocumentReportMonitoringTest.php`

Kiểm thử:

1. Chỉ tài khoản uy tín mới được tính.
2. Không cho báo cáo trùng cùng tài khoản.
3. Đạt 3 báo cáo chuyển `CHO_KIEM_TRA`.
4. Đạt 5 báo cáo chuyển `TAM_AN_KIEM_TRA`.
5. Notification được gửi đến Moderator.

## 8. Ý nghĩa bốn nút quyết định

### 8.1. Giữ nguyên

Action:

```text
KEEP
```

Kết quả:

- Tài liệu về `DA_DUYET`.
- Tài liệu tiếp tục được hiển thị.
- Các báo cáo đang chờ cùng tài liệu được đánh dấu `DA_XU_LY`.
- Lý do Admin được lưu vào `ly_do_trang_thai`.

### 8.2. Khôi phục

Action:

```text
RESTORE
```

Kết quả:

- Bỏ soft delete.
- Xóa giá trị `ngay_xoa_tam`.
- Chuyển tài liệu về `DA_DUYET`.
- Dùng cho tài liệu đã bị xóa mềm nhưng sau khi kiểm tra được xác định là có thể giữ lại.

### 8.3. Ẩn tài liệu

Action:

```text
HIDE
```

Kết quả:

- Chuyển `trang_thai` thành `TAM_AN_KIEM_TRA`.
- Không xóa bản ghi.
- Tài liệu không còn được xem là tài liệu công khai bình thường.
- Lý do xử lý được lưu lại.

### 8.4. Xóa mềm

Action:

```text
DELETE
```

Kết quả:

- Gọi Soft Delete.
- Ghi thời điểm vào `ngay_xoa_tam`.
- Tài liệu biến khỏi truy vấn bình thường.
- Dữ liệu vẫn còn trong database.
- Có thể khôi phục từ `/quan-tri/tai-lieu/da-xoa`.

## 9. Quy trình Admin/Moderator sử dụng

1. Đăng nhập bằng tài khoản Moderator hoặc Admin.
2. Mở menu **Kiểm tra tài liệu**.
3. Chọn tài liệu trong hàng đợi.
4. Nhấn **Xem chi tiết**.
5. Đọc các báo cáo liên quan.
6. Mở preview PDF hoặc tải tệp.
7. Kiểm tra môn học, người đăng, định dạng và dung lượng.
8. Nhập lý do và căn cứ xử lý.
9. Chọn một trong bốn quyết định.
10. Kiểm tra flash message và trạng thái tài liệu.

## 10. Bảo mật và phân quyền

- Người chưa đăng nhập không truy cập được route quản trị.
- Sinh viên thông thường không truy cập được hàng đợi.
- Route preview tệp chỉ nằm trong nhóm middleware Moderator/Admin.
- Tệp được đọc từ private storage, không công khai trực tiếp.
- Tên tệp trả về dùng `basename()` để tránh lộ đường dẫn thư mục.
- Người xử lý lấy từ `$request->user()->getKey()`, không nhận từ client.
- Các thay đổi xử lý chính nằm trong database transaction.

## 11. Kiểm thử đã thực hiện

### 11.1. Test tự động

Đã chạy:

```powershell
php artisan test tests/Feature/DocumentReportMonitoringTest.php
```

Kết quả:

```text
2 tests passed
7 assertions passed
```

Đã chạy regression liên quan:

```powershell
php artisan test tests/Feature/DocumentReportMonitoringTest.php tests/Feature/KycQueueTest.php
```

Kết quả:

```text
5 tests passed
28 assertions passed
```

### 11.2. Kiểm tra frontend

Đã chạy:

```powershell
npm run build
```

Kết quả: build Vite thành công, bao gồm bundle cho:

- `ReviewQueue`.
- `ReviewDetail`.

### 11.3. Kiểm tra backend

Đã kiểm tra:

```powershell
php -l app\Http\Controllers\DocumentModerationController.php
php artisan route:list --name=admin.documents
git diff --check
```

Các kiểm tra đều đạt.

## 12. Lưu ý dữ liệu tệp mẫu

Một số bản ghi trong `TaiLieuSeeder` chỉ tạo đường dẫn database như:

```text
demo/de-thi-co-so-du-lieu.pdf
```

Nếu tệp vật lý chưa tồn tại tại:

```text
storage/app/private/demo/
```

trang chi tiết sẽ hiển thị cảnh báo không tìm thấy tệp. Đây là tình trạng thiếu file vật lý, không phải lỗi route hoặc lỗi preview.

Để kiểm tra preview, cần đặt tệp thật vào đúng đường dẫn, ví dụ:

```text
storage/app/private/demo/de-thi-co-so-du-lieu.pdf
```

## 13. Kết luận

Task đã hoàn thiện luồng từ lúc người dùng gửi báo cáo đến lúc Moderator/Admin xem nội dung và đưa ra quyết định. Hệ thống không chỉ đếm báo cáo mà còn phân biệt uy tín người báo cáo, chống báo cáo trùng, tự động phân loại mức độ rủi ro, thông báo người có trách nhiệm và cung cấp đầy đủ dữ liệu để quyết định giữ, khôi phục, ẩn hoặc xóa mềm tài liệu.
