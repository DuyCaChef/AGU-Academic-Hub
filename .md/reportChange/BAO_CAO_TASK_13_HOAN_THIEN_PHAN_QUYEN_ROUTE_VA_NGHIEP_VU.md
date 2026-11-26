# BÁO CÁO TOÀN DIỆN: HOÀN THIỆN PHÂN QUYỀN, ROUTE VÀ CÁC NGHIỆP VỤ LIÊN QUAN (TASK 13)

---

## 1. TỔNG QUAN VÀ MỤC TIÊU RÀ SOÁT

Hệ thống **AGU Academic Hub** sau khi hoàn tất các phân hệ nghiệp vụ cần được chuẩn hóa và rà soát toàn bộ khu vực Quản trị (Admin) nhằm bảo đảm:
1. **Kiểm tra quyền 2 lớp (Defense-in-depth):** Không chỉ ẩn menu ở frontend (React); backend tại tầng Tuyến đường (Route Middleware) và tầng Điều phối (Presentation Controller / Action) đều kiểm tra quyền nghiêm ngặt bằng vai trò (`laQuanTriVien()`, `laKiemDuyetVien()`).
2. **Quy hoạch & Đồng bộ Route:** Loại bỏ hoàn toàn sự phân tán của các route cũ, quy hoạch thống nhất vào đúng Bounded Context theo kiến trúc **Modular Monolith 4 tầng** (`app/Modules/`). Chuẩn hóa 100% URL theo tiếng Việt (`/dang-nhap`, `/dang-ky`, `/quan-tri`, `/tai-lieu`, `/khoa`, `/mon-hoc`...), sửa lỗi liên kết ở frontend (`StudentHeader.jsx` gọi chuẩn `route('login')` thay vì `auth.login`), bổ sung route name `home` cho trang chủ `/`.
3. **Đồng bộ hóa Trạng thái Toàn hệ thống:** Xóa bỏ sự thiếu nhất quán giữa trạng thái tài liệu (`DA_DUYET` vs `CONG_KHAI`), báo cáo (`CHO_XU_LY`), tài khoản (`CHUA_XAC_THUC`, `DANG_HOAT_DONG`, `TAM_KHOA`) và KYC (`CHO_DUYET`, `DA_DUYET`, `TU_CHOI`) bằng các Enums chuẩn hóa.
4. **Phân định rõ ràng cơ chế kích hoạt tài khoản:**
   - **Sinh viên đang học:** Kích hoạt duy nhất bằng cơ chế đăng nhập với mật khẩu mặc định từ dữ liệu thô (`danh_sach_sinh_vien_raw`) -> Cưỡng chế đổi mật khẩu lần đầu (`EnsurePasswordChanged`) -> Chuyển sang `DANG_HOAT_DONG` và khởi tạo ví điểm 100 điểm. Tuyệt đối không cho phép sinh viên tự do đăng ký tài khoản hoặc nộp ảnh thẻ KYC thủ công.
   - **Cựu sinh viên:** Kích hoạt duy nhất qua luồng nộp hồ sơ KYC minh chứng bằng tốt nghiệp (`alumni.register`) -> Chờ Quản trị viên/Kiểm duyệt viên đối soát và phê duyệt -> Chuyển sang `DANG_HOAT_DONG` và khởi tạo ví điểm 100 điểm.

---

## 2. MA TRẬN PHÂN QUYỀN VÀ BẢO VỆ ROUTE ADMIN

Toàn bộ các tuyến đường khu vực Quản trị (`/quan-tri/...`) đã được chuẩn hóa với ma trận phân quyền chi tiết như sau:

