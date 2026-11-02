# PHẦN 2: TÀI LIỆU THIẾT KẾ (DESIGN SPECIFICATION)

### 1\. Thiết Kế Kiến Trúc Hệ Thống (System Architecture)

Hệ thống được thiết kế theo mô hình **Modular Monolith 4 tầng** chuẩn Domain-Driven Design (DDD) trên nền tảng **Laravel 11** kết hợp **Inertia.js v2 / React 18+** và **Tailwind CSS v4**. Toàn bộ mã nguồn backend được phân rã thành **7 Bounded Contexts** độc lập, bóc tách triệt để tình trạng "Fat Controller" và đảm bảo tính cô lập nghiệp vụ cao.

``` mermaid
graph TD
    subgraph "Client Tier (Giao diện Người dùng)"
        A1[React 18+ Components / Inertia v2 Pages]
        A2[Tailwind CSS v4 / Ziggy Route Helper]
    end

    subgraph "Routing & Global Middleware Tier"
        B1[Web Routes / Route Pipeline]
        B2[Middleware: CheckRole, EnsurePasswordChanged, HandleInertiaRequests]
    end

    subgraph "Modular Monolith Backend (app/Modules/)"
        subgraph "Tầng 1: Presentation Layer"
            C1[Skinny Controllers ~30-70 dòng]
            C2[Form Requests: Validate & Authorize]
            C3[Eloquent API Resources / Data Transformers]
        end

        subgraph "Tầng 2: Application Layer (Use Cases)"
            D1[Actions: State-changing, DB Transactions, Audit Logs]
            D2[Queries: Read-only, Caching, Pagination, Aggregation]
            D3[DTOs: Data Transfer Objects strictly typed]
        end

        subgraph "Tầng 3: Domain Layer (Pure Business Core)"
            E1[Domain Models / Entities]
            E2[Domain Services / Rule Evaluators]
            E3[Value Objects: ByteSize, KycProofFile]
            E4[Domain Policies & Invariant Rules]
        end

        subgraph "Tầng 4: Infrastructure Layer (Hạ tầng & Ngoại vi)"
            F1[Persistence Repositories: Multi-DB Driver MySQL & SQLite]
            F2[Physical Storage: Stream PDF Flysystem, Private KYC Disk]
            F3[Background Jobs & Asynchronous Queues]
        end
    end

    subgraph "Storage & Database Tier"
        G1[(MySQL 8.0 Production / SQLite Test)]
        G2[Private Storage: storage/app/private/kyc]
        G3[Public Storage: storage/app/public/documents]
        G4[Cache Store / Redis / In-Memory]
    end

    A1 -->|Inertia Visit / Form Request| B1
    B1 --> B2
    B2 --> C1
    C1 --> C2
    C1 --> D1
    C1 --> D2
    D1 --> D3
    D2 --> D3
    D1 --> E2
    D2 --> E2
    D1 --> F1
    D2 --> F1
    D1 --> F2
    E2 --> E1
    E2 --> E3
    F1 --> G1
    F2 --> G2
    F2 --> G3
    D2 --> G4
```

---

### Bảng Phân Rã Chi Tiết 7 Bounded Contexts & Chức Năng Từng Thư Mục

Mỗi Bounded Context được tổ chức độc lập tại `app/Modules/<ModuleName>/` với cấu trúc 4 tầng chuẩn mực:

```text
app/Modules/<ModuleName>/
├── Presentation/Http/
│   ├── Controllers/       // Skinny Controller: Điều phối luồng, gọi Action/Query, trả Inertia Page/JSON
│   ├── Requests/          // Form Requests: Kiểm tra tính hợp lệ dữ liệu và phân quyền đầu vào
│   └── Resources/         // API Resources: Format và ẩn danh dữ liệu trước khi trả về Client
├── Application/
│   ├── Actions/           // Nghiệp vụ thay đổi trạng thái: DB Transaction, ghi log, xóa disk, dispatch job
│   ├── Queries/           // Nghiệp vụ đọc: Query builder, Caching (Cache::remember), phân trang
│   └── DTOs/              // Data Transfer Objects: Đóng gói dữ liệu truyền tải giữa các tầng
├── Domain/
│   ├── Models/            // Eloquent Models hoặc Domain Entities thuộc phân hệ
│   ├── Services/          // Evaluator / Domain Services: Đánh giá luật nghiệp vụ thuần túy
│   ├── ValueObjects/      // Value Objects bất biến (chuyển đổi đơn vị, chuẩn hóa chuỗi)
│   └── Rules/             // Business Rules & Custom Validators nội bộ
├── Infrastructure/
│   ├── Persistence/       // Repositories: Thực thi truy vấn CSDL, tương thích đa driver (MySQL/SQLite)
│   └── Storage/           // Physical Disk Adapters: Lưu trữ tệp riêng tư, Stream PDF, File Hash
├── routes.php             // Khai báo tuyến đường riêng biệt của phân hệ
└── <ModuleName>ServiceProvider.php // Nạp route, đăng ký service provider vào container
```

#### Chi tiết tính năng và nhiệm vụ từng Module:

1. **Administration (`app/Modules/Administration/`)**:
   - `Presentation/Http/`: `AdminDashboardController` (tổng quan hệ thống), `AdminStorageController` (dung lượng lưu trữ), `AdminUserController` (quản trị tài khoản và phân cấp).
   - `Application/`: `GetStorageOverviewQuery`, `GetUnusuallyLargeFilesQuery`, `PurgeExpiredTrashAction`, `UpdateUserStatusAction`, `AssignUserRoleAction`.
   - `Domain/`: `StorageRuleEvaluator`, `UserAccountRuleEvaluator`, `ByteSize` Value Object.
   - `Infrastructure/`: `StorageRepository` (bọc `DB::getDriverName()` hỗ trợ MySQL `DATE_FORMAT` và SQLite `strftime`), `AdminUserRepository`, `StorageAuditDisk`.
   - `routes.php`: Tuyến đường `/quan-tri`, `/quan-tri/luu-tru`, `/quan-tri/nguoi-dung`.

2. **AcademicCatalog (`app/Modules/AcademicCatalog/`)**:
   - `Presentation/Http/`: `AcademicCatalogController` (quản trị), `FacultyController`, `CourseController` (công khai cho sinh viên).
   - `Application/`: `GetAdminCatalogOverviewQuery`, `GetPublicFacultyCoursesQuery`, `CreateFacultyAction`, `UpdateFacultyAction`, `ToggleFacultyStatusAction`, `CreateMajorAction`, `UpdateMajorAction`, `ToggleMajorStatusAction`, `CreateCourseAction`, `UpdateCourseAction`, `ToggleCourseStatusAction`.
   - `Domain/`: `AcademicCatalogRuleEvaluator` (bảo vệ cascading active status: khóa khoa cha thì khóa ngành/môn con).
   - `Infrastructure/`: `FacultyRepository`, `MajorRepository`, `CourseRepository`.
   - `routes.php`: Tuyến đường `/khoa`, `/mon-hoc`, `/quan-tri/danh-muc/*`.

3. **Identity (`app/Modules/Identity/`)**:
   - `Presentation/Http/`: `AuthenticatedSessionController`, `RegisteredUserController`, `AlumniRegisterController`, `FirstTimePasswordController`, `PasswordResetLinkController`, `NewPasswordController`, `ConfirmablePasswordController`, `PasswordController`, `EmailVerificationPromptController`, `VerifyEmailController`, `EmailVerificationNotificationController`, `ProfileController`, `KycController`.
   - `Application/`: `AuthenticateUserAction`, `RegisterStudentAction`, `SubmitAlumniKycAction`, `ChangeFirstTimePasswordAction`, `ApproveKycAction`, `RejectKycAction`, `UpdateProfileInformationAction`, `DeleteUserAccountAction`, `GetKycQueueQuery`.
   - `Domain/`: `IdentityRuleEvaluator` (ràng buộc email AGU, trạng thái tài khoản), `AlumniKycRuleEvaluator`, `KycProofFile` Value Object.
   - `Infrastructure/`: `IdentityRepository` (đối soát bảng `danh_sach_sinh_vien_raw`), `KycRepository`, `PrivateKycStorage` (lưu trữ tệp riêng tư, sinh Signed URL 30 phút).
   - `routes.php`: Tuyến đường xác thực, đăng ký cựu SV, hồ sơ cá nhân và quản trị KYC.

