# AGU Academic Hub

Nền tảng tri thức và cộng đồng sinh viên nội bộ của Đại học An Giang (AGU).

## 1. Tech Stack

- **Backend:** Laravel 11, PHP 8.2+, Modular Monolith 4 tầng (Domain-Driven Design).
- **Frontend:** React 18+, Inertia.js v2, Vite.
- **Styling:** Tailwind CSS v4 qua `@theme` trong `resources/css/app.css`.
- **Routing frontend:** Inertia `Link`/`router` kết hợp Ziggy `route()`.
- **Database:** MySQL 8.0 (Production) / SQLite in-memory (Testing).
- **Queue:** Database Queue mặc định; Redis phù hợp cho production.
- **Icons:** Lucide React.

Các tài liệu đặc tả nằm trong thư mục `.md/`:

- [Yêu cầu dự án](.md/TaiLieuYeuCau.md)
- [Thiết kế hệ thống](.md/TaiLieuThietKe.md)
- [Tài liệu kỹ thuật](.md/TaiLieuKyThuat.md)
- [Design Guidelines](.md/DESIGN_GUIDELINES.md)
- [Báo cáo Tái cấu trúc Toàn diện 6 Phase](.md/reportChange/BAO_CAO_TAI_CAU_TRUC_MODULAR_MONOLITH.md)

## 2. Kiến trúc tổng thể (Modular Monolith 4 Tầng)

Hệ thống được tổ chức theo mô hình **Modular Monolith 4 tầng** chuẩn Domain-Driven Design (DDD), phân chia rõ ranh giới giữa 7 Bounded Contexts nghiệp vụ:

```text
Browser (Client Tier)
   │
   ▼
Laravel Web Routes / Middleware Pipeline (CheckRole, EnsurePasswordChanged, HandleInertiaRequests)
   │
   ▼
[Presentation Layer] (Skinny Controllers ~30-70 dòng, Form Requests, Eloquent Resources)
   │
   ▼
[Application Layer] (Use Cases: Actions ghi DB/Transaction, Queries đọc dữ liệu/Cache, DTOs)
   │
   ▼
[Domain Layer] (Domain Services / Rule Evaluators, Value Objects, Domain Policies, Domain Rules)
   │
   ▼
[Infrastructure Layer] (Persistence Repositories, Physical Storage Adapters, Background Jobs, Multi-DB Query Drivers)
   │
   ▼
MySQL 8.0 / SQLite In-Memory / Private Storage (storage/app/private/) / Cache Store
```

### Nguyên tắc bất biến của kiến trúc

1. **Skinny Controller:** Controller chỉ nhận Request, gọi Application Action hoặc Query, và trả về Inertia Page Component hoặc JsonResponse. Tuyệt đối không chứa Raw SQL, DB Transaction hay business logic.
2. **Single Responsibility Use Cases:** Mỗi Action chỉ đảm nhiệm một hành vi thay đổi trạng thái (State-changing operation); mỗi Query chỉ phục vụ đọc dữ liệu (Read-only operation) kèm chiến lược Caching phù hợp.
3. **Pure Domain:** Tầng Domain là trung tâm của nghiệp vụ, độc lập hoàn toàn với framework, không import Inertia, React, Blade hoặc HTTP Request.
4. **Data Privacy (PII Protection):** Các tệp tin nhạy cảm (minh chứng KYC, bản scan tốt nghiệp) lưu trữ tại `storage/app/private/` và chỉ được truy xuất qua Temporary Signed URL sống 30 phút. Tài liệu PDF học thuật được phân phối qua luồng stream an toàn chống lộ đường dẫn disk vật lý.
5. **Database Driver Agnostic:** Mọi truy vấn thống kê nhóm theo thời gian đều được bọc kiểm tra driver (`DB::getDriverName()`) để tương thích 100% cả môi trường MySQL (`DATE_FORMAT`) và SQLite test (`strftime`).

## 3. Cây cấu trúc thư mục thực tế