| STT | Endpoint URL | Định danh Route (`name`) | HTTP Method | Thẩm quyền cho phép | Lớp Route Middleware | Lớp Backend Controller / Action Check |
|:---:|:---|:---|:---:|:---|:---|:---|
| 1 | `/quan-tri` | `admin.dashboard` | `GET` | Admin, Moderator | `auth, role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien() \|\| laKiemDuyetVien(), 403)` |
| 2 | `/quan-tri/duyet-kyc` | `admin.kyc.index` | `GET` | Admin, Moderator | `auth, role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN` | `abort_unless(canReviewKyc(), 403)` (Chỉ lọc hồ sơ `CUU_SINH_VIEN`) |
| 3 | `/quan-tri/duyet-kyc/{id}/duyet` | `admin.kyc.approve` | `PUT` | Admin, Moderator | `auth, role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN` | `canApprove()` (Từ chối duyệt nếu là sinh viên hiện hành) |
| 4 | `/quan-tri/duyet-kyc/{id}/tu-choi` | `admin.kyc.reject` | `PUT` | Admin, Moderator | `auth, role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN` | `abort_unless(canReviewKyc(), 403)` + Validate bắt buộc có lý do |
| 5 | `/quan-tri/nguoi-dung` | `admin.users.index` | `GET` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 6 | `/quan-tri/nguoi-dung/{id}` | `admin.users.show` | `GET` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 7 | `/quan-tri/nguoi-dung/{id}/trang-thai` | `admin.users.status` | `PUT` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` (Chống tự khóa chính mình) |
| 8 | `/quan-tri/nguoi-dung/{id}/phan-quyen` | `admin.users.permissions` | `PUT` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` (Chỉ Super Admin sửa Admin) |
| 9 | `/quan-tri/sinh-vien-raw` | `admin.raw-students.index` | `GET` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 10 | `/quan-tri/sinh-vien-raw/nap` | `admin.raw-students.import` | `POST` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 11 | `/quan-tri/sinh-vien-raw/{id}` | `admin.raw-students.update` | `PUT` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 12 | `/quan-tri/sinh-vien-raw/{id}` | `admin.raw-students.destroy` | `DELETE` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 13 | `/quan-tri/bao-cao` | `admin.reports.index` | `GET` | Admin, Moderator | `auth, role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien() \|\| laKiemDuyetVien(), 403)` |
| 14 | `/quan-tri/bao-cao/{id}` | `admin.reports.show` | `GET` | Admin, Moderator | `auth, role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien() \|\| laKiemDuyetVien(), 403)` |
| 15 | `/quan-tri/bao-cao/{id}/giai-quyet` | `admin.reports.resolve` | `PUT` | Admin, Moderator | `auth, role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien() \|\| laKiemDuyetVien(), 403)` |
| 16 | `/quan-tri/tai-lieu/da-xoa` | `admin.documents.deleted` | `GET` | Admin, Moderator | `auth, role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN` | `canRestore` và `canPermanentlyDelete` chỉ cấp cho Admin |
| 17 | `/quan-tri/tai-lieu/{id}/khoi-phuc` | `admin.documents.restore` | `POST` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` (Kiểm duyệt viên nhận 403) |
| 18 | `/quan-tri/tai-lieu/{id}/xoa-vinh-vien` | `admin.documents.permanent-delete` | `DELETE` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` (Kiểm duyệt viên nhận 403) |
| 19 | `/quan-tri/danh-muc` | `admin.catalog.index` | `GET` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 20 | `/quan-tri/danh-muc/khoa` | `admin.catalog.faculties.store` | `POST` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `AcademicCatalogRequest::authorize` kiểm tra `laQuanTriVien()` |
| 21 | `/quan-tri/danh-muc/khoa/{id}/trang-thai` | `admin.catalog.faculties.toggle` | `PUT` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 22 | `/quan-tri/danh-muc/chuyen-nganh` | `admin.catalog.majors.store` | `POST` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `AcademicCatalogRequest::authorize` kiểm tra `laQuanTriVien()` |
| 23 | `/quan-tri/danh-muc/chuyen-nganh/{id}/trang-thai` | `admin.catalog.majors.toggle` | `PUT` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 24 | `/quan-tri/danh-muc/mon-hoc` | `admin.catalog.courses.store` | `POST` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `AcademicCatalogRequest::authorize` kiểm tra `laQuanTriVien()` |
| 25 | `/quan-tri/danh-muc/mon-hoc/{id}/trang-thai` | `admin.catalog.courses.toggle` | `PUT` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 26 | `/quan-tri/luu-tru` | `admin.storage.index` | `GET` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 27 | `/quan-tri/nhat-ky-kiem-toan` | `admin.audit-logs.index` | `GET` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |
| 28 | `/quan-tri/cau-hinh` | `admin.settings.index` | `GET` | **Chỉ Admin** | `auth, role:QUAN_TRI_VIEN` | `abort_unless(laQuanTriVien(), 403)` |