4. **Documents (`app/Modules/Documents/`)**:
   - `Presentation/Http/`: `DocumentController` (danh sách, chi tiết, tải lên, xem trước PDF, tải về, upvote, tác giả xóa).
   - `Application/`: `UploadDocumentAction`, `DownloadDocumentAction`, `PreviewDocumentAction`, `UpvoteDocumentAction`, `AuthorSoftDeleteDocumentAction`, `GetDocumentsQuery`, `CheckDocumentDuplicationQuery`.
   - `Domain/`: `DocumentDeduplicationEvaluator` (lọc trùng Lớp 1: SHA-256; Lớp 2: SimHash > 85%), `DocumentAuthorRemovalEvaluator` (luật xóa 24h & 0 downloads).
   - `Infrastructure/`: `DocumentRepository`, `DocumentFileStorage` (Stream PDF Flysystem chống tải trực tiếp đường dẫn gốc).
   - `routes.php`: Tuyến đường `/tai-lieu/*`.

5. **Moderation (`app/Modules/Moderation/`)**:
   - `Presentation/Http/`: `DocumentModerationController` (hàng đợi duyệt tài liệu), `DocumentReportController` (báo cáo vi phạm), `DocumentRemovalRequestController` (yêu cầu gỡ bỏ từ tác giả), `TrashManagementController` (thùng rác 60 ngày).
   - `Application/`: `ApproveDocumentAction`, `RejectDocumentAction`, `ResolveReportAction`, `DismissReportAction`, `ApproveRemovalRequestAction`, `RejectRemovalRequestAction`, `RestoreDocumentAction`, `ForceDeleteDocumentAction`, `GetModerationQueueQuery`, `GetReportsQueueQuery`, `GetRemovalRequestsQuery`, `GetTrashQueueQuery`.
   - `Domain/`: `ModerationDecisionEvaluator`, `DocumentReportRecalculator` (ngưỡng tự động ẩn tài liệu khi nhận đủ ≥ 3 báo cáo vi phạm).
   - `Infrastructure/`: `ModerationReportRepository`, `RemovalRequestRepository`, `TrashManagementRepository`.
   - `routes.php`: Tuyến đường `/quan-tri/kiem-duyet/*`, `/bao-cao-vi-pham/*`, `/tai-lieu/{id}/yeu-cau-go-bo`.

6. **Reviews (`app/Modules/Reviews/`)**:
   - `Presentation/Http/`: `CourseReviewController` (danh sách đánh giá môn học, đăng đánh giá, toggle vote hữu ích).
   - `Application/`: `CreateCourseReviewAction`, `ToggleHelpfulVoteAction`, `GetCourseReviewsQuery`.
   - `Domain/`: `CourseReviewRatingEvaluator` (tổng hợp 3 chỉ số sao: độ khó, thực tiễn, khối lượng bài tập), `ProfanityFilterEvaluator` (chuẩn hóa leetspeak, phát hiện từ cấm kèm fallback CSDL).
   - `Infrastructure/`: `CourseReviewRepository` (lọc bỏ định danh khi `la_an_danh = true`, ngăn tự vote bài đánh giá của chính mình).
   - `routes.php`: Tuyến đường `/mon-hoc/{courseId}/danh-gia/*`.

