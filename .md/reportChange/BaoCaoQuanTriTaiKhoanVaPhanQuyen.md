# Báo cáo triển khai: Quản trị tài khoản và phân quyền người dùng

## 1. Thông tin chung

### 1.1. Mục tiêu

Task này mở rộng chức năng quản trị tài khoản của AGU Academic Hub từ khả năng cơ bản là xem danh sách và khóa/mở khóa tài khoản thành một phân hệ quản trị tài khoản hoàn chỉnh, gồm:

- Xem chi tiết hồ sơ người dùng.
- Quản lý vai trò và Khoa.
- Phân biệt Admin thường và Super Admin.
- Khóa, mở khóa và kích hoạt lại tài khoản.
- Hiển thị lý do tài khoản bị khóa.
- Theo dõi lịch sử trạng thái và lịch sử phân quyền.
- Ghi nhận người thực hiện và thời điểm thực hiện.
- Xử lý tài khoản vi phạm nhiều lần.
- Ngăn các thao tác tự khóa, tự hạ quyền hoặc tự thay đổi thông tin nhạy cảm.
- Bảo vệ tài khoản quản trị và Super Admin cuối cùng.

### 1.2. Phạm vi vai trò

Hệ thống hiện có bốn vai trò nghiệp vụ:

| Giá trị | Tên hiển thị | Phạm vi |
|---|---|---|
| `SINH_VIEN` | Sinh viên | Người học đang sử dụng hệ thống |
| `CUU_SINH_VIEN` | Cựu sinh viên | Người học đã tốt nghiệp |
| `KIEM_DUYET_VIEN` | Kiểm duyệt viên | Xử lý nghiệp vụ kiểm duyệt |
| `QUAN_TRI_VIEN` | Quản trị viên | Quản trị hệ thống |

Vai trò `QUAN_TRI_VIEN` được phân thành hai cấp:

| Cấp | Giá trị | Ý nghĩa |
|---|---|---|
| Admin thường | `THUONG` | Quản lý người dùng thông thường |
| Super Admin | `TOI_CAO` | Quản lý các tài khoản quản trị và quyền quản trị |

Thiết kế này không thêm một vai trò `SUPER_ADMIN` mới vào enum vai trò. Thay vào đó, hệ thống giữ `vai_tro = QUAN_TRI_VIEN` và thêm `cap_quan_tri`. Cách này tương thích với các middleware và code hiện có đang kiểm tra vai trò quản trị.

---

## 2. Nghiệp vụ và quy tắc phân quyền

## 2.1. Admin thường

Admin thường có thể:

- Truy cập danh sách tài khoản.
- Xem chi tiết tài khoản.
- Xem thông tin vai trò, trạng thái, Khoa, điểm uy tín và ví điểm.
- Đổi `SINH_VIEN` thành `CUU_SINH_VIEN` và ngược lại nếu tài khoản thuộc phạm vi người dùng thông thường.
- Gán hoặc thay đổi Khoa cho Sinh viên và Cựu sinh viên.
- Khóa hoặc mở khóa Sinh viên và Cựu sinh viên.
- Ghi nhận vi phạm đối với Sinh viên và Cựu sinh viên.
- Xem lịch sử trạng thái và lịch sử phân quyền.

Admin thường không thể:

- Cấp quyền Admin.
- Cấp quyền Kiểm duyệt viên.
- Nâng Admin thường thành Super Admin.
- Hạ cấp hoặc thay đổi cấp quản trị của Admin khác.
- Khóa hoặc mở khóa tài khoản Admin.
- Khóa hoặc mở khóa tài khoản Kiểm duyệt viên.
- Ghi nhận vi phạm đối với tài khoản quản trị.
- Tự khóa tài khoản của mình.
- Tự thay đổi vai trò hoặc Khoa của mình.

## 2.2. Super Admin

Super Admin có thể:

- Thực hiện toàn bộ thao tác của Admin thường.
- Quản lý tài khoản Kiểm duyệt viên.
- Quản lý Admin thường.
- Cấp quyền `QUAN_TRI_VIEN`.
- Thu hồi quyền quản trị.
- Chuyển Admin thường thành Super Admin.
- Hạ Super Admin khác nếu hệ thống vẫn còn Super Admin hợp lệ.
- Khóa hoặc mở khóa tài khoản Admin và Kiểm duyệt viên.
- Xem đầy đủ lịch sử can thiệp.

Super Admin không thể:

- Tự thay đổi vai trò hoặc cấp quản trị của chính mình.
- Tự khóa hoặc mở khóa tài khoản của mình.
- Tự ghi nhận vi phạm cho chính mình.
- Hạ cấp Super Admin cuối cùng của hệ thống.

## 2.3. Quy tắc Khoa

Khoa chỉ áp dụng cho:

- Sinh viên.
- Cựu sinh viên.

Các tài khoản sau không thuộc Khoa:

- Kiểm duyệt viên.
- Admin thường.
- Super Admin.

Khi chuyển một tài khoản sang `KIEM_DUYET_VIEN` hoặc `QUAN_TRI_VIEN`, backend tự đặt `ma_khoa = null`.

Giao diện cũng ẩn ô chọn Khoa đối với các vai trò quản trị và hiển thị thông báo:

> Vai trò quản trị hệ thống không thuộc Khoa.

---

## 3. Luồng nghiệp vụ chính

## 3.1. Luồng truy cập danh sách tài khoản

1. Người dùng đăng nhập.
2. Backend kiểm tra người dùng có vai trò `QUAN_TRI_VIEN`.
3. Nếu không phải quản trị viên, request bị từ chối với HTTP 403.
4. Nếu hợp lệ, hệ thống tải danh sách người dùng kèm:
   - Vai trò.
   - Cấp quản trị.
   - Trạng thái.
   - Lý do khóa.
   - Số lần vi phạm.
   - Khoa nếu có.
   - Ví điểm.
5. Người dùng có thể lọc theo:
   - Từ khóa.
   - Vai trò.
   - Trạng thái.
   - Khoa.
6. Danh sách sử dụng phân trang.

## 3.2. Luồng xem chi tiết tài khoản

1. Quản trị viên chọn một tài khoản trong danh sách.
2. Giao diện điều hướng tới trang chi tiết.
3. Backend tải:
   - Thông tin cá nhân.
   - Email và MSSV.
   - Vai trò.
   - Cấp quản trị.
   - Khoa.
   - Trạng thái.
   - Lý do khóa.
   - Số lần vi phạm.
   - Mức cảnh báo.
   - Điểm uy tín và ví điểm.
   - Lịch sử trạng thái.
   - Lịch sử phân quyền.
4. Backend tính toán quyền thao tác của người đang đăng nhập đối với tài khoản đích.
5. Giao diện bật hoặc khóa các trường và nút thao tác tương ứng.

## 3.3. Luồng thay đổi vai trò và Khoa

1. Quản trị viên chọn vai trò mới.
2. Nếu vai trò là Sinh viên hoặc Cựu sinh viên, giao diện cho phép chọn Khoa.
3. Nếu vai trò là Kiểm duyệt viên hoặc Quản trị viên, giao diện ẩn Khoa.
4. Nếu vai trò là Quản trị viên, Super Admin có thể chọn:
   - Admin thường.
   - Super Admin.
5. Backend kiểm tra:
   - Người thực hiện có phải quản trị viên không.
   - Người thực hiện có đang tác động lên chính mình không.
   - Người thực hiện có đủ cấp quản trị để quản lý tài khoản đích không.
   - Admin thường có đang cố cấp quyền quản trị không.
   - Có đang hạ Super Admin cuối cùng không.
   - Khoa có phù hợp với vai trò không.
6. Backend ghi một bản ghi audit trong transaction.
7. Backend cập nhật người dùng sau khi ghi audit thành công.
8. Giao diện hiển thị thông báo thành công hoặc lỗi.

## 3.4. Luồng khóa/mở khóa tài khoản