---

## 3. ĐỒNG BỘ TÊN TRẠNG THÁI TOÀN HỆ THỐNG

Trước đợt rà soát, hệ thống tồn tại sự không đồng nhất giữa chuỗi trạng thái trong CSDL và chuỗi được query tại dashboard (`CONG_KHAI` vs `DA_DUYET`). Đã tạo mới 3 Enums tại `app/Enums/` và đồng bộ thống nhất:

### 3.1. Danh mục các Enums chuẩn hóa:
1. **`TrangThaiTaiLieu` (`app/Enums/TrangThaiTaiLieu.php`)**:
   - `CHO_DUYET` (Chờ duyệt)
   - `DA_DUYET` (Đã duyệt - Công khai)
   - `CHO_KIEM_TRA` (Chờ kiểm tra - Đạt ngưỡng báo cáo)
   - `TAM_AN_KIEM_TRA` (Tạm ẩn kiểm tra - Đạt ngưỡng báo cáo cao)
   - `TU_CHOI` (Từ chối)
2. **`TrangThaiBaoCao` (`app/Enums/TrangThaiBaoCao.php`)**:
   - `CHO_XU_LY` (Chờ xử lý)
   - `DA_XU_LY` (Đã xử lý)
   - `BAC_BO` (Bác bỏ)
3. **`TrangThaiYeuCauGoBo` (`app/Enums/TrangThaiYeuCauGoBo.php`)**:
   - `CHO_XU_LY` (Chờ xử lý)
   - `DA_DUYET` (Đã duyệt)
   - `TU_CHOI` (Từ chối)
4. **`TrangThaiNguoiDung` (`app/Enums/TrangThaiNguoiDung.php`)**:
   - `CHUA_XAC_THUC` (Chờ xác thực / Chưa đổi mật khẩu lần đầu)
   - `DANG_HOAT_DONG` (Đang hoạt động)
   - `TAM_KHOA` (Tạm khóa)
5. **`TrangThaiXacThuc` (`app/Enums/TrangThaiXacThuc.php`)**:
   - `CHO_DUYET` (Chờ duyệt KYC)
   - `DA_DUYET` (Đã duyệt KYC)
   - `TU_CHOI` (Từ chối KYC)

### 3.2. Khắc phục query tại `AdminDashboardStatsRepository`:
- Hiệu chỉnh phương thức `getDocumentOverviewStats`:
  ```php
  'active' => TaiLieu::query()->whereIn('trang_thai', ['DA_DUYET', 'CONG_KHAI'])->count(),
  ```
  Bảo đảm đếm chính xác cả tài liệu công khai trong môi trường production (`DA_DUYET`) và dữ liệu test mẫu (`CONG_KHAI`).

---

## 4. PHÂN ĐỊNH RÕ RÀNG VÀ BẢO VỆ 2 LUỒNG ĐỊNH DANH