7. **Bounties (`app/Modules/Bounties/`)**:
   - `Presentation/Http/`: `WishlistController` (danh sách yêu cầu tài liệu, tạo yêu cầu, chi tiết, nộp tài liệu giải quyết, xác nhận trả thưởng).
   - `Application/`: `CreateWishlistRequestAction`, `FulfillWishlistRequestAction`, `ConfirmWishlistResolutionAction`, `GetWishlistRequestsQuery`, `GetWishlistDetailQuery`.
   - `Domain/`: `BountyEscrowEvaluator` (ràng buộc số dư ví, chống nộp giải quyết bài của chính mình, cơ chế khóa điểm `TAM_GIU`).
   - `Infrastructure/`: `WishlistRepository`, `BountyWalletRepository` (khóa dòng bi quan `lockForUpdate`, ghi sổ cái `giao_dich_diem_thuong` với khóa chống trùng).
   - `routes.php`: Tuyến đường `/yeu-cau-tai-lieu/*`.

-----

### 2\. Thiết Kế Cơ Sở Dữ Liệu (Database Design & ERD)

#### A. Sơ đồ Quan hệ Thực thể (Entity Relationship Diagram - ERD)

``` mermaid
erDiagram
    KHOA ||--o{ CHUYEN_NGANH : "Có các ngành"
    KHOA ||--o{ NGUOI_DUNG : "Thuộc khoa"
    CHUYEN_NGANH ||--o{ MON_HOC : "Chứa môn học"
    KHOA ||--o{ MON_HOC : "Quản lý môn"
    DANH_SACH_SINH_VIEN_RAW ||--o| NGUOI_DUNG : "Đối soát đăng nhập lần đầu"
    NGUOI_DUNG ||--o| HO_SO_CUU_SINH_VIEN : "Nộp KYC"
    NGUOI_DUNG ||--o{ TAI_LIEU : "Đăng tài liệu"
    NGUOI_DUNG ||--o{ DANH_GIA_MON_HOC : "Viết đánh giá"
    NGUOI_DUNG ||--o{ YEU_CAU_TAI_LIEU : "Tạo yêu cầu"
    NGUOI_DUNG ||--o{ BAO_CAO_VI_PHAM : "Báo cáo"
    NGUOI_DUNG ||--o{ YEU_CAU_GO_BO : "Xin gỡ bài"
    NGUOI_DUNG ||--o{ GIAO_DICH_DIEM_THUONG : "Giao dịch ví"
    MON_HOC ||--o{ TAI_LIEU : "Có tài liệu"
    MON_HOC ||--o{ DANH_GIA_MON_HOC : "Có đánh giá"
    MON_HOC ||--o{ YEU_CAU_TAI_LIEU : "Có yêu cầu"
    TAI_LIEU ||--o{ TAI_LIEU : "Phiên bản cha-con"
    TAI_LIEU ||--o{ BAO_CAO_VI_PHAM : "Bị báo cáo"
    TAI_LIEU ||--o{ YEU_CAU_GO_BO : "Bị xin gỡ"
    TAI_LIEU ||--o| YEU_CAU_TAI_LIEU : "Giải quyết yêu cầu"
    TAI_LIEU ||--o| LICH_SU_THUNG_RAC : "Audit khi xóa vĩnh viễn"
```

#### Chi tiết bảng dữ liệu cốt lõi:

**1. Bảng khoa (Danh mục Khoa / Bộ môn)**
- `ma_khoa`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ky_hieu_khoa`: VARCHAR(20), Duy nhất (CNTT, KT, SP...).
- `ten_khoa`: VARCHAR(255).
- `trang_thai`: ENUM('HOAT_DONG', 'TAM_KHOA'), Mặc định 'HOAT_DONG'.

**2. Bảng chuyen_nganh (Chuyên ngành đào tạo)**
- `ma_chuyen_nganh`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_khoa`: Khóa ngoại liên kết bảng khoa.
- `ten_chuyen_nganh`: VARCHAR(255).
- `trang_thai`: ENUM('HOAT_DONG', 'TAM_KHOA'), Mặc định 'HOAT_DONG'.