1. Quản trị viên nhập lý do nếu cần.
2. Backend kiểm tra quyền quản lý tài khoản đích.
3. Backend chặn trường hợp tự khóa hoặc tự mở khóa.
4. Nếu tài khoản đang hoạt động, trạng thái chuyển thành `TAM_KHOA`.
5. Nếu tài khoản đang tạm khóa, trạng thái chuyển thành `DANG_HOAT_DONG`.
6. Khi khóa:
   - Lưu `ly_do_khoa`.
   - Ghi lịch sử trạng thái.
7. Khi mở khóa:
   - Xóa `ly_do_khoa`.
   - Ghi lịch sử trạng thái.
8. Lịch sử lưu:
   - Trạng thái cũ.
   - Trạng thái mới.
   - Hành động.
   - Lý do.
   - Người thực hiện.
   - Thời điểm.

## 3.5. Luồng xử lý vi phạm nhiều lần

1. Quản trị viên chọn tài khoản cần ghi nhận vi phạm.
2. Nhập mô tả vi phạm bắt buộc.
3. Backend tăng `so_lan_vi_pham`.
4. Lần vi phạm đầu tiên:
   - Gán `muc_canh_bao = CANH_BAO`.
   - Giữ nguyên trạng thái hiện tại.
5. Từ lần vi phạm tiếp theo:
   - Gán `muc_canh_bao = TAM_KHOA`.
   - Chuyển tài khoản sang `TAM_KHOA`.
   - Lưu lý do khóa.
6. Ghi lịch sử trạng thái tương ứng.
7. Tài khoản có thể được mở khóa bằng luồng mở khóa thủ công nếu người có thẩm quyền thực hiện.

---

## 4. Thiết kế dữ liệu

## 4.1. Bảng `nguoi_dung`

Migration bổ sung các trường quản lý tài khoản:

| Trường | Kiểu | Ý nghĩa |
|---|---|---|
| `ly_do_khoa` | `TEXT NULL` | Lý do tài khoản bị khóa |
| `so_lan_vi_pham` | `UNSIGNED SMALLINT` | Tổng số lần vi phạm |
| `muc_canh_bao` | `VARCHAR(30) NULL` | Mức cảnh báo hiện tại |
| `cap_quan_tri` | `ENUM('THUONG','TOI_CAO') NULL` | Cấp quản trị, chỉ dùng cho Admin |

Giá trị `cap_quan_tri`:

- `NULL`: Không phải tài khoản Admin.
- `THUONG`: Admin thường.
- `TOI_CAO`: Super Admin.

## 4.2. Bảng `lich_su_trang_thai_nguoi_dung`

Bảng này lưu lịch sử khóa, mở khóa và xử lý vi phạm:

| Trường | Ý nghĩa |
|---|---|
| `ma_nguoi_dung` | Tài khoản bị tác động |
| `ma_nguoi_thuc_hien` | Người thực hiện |
| `trang_thai_cu` | Trạng thái trước thay đổi |
| `trang_thai_moi` | Trạng thái sau thay đổi |
| `ly_do` | Lý do |
| `hanh_dong` | `KHOA`, `MO_KHOA`, `CANH_BAO`, `TAM_KHOA` |
| `created_at` | Thời điểm thao tác |

## 4.3. Bảng `lich_su_phan_quyen_nguoi_dung`

Bảng này lưu lịch sử thay đổi vai trò, Khoa và cấp quản trị:

| Trường | Ý nghĩa |
|---|---|
| `ma_nguoi_dung` | Tài khoản bị thay đổi |
| `ma_nguoi_thuc_hien` | Người thực hiện |
| `vai_tro_cu` | Vai trò trước thay đổi |
| `vai_tro_moi` | Vai trò sau thay đổi |
| `cap_quan_tri_cu` | Cấp quản trị cũ |
| `cap_quan_tri_moi` | Cấp quản trị mới |
| `ma_khoa_cu` | Khoa cũ |
| `ma_khoa_moi` | Khoa mới |
| `ly_do` | Lý do thay đổi |
| `created_at` | Thời điểm thao tác |

## 4.4. Tài khoản Super Admin mặc định

Migration phân quyền thực hiện các bước:

1. Gán tất cả Admin hiện có thành `THUONG`.
2. Tìm tài khoản có email `admin@agu.edu.vn`.
3. Nếu tài khoản này có vai trò `QUAN_TRI_VIEN`, gán:
   - `cap_quan_tri = TOI_CAO`.
   - `ma_khoa = null`.
4. Các tài khoản quản trị không thuộc Khoa.

---

## 5. Kiểm soát quyền ở backend

## 5.1. Kiểm tra cấp quản trị trong model

Model [NguoiDung.php](../app/Models/NguoiDung.php) cung cấp:

- `laQuanTriVien()`.
- `laSuperAdmin()`.
- `laAdminThuong()`.
- `laKiemDuyetVien()`.
- `dangHoatDong()`.

`laSuperAdmin()` chỉ trả về `true` khi:

```text
vai_tro = QUAN_TRI_VIEN
và cap_quan_tri = TOI_CAO
```

## 5.2. Hàm `canManageTarget()`

Controller sử dụng hàm kiểm tra tập trung:

- Từ chối nếu actor và target là cùng một người.
- Nếu target là Admin hoặc Kiểm duyệt viên, chỉ Super Admin được thao tác.
- Nếu target là Sinh viên hoặc Cựu sinh viên, Admin hợp lệ được thao tác.

Kiểm tra được thực hiện ở backend, vì ẩn hoặc disable nút trên frontend không phải là cơ chế bảo mật.

## 5.3. Bảo vệ Super Admin cuối cùng

Trước khi hạ cấp một Super Admin, backend đếm số Super Admin hiện có.

Nếu chỉ còn một Super Admin, thao tác hạ cấp bị từ chối. Quy tắc này ngăn hệ thống rơi vào trạng thái không còn người có quyền quản lý tài khoản quản trị.

---

## 6. API và routes

Các routes quản trị tài khoản nằm trong [routes/community.php](../routes/community.php):

| Method | URI | Tên route | Mục đích |
|---|---|---|---|
| `GET` | `/quan-tri/nguoi-dung` | `admin.users.index` | Danh sách tài khoản |
| `GET` | `/quan-tri/nguoi-dung/{ma_nguoi_dung}` | `admin.users.show` | Chi tiết tài khoản |
| `PUT` | `/quan-tri/nguoi-dung/{ma_nguoi_dung}/phan-quyen` | `admin.users.permissions` | Cập nhật vai trò/Khoa/cấp quản trị |
| `PUT` | `/quan-tri/nguoi-dung/{ma_nguoi_dung}/trang-thai` | `admin.users.status` | Khóa hoặc mở khóa |
| `POST` | `/quan-tri/nguoi-dung/{ma_nguoi_dung}/vi-pham` | `admin.users.violations` | Ghi nhận vi phạm |

Hiện tại không có route xóa tài khoản quản trị. Việc vô hiệu hóa tài khoản được thực hiện bằng trạng thái `TAM_KHOA` và có audit.

---

## 7. Giao diện người dùng

## 7.1. Danh sách tài khoản

File [resources/js/Pages/Admin/Users/Index.jsx](../resources/js/Pages/Admin/Users/Index.jsx) hỗ trợ:

- Tìm kiếm theo họ tên, email và MSSV.
- Lọc theo vai trò.
- Lọc theo trạng thái.
- Lọc theo Khoa.
- Phân trang.
- Hiển thị vai trò.
- Hiển thị nhãn `Super Admin`.
- Hiển thị trạng thái và lý do khóa.
- Hiển thị ví điểm và điểm uy tín.
- Điều hướng tới trang chi tiết.
- Hiển thị `Chỉ Super Admin` thay vì nút khóa đối với tài khoản quản trị khi actor là Admin thường.
- Không hiển thị Khoa cho Admin/Moderator.

## 7.2. Trang chi tiết

File [resources/js/Pages/Admin/Users/Show.jsx](../resources/js/Pages/Admin/Users/Show.jsx) hỗ trợ:

- Xem thông tin tài khoản.
- Chọn vai trò.
- Chọn cấp Admin.
- Chọn Khoa khi vai trò cho phép.
- Nhập lý do thay đổi phân quyền.
- Nhập lý do khóa/mở khóa.
- Ghi nhận vi phạm.
- Xem lịch sử trạng thái.
- Xem lịch sử phân quyền.
- Disable các control không phù hợp với quyền của actor.
- Hiển thị cảnh báo tài khoản chỉ được quản lý bởi Super Admin.

Các textarea được tách state riêng để tránh hiện tượng mất hoặc nhảy con trỏ khi nhập. Kích thước textarea được tăng để dễ đọc và tất cả caret input/textarea/select được đặt màu đen.

---

## 8. Các file đã thực hiện

### 8.1. Backend

- [app/Enums/CapQuanTri.php](../app/Enums/CapQuanTri.php)
  - Enum cấp quản trị.
- [app/Models/NguoiDung.php](../app/Models/NguoiDung.php)
  - Cast `cap_quan_tri`.
  - Helper `laSuperAdmin()` và `laAdminThuong()`.
  - Quan hệ lịch sử.
- [app/Models/LichSuTrangThaiNguoiDung.php](../app/Models/LichSuTrangThaiNguoiDung.php)
  - Model audit trạng thái.
- [app/Models/LichSuPhanQuyenNguoiDung.php](../app/Models/LichSuPhanQuyenNguoiDung.php)
  - Model audit phân quyền và cấp quản trị.
- [app/Http/Controllers/AdminUserController.php](../app/Http/Controllers/AdminUserController.php)
  - Danh sách.
  - Chi tiết.
  - Cập nhật quyền.
  - Khóa/mở khóa.
  - Xử lý vi phạm.
  - Kiểm tra cấp quản trị.
  - Bảo vệ Super Admin cuối cùng.
- [app/Http/Middleware/HandleInertiaRequests.php](../app/Http/Middleware/HandleInertiaRequests.php)
  - Chia sẻ cấp quản trị và cờ `la_super_admin` cho frontend.

### 8.2. Database

- [database/migrations/2026_10_29_000001_add_account_management_fields_to_nguoi_dung_table.php](../database/migrations/2026_10_29_000001_add_account_management_fields_to_nguoi_dung_table.php)
  - Trường lý do khóa, số lần vi phạm và mức cảnh báo.
- [database/migrations/2026_10_29_000002_create_account_management_audits_tables.php](../database/migrations/2026_10_29_000002_create_account_management_audits_tables.php)
  - Bảng lịch sử trạng thái và phân quyền.
- [database/migrations/2026_10_29_000003_add_admin_level_to_nguoi_dung_and_permission_audit.php](../database/migrations/2026_10_29_000003_add_admin_level_to_nguoi_dung_and_permission_audit.php)
  - Cấp quản trị.
  - Audit cấp quản trị.
  - Nâng `admin@agu.edu.vn` thành Super Admin.

### 8.3. Frontend

- [resources/js/Pages/Admin/Users/Index.jsx](../resources/js/Pages/Admin/Users/Index.jsx)
  - Danh sách, bộ lọc, nhãn cấp quản trị và giới hạn thao tác.
- [resources/js/Pages/Admin/Users/Show.jsx](../resources/js/Pages/Admin/Users/Show.jsx)
  - Chi tiết, phân quyền, cấp quản trị, trạng thái và audit.
- [resources/css/app.css](../resources/css/app.css)
  - Caret màu đen cho input, textarea và select.
- [resources/js/Components/Wishlist/CreateRequestModal.jsx](../resources/js/Components/Wishlist/CreateRequestModal.jsx)
  - Đồng nhất caret màu đen.
- [resources/js/Components/Wishlist/FulfillRequestModal.jsx](../resources/js/Components/Wishlist/FulfillRequestModal.jsx)
  - Đồng nhất caret màu đen.

### 8.4. Seeders và factory

- [database/seeders/CommunitySeeder.php](../database/seeders/CommunitySeeder.php)
  - Admin mặc định là Super Admin.
  - Admin/Moderator không thuộc Khoa.
- [database/factories/NguoiDungFactory.php](../database/factories/NguoiDungFactory.php)
  - Admin factory mặc định là Admin thường.