```text
AGU-Academic-Hub/
├── app/                                  # Mã nguồn PHP backend
│   ├── Enums/                            # Enums định danh dùng chung (VaiTroNguoiDung, TrangThaiNguoiDung...)
│   ├── Http/                             # Lớp HTTP toàn cục
│   │   ├── Controllers/                  # Controller dùng chung (Controller.php, DashboardController.php)
│   │   └── Middleware/                   # Middleware (CheckRole, EnsurePasswordChanged, HandleInertiaRequests...)
│   ├── Models/                           # Eloquent Models thực thể CSDL (NguoiDung, TaiLieu, MonHoc, Khoa...)
│   ├── Modules/                          # 7 Bounded Contexts độc lập của hệ thống
│   │   ├── Administration/               # [Module 1] Quản trị hệ thống, dung lượng Disk, người dùng & dashboard
│   │   │   ├── Application/              # Use cases: Actions (PurgeExpiredTrash, UpdateUserStatus, AssignUserRole...) & Queries
│   │   │   ├── Domain/                   # StorageRuleEvaluator, UserAccountRuleEvaluator, ByteSize Value Object
│   │   │   ├── Infrastructure/           # StorageRepository (hỗ trợ đa driver CSDL), AdminUserRepository, StorageAuditDisk
│   │   │   ├── Presentation/Http/        # AdminStorageController, AdminUserController, AdminDashboardController
│   │   │   ├── routes.php                # Tuyến đường /quan-tri/luu-tru, /quan-tri/nguoi-dung, /quan-tri
│   │   │   └── AdministrationServiceProvider.php
│   │   ├── AcademicCatalog/              # [Module 2] Danh mục đào tạo: Khoa, Chuyên ngành & Môn học
│   │   │   ├── Application/              # Actions tạo/sửa/toggle trạng thái & Queries lấy danh mục Admin/Public
│   │   │   ├── Domain/                   # AcademicCatalogRuleEvaluator (luật ràng buộc trạng thái cha-con)
│   │   │   ├── Infrastructure/           # FacultyRepository, MajorRepository, CourseRepository
│   │   │   ├── Presentation/Http/        # AcademicCatalogController (Admin), FacultyController & CourseController (Public)
│   │   │   ├── routes.php                # Tuyến đường /khoa, /mon-hoc, /quan-tri/danh-muc/*
│   │   │   └── AcademicCatalogServiceProvider.php
│   │   ├── Identity/                     # [Module 3] Xác thực, Đăng ký, Vòng đời mật khẩu, Profile & Cựu SV KYC
│   │   │   ├── Application/              # Actions (AuthenticateUser, RegisterStudent, SubmitKyc, ApproveKyc, RejectKyc...) & Queries
│   │   │   ├── Domain/                   # IdentityRuleEvaluator (email AGU, trạng thái khóa), AlumniKycRuleEvaluator, KycProofFile
│   │   │   ├── Infrastructure/           # IdentityRepository (đối soát dữ liệu thô sinh viên), KycRepository, PrivateKycStorage
│   │   │   ├── Presentation/Http/        # AuthenticatedSessionController, ProfileController, KycController, PasswordControllers...
│   │   │   ├── routes.php                # Tuyến đường /dang-nhap, /dang-xuat, /dang-ky, /ho-so, /cuu-sinh-vien/dang-ky...
│   │   │   └── IdentityServiceProvider.php
│   │   ├── Documents/                    # [Module 4] Đăng tải, Phiên bản, Stream PDF, Upvote & Tải về tài liệu
│   │   │   ├── Application/              # Actions (UploadDocument, DownloadDocument, PreviewDocument...) & Queries kiểm tra trùng
│   │   │   ├── Domain/                   # DocumentDeduplicationEvaluator (SHA-256 + SimHash), DocumentAuthorRemovalEvaluator
│   │   │   ├── Infrastructure/           # DocumentRepository, DocumentFileStorage (Stream PDF Flysystem)
│   │   │   ├── Presentation/Http/        # DocumentController
│   │   │   ├── routes.php                # Tuyến đường /tai-lieu/*
│   │   │   └── DocumentsServiceProvider.php
│   │   ├── Moderation/                   # [Module 5] Hàng đợi duyệt tài liệu, Xử lý vi phạm, Gỡ bỏ & Thùng rác 60 ngày
│   │   │   ├── Application/              # Actions giải quyết báo cáo, phê duyệt tài liệu, khôi phục/xóa vĩnh viễn & Queries hàng đợi
│   │   │   ├── Domain/                   # ModerationDecisionEvaluator, DocumentReportRecalculator
│   │   │   ├── Infrastructure/           # ModerationReportRepository, RemovalRequestRepository, TrashManagementRepository
│   │   │   ├── Presentation/Http/        # DocumentModerationController, DocumentReportController, DocumentRemovalRequestController
│   │   │   ├── routes.php                # Tuyến đường /quan-tri/bao-cao, /quan-tri/yeu-cau-go-tai-lieu, /quan-tri/tai-lieu/*
│   │   │   └── ModerationServiceProvider.php
│   │   ├── Reviews/                      # [Module 6] Đánh giá môn học, Xếp hạng 3 tiêu chí, Lọc từ cấm & Ẩn danh
│   │   │   ├── Application/              # Actions (StoreReview, UpdateReview, DeleteReview, ToggleHelpful, ReportReview) & Queries
│   │   │   ├── Domain/                   # ProfanityFilterEvaluator (Lọc từ cấm, Leetspeak regex), CourseReviewRuleEvaluator
│   │   │   ├── Infrastructure/           # CourseReviewRepository
│   │   │   ├── Presentation/Http/        # CourseReviewController, CourseReviewResource (Ẩn danh 2 lớp bảo vệ PII)
│   │   │   ├── routes.php                # Tuyến đường /mon-hoc/{ma_hoc_phan}/danh-gia, /danh-gia/*
│   │   │   └── ReviewsServiceProvider.php
│   │   └── Bounties/                     # [Module 7] Wishlist tìm tài liệu, Đóng băng & Giải ngân điểm Bounty, Ví điểm
│   │       ├── Application/              # Actions (CreateBounty, FulfillBounty, ConfirmBounty, CancelBounty) & Queries
│   │       ├── Domain/                   # ReputationEvaluator (Lock ví lockForUpdate, Idempotency, Escrow, Release, Refund)
│   │       ├── Infrastructure/           # WishlistBountyRepository
│   │       ├── Presentation/Http/        # WishlistBountyController, WalletController
│   │       ├── routes.php                # Tuyến đường /yeu-cau-tai-lieu/*, /vi-diem
│   │       └── BountiesServiceProvider.php
│   ├── Providers/                        # AppServiceProvider (đăng ký 7 Module Service Providers)
│   └── Shared/                           # Hỗ trợ dùng chung (Base Module, backward-compatible service adapters)
├── bootstrap/                            # Bootstrap vòng đời framework
│   ├── app.php                           # Định nghĩa middleware, routing (web, console, health)
│   └── providers.php
├── config/                               # Cấu hình framework và modules
├── database/                             # Migrations, seeders, factories
├── public/build/                         # Production assets đã build qua Vite
├── resources/
│   ├── css/app.css                       # Tailwind CSS v4 design tokens (@theme)
│   └── js/                               # Ứng dụng Inertia React
│       ├── Components/                   # UI primitives & Domain components
│       ├── Layouts/                      # GuestLayout, StudentLayout, AdminLayout
│       └── Pages/                        # React Page Components tương ứng từng route
├── routes/
│   ├── web.php                           # Web entry route (/, /trang-chu)
│   └── console.php
└── tests/
    └── Feature/                          # 21 bộ kiểm thử tự động toàn diện (112 tests, 558 assertions)
```