**3. Bảng nguoi_dung (Tài khoản Người dùng)**
- `ma_nguoi_dung`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_khoa`: Khóa ngoại liên kết bảng khoa, Có thể rỗng (Admin/Mod = NULL).
- `ho_ten`: VARCHAR(255).
- `email`: VARCHAR(255), Duy nhất.
- `ma_so_sinh_vien`: VARCHAR(50), Duy nhất, Có thể rỗng.
- `mat_khau`: VARCHAR(255) (Bcrypt).
- `vai_tro`: ENUM('KHACH', 'SINH_VIEN', 'CUU_SINH_VIEN', 'KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN').
- `cap_quan_tri`: ENUM('THUONG', 'TOI_CAO'), Chỉ áp dụng khi `vai_tro = QUAN_TRI_VIEN`.
- `trang_thai`: ENUM('CHO_XAC_THUC', 'HOAT_DONG', 'TAM_KHOA').
- `diem_uy_tin`: INT, Mặc định 100.
- `diem_tam_giu`: INT, Mặc định 0 (Dùng ký quỹ Wishlist).
- `xac_thuc_email_luc`: TIMESTAMP NULL (Đồng bộ `email_verified_at`).
- `mat_khau_mac_dinh`: BOOLEAN, True khi chưa đổi mật khẩu lần đầu.
- `created_at, updated_at, deleted_at`: Dấu mốc thời gian (Hỗ trợ Soft Delete).

**4. Bảng danh_sach_sinh_vien_raw (Dữ liệu sinh viên đang học do trường nạp)**
- `ma_ban_ghi`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `mssv`: VARCHAR(50), Duy nhất.
- `ho_ten`: VARCHAR(255).
- `email_sv`: VARCHAR(255), Duy nhất.
- `mat_khau_mac_dinh`: VARCHAR(255).
- `ma_khoa`: VARCHAR(50).
- `da_kich_hoat`: BOOLEAN, Mặc định false.
- `kich_hoat_luc`: TIMESTAMP NULL.

**5. Bảng ho_so_cuu_sinh_vien (Hồ sơ xác thực KYC Cựu Sinh Viên)**
- `ma_ho_so`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_nguoi_dung`: Khóa ngoại liên kết bảng nguoi_dung.
- `nam_tot_nghiep`: INT.
- `duong_dan_minh_chung`: VARCHAR(500) (Lưu tại `storage/app/private/kyc/`).
- `trang_thai_duyet`: ENUM('CHO_DUYET', 'DA_DUYET', 'TU_CHOI'), Mặc định 'CHO_DUYET'.
- `ly_do_tu_choi`: TEXT NULL.
- `nguoi_duyet_id`: Khóa ngoại liên kết bảng nguoi_dung NULL.
- `ngay_duyet`: TIMESTAMP NULL.

**6. Bảng mon_hoc (Danh mục Môn học / Học phần)**
- `ma_mon_hoc`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_khoa`: Khóa ngoại liên kết bảng khoa.
- `ma_chuyen_nganh`: Khóa ngoại liên kết bảng chuyen_nganh NULL.
- `ma_hoc_phan`: VARCHAR(50), Duy nhất (COS101, KTE201...).
- `ten_mon_hoc`: VARCHAR(255).
- `so_tin_chi`: SMALLINT UNSIGNED, Mặc định 3.
- `mo_ta`: TEXT NULL.
- `trang_thai`: ENUM('HOAT_DONG', 'TAM_KHOA'), Mặc định 'HOAT_DONG'.

**7. Bảng tai_lieu (Kho Tài liệu Học tập)**
- `ma_tai_lieu`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_tai_lieu_goc`: Khóa ngoại liên kết chính bảng tai_lieu (Quản lý phiên bản cha-con).
- `ma_mon_hoc`: Khóa ngoại liên kết bảng mon_hoc.
- `ma_nguoi_dang`: Khóa ngoại liên kết bảng nguoi_dung.
- `tieu_de`: VARCHAR(255).
- `duong_dan_tep`: VARCHAR(500).
- `dinh_dang_tep`: VARCHAR(50) (pdf, docx, zip...).
- `dung_luong_kb`: INT UNSIGNED.
- `ma_bam_file`: VARCHAR(64) (Mã băm SHA-256 chống tải file trùng nhị phân 100%).
- `chu_ky_noi_dung`: VARCHAR(64) NULL (Mã băm SimHash tương đồng nội dung văn bản).
- `phan_loai`: ENUM('DE_THI', 'BAI_GIANG', 'DO_AN', 'BAI_TAP', 'KHAC').
- `phien_ban`: VARCHAR(20), Mặc định '1.0'.
- `luot_tai`: INT UNSIGNED, Mặc định 0.
- `luot_thich`: INT UNSIGNED, Mặc định 0.
- `trang_thai`: ENUM('CHO_DUYET', 'DA_DUYET', 'TAM_AN_KIEM_TRA', 'TU_CHOI').
- `created_at, updated_at, deleted_at`: Dấu mốc thời gian (Soft Delete lưu trữ 60 ngày).