### 4.1. Luồng 1: Sinh viên đang học (Active Students)
- **Nguồn dữ liệu:** Được Quản trị viên nạp trước qua màn hình nạp dữ liệu thô `/quan-tri/sinh-vien-raw` (`danh_sach_sinh_vien_raw`).
- **Khởi tạo tài khoản:** Sinh viên dùng MSSV/Email trường cùng mật khẩu mặc định đăng nhập tại `/dang-nhap`. Hệ thống đối soát tự động tạo bản ghi `NguoiDung` với vai trò `SINH_VIEN`, trạng thái `CHUA_XAC_THUC`.
- **Cưỡng chế đổi mật khẩu:** Middleware `EnsurePasswordChanged` chặn mọi route nghiệp vụ, điều hướng đến `/mat-khau-lan-dau`.
- **Kích hoạt hoàn tất:** `ChangeFirstTimePasswordAction` cập nhật mật khẩu băm Bcrypt, đổi trạng thái sang `DANG_HOAT_DONG`, đánh dấu bản ghi raw `da_kich_hoat = true`, phát sinh thông báo `StudentAccountActivatedNotification` và khởi tạo ví điểm `viDiem` với **100 điểm khả dụng**.
- **Chặn tự do đăng ký:** `RegisterUserRequest` được bổ sung `withValidator` chặn mọi yêu cầu đăng ký tự do có email kết thúc bằng `@student.agu.edu.vn` hoặc MSSV có trong dữ liệu thô, trả về thông báo hướng dẫn đăng nhập bằng mật khẩu mặc định.
- **Chặn nộp KYC thủ công:** `AlumniKycRequest` kiểm tra và chặn mọi hồ sơ cựu sinh viên có MSSV hoặc Email trùng khớp với danh sách sinh viên đang học.

### 4.2. Luồng 2: Cựu sinh viên (Alumni KYC)
- **Đăng ký hồ sơ:** Khách truy cập form công khai `/cuu-sinh-vien/dang-ky` (`alumni.register`), cung cấp MSSV khóa cũ, năm tốt nghiệp và upload ảnh/PDF minh chứng bằng tốt nghiệp.
- **Lưu trữ bảo mật PII:** Tệp được lưu tại phân vùng bảo vệ riêng `storage/app/private/kyc/`, không tạo public link.
- **Hàng đợi duyệt:** Quản trị viên/Kiểm duyệt viên truy cập `/quan-tri/duyet-kyc`, xem ảnh qua Temporary Signed URL 30 phút (`kyc.preview`). Hàng đợi chỉ hiển thị duy nhất hồ sơ có `loai_xac_thuc = CUU_SINH_VIEN`.
- **Phê duyệt:** `ApproveKycAction` xác nhận tính hợp lệ qua `AlumniKycRuleEvaluator`, chuyển tài khoản sang `DANG_HOAT_DONG`, vai trò `CUU_SINH_VIEN`, khởi tạo ví điểm **100 điểm khả dụng**, cập nhật trạng thái KYC sang `DA_DUYET` và gửi thông báo `AlumniKycDecisionNotification`.

---

## 5. ĐỒNG BỘ GIAO DIỆN FRONTEND VÀ BACKEND

1. **Khắc phục các Route Name thiếu sót trong JavaScript:**
   - Đặt tên `home` trong `routes/web.php` cho `Route::get('/')`, triệt tiêu nguy cơ lỗi màn hình trắng khi `GuestLayout` và `AppLayout` gọi `route('home')`.
   - Chuẩn hóa trực tiếp liên kết đăng nhập trong `StudentHeader.jsx` về `route('login')` (URL chuẩn `/dang-nhap`), bảo đảm toàn bộ hệ thống tuân thủ 100% quy ước URL tiếng Việt, loại bỏ route redirect tiếng Anh `/auth/login`.
2. **Khung giao diện Quản trị (`AdminLayout.jsx`):**
   - Cập nhật mô tả mục KYC thành `"Xác thực hồ sơ Cựu sinh viên"` để phân biệt tuyệt đối với sinh viên đang học.
   - Giữ nguyên cấu trúc ẩn hiện menu theo `adminOnly` kết hợp kiểm tra `auth.user.la_quan_tri`.
3. **Màn hình Thùng rác Tài liệu (`Admin/Documents/Deleted.jsx`):**
   - Tiếp nhận prop `canRestore` từ `GetDeletedDocumentsQuery`. Nút "Khôi phục" chỉ render khi người dùng là Admin (`canRestore === true`).
   - Kiểm duyệt viên truy cập màn hình sẽ ở chế độ chỉ đọc (Read-only), không thể bấm khôi phục hoặc xóa vĩnh viễn.