## 4. Chạy kiểm thử và Build hệ thống

### 4.1. Kiểm thử tự động (PHPUnit)
Hệ thống tích hợp bộ runner kiểm thử độc lập hỗ trợ môi trường Windows token sandbox:

```bash
php scratch/run_phpunit.php tests/Feature
```

Kết quả: **118/118 tests passed (670 assertions) — 100% OK**.

### 4.2. Biên dịch Frontend (Vite Production Build)

```bash
npm run build
```

Kết quả: **2600 modules transformed, 0 lỗi, 0 cảnh báo breaking syntax**.

### 4.3. Kiểm tra danh sách tuyến đường (Routes List)

```bash
php artisan route:list
```

Tất cả 114 endpoints của hệ thống đều được nạp đầy đủ qua 7 Module Service Providers tương ứng.

## 5. Trạng thái dự án

Hệ thống đã **hoàn thành 100% công tác tái cấu trúc toàn diện qua 6 giai đoạn**:
- [x] **Phase 1: Administration** (Thống kê lưu trữ đa driver CSDL, Quản lý tài khoản, Admin Dashboard 14 chỉ số & cảnh báo).
- [x] **Phase 2: AcademicCatalog** (Khoa, Chuyên ngành, Môn học Public & Admin).
- [x] **Phase 3: Identity & KYC** (Đăng ký Cựu SV KYC, Hàng đợi duyệt, Signed URL xem ảnh 30 phút, Đổi mật khẩu lần đầu).
- [x] **Phase 4: DocumentManagement & Moderation** (Upload, Lọc trùng SHA-256 + SimHash, Stream PDF, Upvote, Hàng đợi duyệt, Thùng rác 60 ngày).
- [x] **Phase 5: Reviews & Bounties** (Đánh giá môn học 3 tiêu chí, Lọc từ cấm Leetspeak, Wishlist, Đóng băng & Giải ngân Bounty Points, Ví điểm).
- [x] **Phase 6: Identity Completeness** (Di chuyển toàn bộ Auth, Session, Registration, Profile vào Identity Module, xóa bỏ 100% Fat Controllers cũ).