**8. Bảng danh_gia_mon_hoc (Đánh giá Môn học)**
- `ma_danh_gia`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_mon_hoc`: Khóa ngoại liên kết bảng mon_hoc.
- `ma_nguoi_danh_gia`: Khóa ngoại liên kết bảng nguoi_dung.
- `diem_do_kho`: TINYINT (1-5 sao).
- `diem_thuc_tien`: TINYINT (1-5 sao).
- `diem_ap_luc_bai_tap`: TINYINT (1-5 sao).
- `nhan_xet`: TEXT.
- `la_an_danh`: BOOLEAN, Mặc định true.
- `luot_huu_ich`: INT UNSIGNED, Mặc định 0.
- `created_at, updated_at, deleted_at`: Dấu mốc thời gian.

**9. Bảng yeu_cau_tai_lieu (Yêu cầu Tìm Tài liệu / Wishlist & Bounty)**
- `ma_yeu_cau`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_mon_hoc`: Khóa ngoại liên kết bảng mon_hoc.
- `ma_nguoi_yeu_cau`: Khóa ngoại liên kết bảng nguoi_dung.
- `tieu_de`: VARCHAR(255).
- `chi_tiet_yeu_cau`: TEXT.
- `diem_thuong`: INT UNSIGNED, Mặc định 50.
- `trang_thai`: ENUM('DANG_MO', 'DA_GIAI_QUYET', 'DA_DONG'), Mặc định 'DANG_MO'.
- `ma_tai_lieu_giai_quyet`: Khóa ngoại liên kết bảng tai_lieu NULL.
- `nguoi_giai_quyet_id`: Khóa ngoại liên kết bảng nguoi_dung NULL.

**10. Bảng bao_cao_vi_pham (Báo cáo Vi phạm Tài liệu)**
- `ma_bao_cao`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_tai_lieu`: Khóa ngoại liên kết bảng tai_lieu.
- `ma_nguoi_bao_cao`: Khóa ngoại liên kết bảng nguoi_dung.
- `ly_do`: ENUM('TRUNG_LAP', 'SAI_NOI_DUNG', 'MA_DOC', 'BAN_QUYEN', 'KHAC').
- `chi_tiet_loi`: TEXT NULL.
- `trang_thai_xu_ly`: ENUM('CHO_XU_LY', 'DA_XU_LY', 'BAC_BO'), Mặc định 'CHO_XU_LY'.

**11. Bảng yeu_cau_go_bo (Yêu cầu Gỡ bỏ Tài liệu từ Tác giả)**
- `ma_yeu_cau`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_tai_lieu`: Khóa ngoại liên kết bảng tai_lieu.
- `ma_nguoi_yeu_cau`: Khóa ngoại liên kết bảng nguoi_dung.
- `ly_do`: TEXT.
- `trang_thai`: ENUM('CHO_XU_LY', 'DA_DUYET', 'TU_CHOI'), Mặc định 'CHO_XU_LY'.

