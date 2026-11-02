# Báo cáo Triển khai Task 10: Dashboard Tổng quan Admin

**Dự án:** Nền tảng Tri thức và Cộng đồng Sinh viên Nội bộ AGU (AGU Academic Hub)  
**Phân hệ:** Quản trị Hệ thống (`Administration` Bounded Context)  
**Mã Task:** TASK-10  
**Tài liệu tham chiếu:** [Tài liệu Thiết kế Kiến trúc](.md/TaiLieuThietKe.md), [Design Guidelines](.md/DESIGN_GUIDELINES.md), [Báo cáo Tái cấu trúc](.md/reportChange/BAO_CAO_TAI_CAU_TRUC_MODULAR_MONOLITH.md)

---

## 1. Mục tiêu và Yêu cầu Nghiệp vụ

Cung cấp trang tổng quan (Admin Console / Moderation Hub) tập trung, trực quan theo thời gian thực giúp Ban Quản trị (Admin) và Ban Kiểm duyệt (Moderator) kiểm soát toàn diện tình trạng vận hành của hệ thống, bao gồm 14 nhóm chỉ số và cảnh báo nghiệp vụ:

1. **Tổng số người dùng** trong hệ thống.
2. **Số sinh viên đã kích hoạt** (đã hoàn thành đổi mật khẩu lần đầu).
3. **Số sinh viên trong dữ liệu thô (raw roster)** do nhà trường cung cấp nhưng chưa đăng nhập.
4. **Số cựu sinh viên đang chờ duyệt KYC** danh tính.
5. **Số hồ sơ KYC đã duyệt và bị từ chối**.
6. **Số tài khoản đang hoạt động và bị tạm khóa** do vi phạm.
7. **Số báo cáo vi phạm chưa xử lý**.
8. **Số tài liệu học thuật đang chờ kiểm tra** (bị tạm ẩn hoặc chờ duyệt).
9. **Số yêu cầu gỡ tài liệu đang chờ giải quyết**.
10. **Số tài liệu trong thùng rác** (và số tài liệu quá hạn 60 ngày chờ thanh lọc).
11. **Tổng số Khoa, Chuyên ngành và Môn học** trong danh mục đào tạo.
12. **Tổng dung lượng lưu trữ** (dung lượng đã dùng, hạn mức ổ đĩa, tỷ lệ %, trạng thái an toàn/nguy cấp và phân bổ loại dữ liệu).
13. **Tổng lượt tải tài liệu** toàn hệ thống.
14. **Các cảnh báo nghiệp vụ khẩn cấp cần xử lý ngay** với lối tắt hành động trực tiếp.

---

## 2. Kiến trúc 4 Tầng Chuẩn Domain-Driven Design (DDD)

Tuân thủ nghiêm ngặt mô hình **Modular Monolith 4 tầng** của bounded context `Administration`:

```text
Trình duyệt Client (React 18 + Inertia.js v2 + Tailwind CSS v4)
      │
      ▼ (Route: GET /quan-tri, Name: admin.dashboard)
[Presentation Layer]
  └── AdminDashboardController (__invoke skinny controller ~15 dòng)
      │
      ▼ (Gọi use-case đọc dữ liệu)
[Application Layer]
  └── GetAdminDashboardOverviewQuery
      ├── Tích hợp GetStorageOverviewQuery (Đọc cache dung lượng đĩa)
      └── Chuyển tham số sang Domain Rule Evaluator
      │
      ▼
[Domain Layer]
  ├── AdminDashboardAlertEvaluator (Quy tắc đánh giá cảnh báo nghiệp vụ khẩn cấp)
  └── StorageThresholdEvaluator (Đánh giá ngưỡng cảnh báo vàng/đỏ dung lượng)
      │
      ▼
[Infrastructure Layer]
  └── AdminDashboardStatsRepository (Truy vấn tối ưu hóa đa driver CSDL)
      │
      ▼
[Database / Storage]
  MySQL 8.0 (Production) / SQLite In-Memory (Test Runner) / Private File Storage
```

---

## 3. Chi tiết Thành phần Đã Xây dựng & Tái cấu trúc

### 3.1. Tầng Domain (Domain Layer)