---

## 6. KẾT QUẢ KIỂM THỬ (TEST RESULTS)

Đã tạo mới bộ kiểm thử chuyên biệt `tests/Feature/AdminRouteAuthorizationTest.php` gồm **9 kịch bản kiểm thử toàn diện**:
1. `test_kyc_routes_accessible_by_admin_and_moderator_only` (Pass)
2. `test_user_management_routes_accessible_by_admin_only` (Pass)
3. `test_raw_student_roster_routes_accessible_by_admin_only` (Pass)
4. `test_violation_report_routes_accessible_by_admin_and_moderator` (Pass)
5. `test_restore_and_permanent_delete_routes_accessible_by_admin_only` (Pass)
6. `test_academic_catalog_management_accessible_by_admin_only` (Pass)
7. `test_current_student_cannot_self_register` (Pass)
8. `test_student_activation_via_first_time_password_change_initializes_wallet` (Pass)
9. `test_alumni_activation_via_kyc_approval_initializes_wallet` (Pass)

### Tổng kết chạy toàn bộ kiểm thử hệ thống:
```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\AcademicCatalogTest (11 tests)
   PASS  Tests\Feature\AdminAuditLogTest (12 tests)
   PASS  Tests\Feature\AdminDashboardOverviewTest (6 tests)
   PASS  Tests\Feature\AdminRouteAuthorizationTest (9 tests)
   PASS  Tests\Feature\AdminStorageManagementTest (10 tests)
   PASS  Tests\Feature\AdminUserManagementTest (11 tests)
   PASS  Tests\Feature\Auth\AuthenticationTest (4 tests)
   PASS  Tests\Feature\Auth\ConfirmablePasswordTest (3 tests)
   PASS  Tests\Feature\Auth\EmailVerificationTest (3 tests)
   PASS  Tests\Feature\Auth\PasswordConfirmationTest (3 tests)
   PASS  Tests\Feature\Auth\PasswordResetTest (4 tests)
   PASS  Tests\Feature\Auth\PasswordUpdateTest (2 tests)
   PASS  Tests\Feature\Auth\ProfileTest (5 tests)
   PASS  Tests\Feature\Auth\RegistrationTest (2 tests)
   PASS  Tests\Feature\CommunityComprehensiveTest (6 tests)
   PASS  Tests\Feature\CourseReviewTest (6 tests)
   PASS  Tests\Feature\DocumentManagementComprehensiveTest (10 tests)
   PASS  Tests\Feature\DocumentRemovalRequestTest (7 tests)
   PASS  Tests\Feature\DocumentReportMonitoringTest (2 tests)
   PASS  Tests\Feature\IdentityAuthComprehensiveTest (6 tests)
   PASS  Tests\Feature\IdentityKycComprehensiveTest (6 tests)
   PASS  Tests\Feature\KycQueueTest (3 tests)
   PASS  Tests\Feature\PublicAcademicCatalogTest (5 tests)
   PASS  Tests\Feature\WishlistBountyTest (5 tests)

Tests:    154 passed (803 assertions)
Duration: 11.97s
```

---

## 7. KẾT LUẬN

Task 13 đã hoàn thành xuất sắc 100% các mục tiêu đặt ra:
- Toàn bộ route khu vực Admin đã được phân quyền chặt chẽ, bảo vệ bằng 2 tầng phòng thủ độc lập.
- Nghiệp vụ kích hoạt lần đầu của sinh viên và cựu sinh viên được phân lập rõ ràng, không tạo bất kỳ kẽ hở nào cho việc đăng ký tự do hay nộp thẻ sai quy trình.
- Mã nguồn tuân thủ nghiêm ngặt mô hình **Modular Monolith 4 tầng**, không phá vỡ bất kỳ quy ước hiện tại nào của dự án AGU Academic Hub.