**12. Bảng lich_su_thung_rac (Audit Log Dọn dẹp & Xóa Vĩnh viễn)**
- `id`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_tai_lieu`: BIGINT UNSIGNED.
- `tieu_de`: VARCHAR(255).
- `dung_luong_kb`: INT UNSIGNED.
- `nguoi_thuc_hien`: VARCHAR(255).
- `ly_do_xoa`: VARCHAR(500).
- `thoi_diem_xoa`: TIMESTAMP.

**13. Bảng giao_dich_diem_thuong (Sổ cái Điểm thưởng & Ký quỹ)**
- `ma_giao_dich`: Khóa chính, BIGINT UNSIGNED, Tự tăng.
- `ma_nguoi_dung`: Khóa ngoại liên kết bảng nguoi_dung.
- `loai_giao_dich`: ENUM('CONG_DIEM', 'TRU_DIEM', 'TAM_GIU', 'HOAN_TRA').
- `so_diem`: INT.
- `so_du_sau`: INT.
- `khoa_chong_trung`: VARCHAR(100) UNIQUE NULL (Idempotency Key).
- `mo_ta`: VARCHAR(255).

---

### Cơ Chế Tương Thích Đa Driver CSDL (Multi-DB Query Compatibility)

Nhằm đảm bảo 100% các bộ kiểm thử tự động (Unit / Feature Tests) chạy mượt mà trên bộ nhớ tạm SQLite (`:memory:`) trong khi môi trường Production vận hành trên MySQL 8.0, tầng `Infrastructure/Persistence/` sử dụng cơ chế bọc kiểm tra `DB::getDriverName()`:
- **MySQL Driver:** Sử dụng hàm ngày tháng chuẩn `DATE_FORMAT(created_at, '%Y-%m')` hoặc `%Y-%m-%d`.
- **SQLite Driver:** Tự động fallback sang cú pháp `strftime('%Y-%m', created_at)` hoặc `strftime('%Y-%m-%d', created_at)`.
- Nhờ cơ chế này, toàn bộ 21 test suites (112 tests, 558 assertions) luôn pass 100% mà không phụ thuộc vào driver vật lý cụ thể.

-----

### 3\. Thiết Kế Giao Diện (UI/UX Design)

#### A. Hệ thống Nhận diện Trực quan (Design System / Style Guide)

  * **Primary Color:** \#005baa (Deep Blue)
  * **Secondary Color:** \#00a896 (Teal Green)
  * **Accent/Warning Color:** \#f48c06 (Amber) & \#e63946 (Đỏ cảnh báo)
  * **Neutral Background:** \#f8fafc (Light Mode), \#0f172a (Dark Mode)
  * **Typography:** Inter / Be Vietnam Pro

#### B. Bản vẽ Giao diện (Wireframe Layout)

  * **Màn hình Kho Môn Học & Tài Liệu (Desktop Hub View):** Hiển thị danh mục khoa bên trái, danh sách tài liệu chi tiết của môn học ở giữa, kèm thông tin giảng viên và các tab chức năng (Kho tài liệu, Review, Wishlist).
  * **Màn hình Đánh giá Môn học (Course Review Form):** Cửa sổ Modal hiển thị thang điểm cho các tiêu chí: Độ khó, Tính thực tiễn, Khối lượng bài tập. Hỗ trợ tùy chọn ẩn danh 2 lớp cho người dùng.
## Thiết kế phân cấp Admin

Thiết kế RBAC không tạo thêm vai trò `SUPER_ADMIN`. Thay vào đó, `QUAN_TRI_VIEN` là vai trò nền và `cap_quan_tri` là cấp quản trị. Cách này giữ tương thích với các middleware đang kiểm tra `QUAN_TRI_VIEN`, đồng thời cho phép giới hạn thao tác nhạy cảm ở controller.

Luồng xử lý:

1. Người dùng đăng nhập được nhận `vai_tro`, `cap_quan_tri` và các cờ quyền qua Inertia shared props.
2. Controller xác thực Admin trước mọi endpoint quản trị.
3. `canManageTarget()` chặn Admin thường tác động đến Moderator/Admin.
4. Chỉ Super Admin được cấp vai trò quản trị hoặc thay đổi cấp `THUONG`/`TOI_CAO`.
5. Giao dịch cập nhật ghi trước lịch sử phân quyền, sau đó mới cập nhật người dùng.
6. Super Admin cuối cùng không thể bị hạ cấp.