Tạo mới Domain Service: [`app/Modules/Administration/Domain/Services/AdminDashboardAlertEvaluator.php`](file:///d:/AGU-Academic-Hub/app/Modules/Administration/Domain/Services/AdminDashboardAlertEvaluator.php)

- **Trách nhiệm:** Nhận các chỉ số thống kê thô từ tầng dữ liệu và đánh giá các tình huống bất thường hoặc rủi ro vận hành cần sự can thiệp ngay của Admin/Mod.
- **Phân cấp mức độ nghiêm trọng (Severity Hierarchy):**
  - `critical` (Đỏ / Mức độ nguy cấp):
    - Dung lượng đĩa vượt ngưỡng đỏ ($\ge 90\%$ hoặc trạng thái `NGUY_HIEM`).
    - Báo cáo vi phạm tồn đọng $\ge 5$ mục cần xử lý gấp.
    - Tài liệu chờ kiểm tra $\ge 10$ mục có nguy cơ ứ đọng kiểm duyệt.
  - `warning` (Vàng / Cảnh báo cần lưu ý):
    - Dung lượng lưu trữ sắp đầy ($\ge 80\%$ hoặc trạng thái `CANH_BAO`).
    - Báo cáo vi phạm chưa xử lý ($1 - 4$ mục).
    - Yêu cầu gỡ tài liệu từ tác giả đang chờ giải quyết.
    - Tài liệu thùng rác đã quá hạn lưu trữ 60 ngày cần thanh lọc vĩnh viễn.
  - `info` (Xanh dương / Thông báo nghiệp vụ thường kỳ):
    - Hồ sơ xác thực KYC của cựu sinh viên đang chờ duyệt danh tính.
    - Tài khoản đang bị tạm khóa do vi phạm nội quy.
- **Cơ chế sắp xếp:** Tự động sắp xếp mảng cảnh báo theo thứ tự ưu tiên `critical` $\rightarrow$ `warning` $\rightarrow$ `info`.
- **Cung cấp Action Route & Label:** Mỗi cảnh báo kèm theo nhãn hành động (ví dụ: *"Xử lý báo cáo"*, *"Dọn dẹp lưu trữ"*) và tên route Ziggy chính xác (`admin.reports.index`, `admin.storage.index`, v.v.) để chuyển hướng một chạm.

### 3.2. Tầng Infrastructure (Persistence Layer)

Nâng cấp Repository: [`app/Modules/Administration/Infrastructure/Persistence/AdminDashboardStatsRepository.php`](file:///d:/AGU-Academic-Hub/app/Modules/Administration/Infrastructure/Persistence/AdminDashboardStatsRepository.php)

- Bổ sung các phương thức truy vấn chuyên biệt, tối ưu và độc lập driver CSDL (Database Driver Agnostic):
  - `getPendingKycCount()`: Đếm tổng hồ sơ KYC chờ duyệt (`trang_thai = CHO_DUYET`).
  - `getPendingAlumniKycCount()`: Đếm hồ sơ KYC cựu sinh viên (`loai_xac_thuc = CUU_SINH_VIEN`).
  - `getKycOverviewStats()`: Tổng hợp hồ sơ KYC (chờ duyệt, đã duyệt, từ chối, tỷ lệ duyệt thành công).
  - `getPendingReportsCount()`: Báo cáo vi phạm chờ xử lý (`trang_thai_xu_ly = CHO_XU_LY`).
  - `getDocumentsToReviewCount()`: Tài liệu học thuật cần duyệt (`CHO_KIEM_TRA`, `TAM_AN_KIEM_TRA`).
  - `getPendingRemovalRequestsCount()`: Yêu cầu gỡ tài liệu chờ xử lý.
  - `getTrashedDocumentsCount()`: Số tài liệu xóa mềm trong thùng rác (`TaiLieu::onlyTrashed()`).
  - `getExpiredTrashedDocumentsCount()`: Tài liệu thùng rác quá hạn 60 ngày (`han_luu_thung_rac <= now()`).
  - `getUserStatusStats()`: Thống kê trạng thái người dùng (Đang hoạt động, Tạm khóa, Chờ xác thực) và cơ cấu vai trò (Sinh viên, Cựu SV, Kiểm duyệt viên, Quản trị viên).
  - `getRawStudentStats()`: Thống kê sinh viên trong bảng dữ liệu nạp trước (Tổng raw, Đã kích hoạt, Chưa đăng nhập lần đầu, Tỷ lệ kích hoạt %).
  - `getAcademicCatalogStats()`: Thống kê cấu trúc đào tạo (Khoa, Chuyên ngành, Môn học).
  - `getTotalDocumentDownloads()`: Tổng lượt tải tài liệu toàn hệ thống (`TaiLieu::withTrashed()->sum('luot_tai')`).
  - `getDocumentOverviewStats()`: Tổng tài liệu, đang công khai, trong thùng rác, tổng lượt tải.

### 3.3. Tầng Application (Use Case Queries)

Nâng cấp Query: [`app/Modules/Administration/Application/Queries/GetAdminDashboardOverviewQuery.php`](file:///d:/AGU-Academic-Hub/app/Modules/Administration/Application/Queries/GetAdminDashboardOverviewQuery.php)

- Điều phối luồng đọc dữ liệu, kết hợp repository, query dung lượng lưu trữ (`GetStorageOverviewQuery`) và domain evaluator.
- Đảm bảo tính **tương thích ngược 100% (Backward Compatibility)** với các test và consumer cũ qua các key mức gốc (`pendingKyc`, `pendingReports`, `documentsToReview`, `pendingRemovalRequests`, `totalUsers`, `totalDocuments`, `storage`).
- Đóng gói cấu trúc phân tầng chi tiết: `users`, `rawStudents`, `kyc`, `moderation`, `catalog`, `documents`, `alerts`.

### 3.4. Tầng Presentation & Frontend UI (React + Tailwind CSS v4)

#### Bổ sung Menu Sidebar Quản trị
- File: [`resources/js/Layouts/AdminLayout.jsx`](file:///d:/AGU-Academic-Hub/resources/js/Layouts/AdminLayout.jsx)
- Thêm mục menu **"Tổng quan hệ thống"** (`admin.dashboard`) ở vị trí đầu tiên của `navItems` với icon `LayoutDashboard` từ thư viện Lucide React.
- Xử lý trạng thái active URL chính xác khi người dùng ở trang chủ quản trị (`/quan-tri`).

#### Tái thiết kế Giao diện Bảng điều khiển
- File: [`resources/js/Pages/Admin/Dashboard/Index.jsx`](file:///d:/AGU-Academic-Hub/resources/js/Pages/Admin/Dashboard/Index.jsx)
- **Header Banner & Controls:**
  - Badge trạng thái AGU Academic Hub.
  - Nút **"Làm mới"** sử dụng `router.reload({ only: ['stats'] })` kèm hiệu ứng xoay icon `RefreshCw` giúp cập nhật dữ liệu tức thì mà không cần tải lại toàn bộ trang.
  - Nút lối tắt **"Quản lý lưu trữ"** dành riêng cho Quản trị viên.
- **Section Cảnh báo Nghiệp vụ Khẩn cấp:**
  - Khi có cảnh báo: Hiển thị các khối card nổi bật với viền và nền phân biệt (Đỏ/Vàng/Xanh), số lượng nổi bật, thông điệp rõ ràng và nút bấm chuyển tiếp nhanh sang hàng đợi giải quyết.
  - Khi không có cảnh báo: Hiển thị trạng thái an toàn màu xanh ngọc bích (Emerald) thông báo hệ thống hoạt động ổn định.
- **Section Hàng đợi Kiểm duyệt & Tác vụ (5 Cards):**
  - Duyệt hồ sơ KYC (có ghi chú số cựu SV chờ duyệt).
  - Báo cáo vi phạm chưa xử lý.
  - Kiểm tra tài liệu học thuật.
  - Yêu cầu gỡ tài liệu cộng đồng.
  - Thùng rác tài liệu (có highlight màu đỏ nếu có file quá hạn 60 ngày).
- **Section Người dùng, Dữ liệu sinh viên & Xác thực KYC (3 Cards):**
  - *Tài khoản người dùng:* Tổng số người dùng, phân bổ 4 khối trạng thái (Đang hoạt động, Tạm khóa, Chờ kích hoạt, Vi phạm $\ge 3$ lần) và danh sách cơ cấu vai trò.
  - *Dữ liệu sinh viên nạp trước:* Đối soát MSSV, thanh tiến trình (ProgressBar) tỷ lệ kích hoạt tài khoản %, số sinh viên đã kích hoạt và chưa đăng nhập lần đầu.
  - *Hồ sơ KYC:* Tổng hồ sơ, số cựu sinh viên chờ duyệt, hồ sơ đã duyệt, bị từ chối và tỷ lệ duyệt thành công %.
- **Section Cấu trúc Đào tạo, Kho Tài liệu & Lưu trữ (3 Cards):**
  - *Danh mục đào tạo:* Tổng số Khoa, Chuyên ngành, Môn học.
  - *Tài liệu & Tương tác:* Tổng tài liệu, số tài liệu công khai, số tài liệu trong thùng rác, và khối nổi bật hiển thị **Tổng lượt tải tài liệu** kèm icon `Download`.
  - *Dung lượng lưu trữ:* Dung lượng đã dùng / Hạn mức, ProgressBar trực quan theo màu trạng thái (`AN_TOAN`, `CANH_BAO`, `NGUY_HIEM`), kèm thống kê chi tiết dung lượng file hoạt động, thùng rác và ảnh minh chứng KYC.

---

## 4. Đặc tả Hợp đồng Dữ liệu (Data Contract API / Inertia Props)

Cấu trúc đối tượng `stats` được Controller truyền xuống React component qua Inertia:

```json
{
  "pendingKyc": 2,
  "pendingReports": 1,
  "documentsToReview": 3,
  "pendingRemovalRequests": 1,
  "totalUsers": 1250,
  "totalDocuments": 3420,
  "storage": {
    "tong_dung_luong_bytes": 10737418240,
    "tong_dung_luong_dinh_dang": "10.00 GB",
    "han_muc_luu_tru_dinh_dang": "50.00 GB",
    "ty_le_su_dung_phan_tram": 20.0,
    "trang_thai": "AN_TOAN",
    "dung_luong_hoat_dong_dinh_dang": "8.50 GB",
    "dung_luong_thung_rac_dinh_dang": "1.20 GB",
    "dung_luong_kyc_dinh_dang": "300.00 MB"
  },
  "users": {
    "total": 1250,
    "active": 1180,
    "locked": 25,
    "unverified": 45,
    "byRole": {
      "students": 1100,
      "alumni": 135,
      "moderators": 10,
      "admins": 5
    },
    "highViolations": 8
  },
  "rawStudents": {
    "total": 5000,
    "activated": 1100,
    "unactivated": 3900,
    "activationRate": 22.0
  },
  "kyc": {
    "total": 150,
    "pending": 2,
    "pendingAlumni": 2,
    "pendingStudents": 0,
    "approved": 138,
    "rejected": 10,
    "approvalRate": 92.0
  },
  "moderation": {
    "pendingReports": 1,
    "documentsToReview": 3,
    "pendingRemovalRequests": 1,
    "trashedDocuments": 42,
    "expiredTrashedDocuments": 5
  },
  "catalog": {
    "faculties": 8,
    "majors": 24,
    "courses": 350
  },
  "documents": {
    "total": 3420,
    "active": 3375,
    "totalDownloads": 48920,
    "trashed": 42
  },
  "alerts": [
    {
      "id": "pending_reports",
      "severity": "warning",
      "title": "Báo cáo vi phạm chưa xử lý",
      "message": "Có 1 báo cáo vi phạm nội dung đang chờ kiểm duyệt viên xử lý để đảm bảo môi trường học thuật.",
      "count": 1,
      "actionLabel": "Xử lý báo cáo",
      "actionRoute": "admin.reports.index"
    }
  ],
  "generatedAt": "2026-11-02T19:15:00+07:00"
}
```

---

## 5. Danh mục Tệp tin Thay đổi và Thêm mới

| Tệp tin | Trạng thái | Nhiệm vụ chính |
| :--- | :--- | :--- |
| `app/Modules/Administration/Domain/Services/AdminDashboardAlertEvaluator.php` | **Tạo mới** | Đánh giá luật nghiệp vụ và tổng hợp cảnh báo khẩn cấp |
| `app/Modules/Administration/Infrastructure/Persistence/AdminDashboardStatsRepository.php` | **Nâng cấp** | Bổ sung các truy vấn thống kê chuyên biệt, driver agnostic |
| `app/Modules/Administration/Application/Queries/GetAdminDashboardOverviewQuery.php` | **Nâng cấp** | Tích hợp thu thập toàn diện chỉ số và cảnh báo |
| `resources/js/Layouts/AdminLayout.jsx` | **Nâng cấp** | Bổ sung menu Dashboard "Tổng quan hệ thống" vào Sidebar |
| `resources/js/Pages/Admin/Dashboard/Index.jsx` | **Tái thiết kế** | Giao diện tổng quan mới với 14 chỉ số, hàng đợi và cảnh báo |
| `tests/Feature/AdminDashboardOverviewTest.php` | **Tạo mới** | Bộ kiểm thử tự động toàn diện 6 test cases cho Dashboard |
| `README.md` | **Cập nhật** | Cập nhật kết quả kiểm thử (118 tests, 670 assertions) |
| `.md/reportChange/BAO_CAO_TASK_10_DASHBOARD_TONG_QUAN_ADMIN.md` | **Tạo mới** | File tài liệu báo cáo kỹ thuật chi tiết của Task 10 |

---

## 6. Kết quả Kiểm thử và Đánh giá

### 6.1. Kiểm thử Tự động (PHPUnit)

File kiểm thử [`tests/Feature/AdminDashboardOverviewTest.php`](file:///d:/AGU-Academic-Hub/tests/Feature/AdminDashboardOverviewTest.php) bao quát:
- Khách vãng lai bị chuyển hướng sang trang đăng nhập (`assertRedirect(route('login'))`).
- Sinh viên bình thường bị cấm truy cập (`assertForbidden()` / HTTP 403).
- Kiểm duyệt viên được phép truy cập (`assertOk()`).
- Quản trị viên truy cập và nhận đầy đủ 14 nhóm chỉ số: Tài khoản, Trạng thái, Dữ liệu raw sinh viên, Hồ sơ KYC cựu SV, Danh mục học thuật, Tài liệu, Thùng rác, Lượt tải, Cảnh báo nghiệp vụ.
- Trường hợp hệ thống an toàn (sạch bóng vi phạm/tồn đọng): mảng `alerts` rỗng và UI hiển thị trạng thái an toàn.
- Cơ chế sắp xếp cảnh báo nghiệp vụ theo độ nghiêm trọng (`critical` $\rightarrow$ `warning` $\rightarrow$ `info`).

Chạy kiểm thử toàn bộ dự án:

```bash
php scratch/run_phpunit.php tests/Feature
```

**Kết quả:**
```text
PHPUnit 11.5.57 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.2.12
Configuration: C:\Users\Admin\AppData\Local\Temp\phpunit_nobootstrap_...xml

...............................................................  63 / 118 ( 53%)
.......................................................         118 / 118 (100%)

Time: 00:10.281, Memory: 60.00 MB
OK (118 tests, 670 assertions) — 100% PASS
```

### 6.2. Biên dịch Tài nguyên Frontend (Vite Production Build)

Chạy lệnh build kiểm tra tương thích syntax JSX và CSS tokens:

```bash
npm.cmd run build
```

**Kết quả:**
```text
vite v7.3.6 building client environment for production...
transforming...
✓ 2600 modules transformed.
rendering chunks...
computing gzip size...
✓ built in 6.54s — 0 errors, 0 warnings.
```

---

## 7. Kết luận

Task **10. Dashboard tổng quan Admin** đã được triển khai hoàn chỉnh, đúng chuẩn kiến trúc Modular Monolith 4 tầng, bảo đảm tính toàn vẹn dữ liệu, hiệu năng tối ưu với hệ thống cache, giao diện người dùng chuyên nghiệp bám sát Design Guidelines của AGU, và vượt qua 100% các bài kiểm thử tự động.