### 8.5. Tài liệu

- [`.md/TaiLieuYeuCau.md`](./TaiLieuYeuCau.md)
  - Quy tắc nghiệp vụ.
- [`.md/TaiLieuKyThuat.md`](./TaiLieuKyThuat.md)
  - Mô hình dữ liệu và endpoint.
- [`.md/TaiLieuThietKe.md`](./TaiLieuThietKe.md)
  - Thiết kế RBAC và luồng xử lý.
- [`.md/DESIGN_GUIDELINES.md`](./DESIGN_GUIDELINES.md)
  - Nguyên tắc hiển thị và giới hạn thao tác trên giao diện.

---

## 9. Kiểm thử và xác minh

Đã thực hiện:

- PHP lint cho enum, model, controller, middleware, migration và seeder.
- Kiểm tra route quản trị bằng `php artisan route:list`.
- Kiểm tra frontend production bằng `npm.cmd run build`.
- Kiểm tra whitespace bằng `git diff --check`.
- Tìm kiếm đối chiếu các nơi sử dụng `QUAN_TRI_VIEN`, `vai_tro`, `role` và `cap_quan_tri`.

Kết quả:

- PHP syntax: đạt.
- Route resolution: đạt.
- Frontend build: đạt.
- Diff check: đạt.

## 9.1. Giới hạn môi trường

Migration chưa được chạy trực tiếp trong môi trường phát triển tại thời điểm triển khai vì MySQL local không lắng nghe tại `127.0.0.1:3306`.

Sau khi bật database, chạy:

```bash
php artisan migrate
```

Sau migration cần xác minh:

```sql
SELECT email, vai_tro, cap_quan_tri, ma_khoa
FROM nguoi_dung
WHERE email = 'admin@agu.edu.vn';
```

Kết quả mong đợi:

```text
email              = admin@agu.edu.vn
vai_tro            = QUAN_TRI_VIEN
cap_quan_tri       = TOI_CAO
ma_khoa            = NULL
```

---

## 10. Các điểm cần lưu ý khi vận hành

1. Không chỉ dựa vào giao diện để bảo vệ quyền. Backend đã có kiểm tra độc lập.
2. Cần bảo vệ thông tin đăng nhập của `admin@agu.edu.vn` vì đây là Super Admin mặc định.
3. Không nên chạy lại seeder trong môi trường production nếu chưa rà soát hành vi cập nhật tài khoản.
4. Nên có quy trình cấp quyền Super Admin được phê duyệt và ghi nhận ngoài hệ thống.
5. Nên bổ sung test feature riêng cho:
   - Admin thường không được đổi quyền Admin.
   - Admin thường không được khóa Admin.
   - Super Admin được quản lý Admin.
   - Không thể tự hạ quyền.
   - Không thể hạ Super Admin cuối cùng.
   - Chuyển Admin/Moderator phải xóa Khoa.
   - `admin@agu.edu.vn` được nâng đúng cấp sau migration.
6. Hiện chưa có chức năng xóa vật lý tài khoản trong module quản trị. Trạng thái `TAM_KHOA` là cơ chế vô hiệu hóa tài khoản có kiểm soát và lưu lịch sử.
7. Các thao tác thay đổi quyền đều cần lý do trong quy trình vận hành thực tế dù trường lý do hiện cho phép bỏ trống ở một số endpoint.

---

## 11. Kết luận

Task đã bổ sung đầy đủ nền tảng quản trị tài khoản theo mô hình phân cấp:

```text
QUAN_TRI_VIEN + THUONG   = Admin thường
QUAN_TRI_VIEN + TOI_CAO  = Super Admin
```

Admin thường chỉ quản lý người dùng thông thường. Super Admin chịu trách nhiệm quản lý quyền quản trị và các tài khoản nhạy cảm. Mọi thay đổi trạng thái và phân quyền đều có audit, có người thực hiện, có thời điểm và có lý do.

Tài khoản `admin@agu.edu.vn` được định nghĩa là Super Admin mặc định thông qua migration, tạo nền tảng để quản lý các Admin còn lại một cách an toàn và có kiểm soát.
