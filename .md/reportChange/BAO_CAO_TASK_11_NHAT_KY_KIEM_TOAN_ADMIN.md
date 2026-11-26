# Báo cáo Triển khai Task 11: Nhật ký Kiểm toán Nghiệp vụ Admin (Audit Log)

**Dự án:** Nền tảng Tri thức và Cộng đồng Sinh viên Nội bộ AGU (AGU Academic Hub)  
**Phân hệ:** Quản trị Hệ thống (`Administration` Bounded Context)  
**Mã Task:** TASK-11  
**Tài liệu tham chiếu:** [Tài liệu Thiết kế Kiến trúc](.md/TaiLieuThietKe.md), [Design Guidelines](.md/DESIGN_GUIDELINES.md), [Báo cáo Tái cấu trúc](.md/reportChange/BAO_CAO_TAI_CAU_TRUC_MODULAR_MONOLITH.md)

---

## 1. Mục tiêu và Yêu cầu Nghiệp vụ

Xây dựng hệ thống Nhật ký kiểm toán nghiệp vụ quản trị (**Business Audit Log**) tập trung, bảo đảm tính toàn vẹn (Integrity), bất biến (Immutability) và khả năng truy vết (Traceability) cho toàn bộ các hoạt động quản trị nhạy cảm trên hệ thống:

1. **Đăng nhập và đăng xuất** khu vực Admin.
2. **Nạp dữ liệu sinh viên thô** vào `danh_sach_sinh_vien_raw` (Batch Import).
3. **Cập nhật hoặc xóa** dữ liệu sinh viên raw.
4. **Xem hồ sơ minh chứng KYC** của cựu sinh viên (bảo vệ dữ liệu nhạy cảm PII theo chuẩn NFR-01).
5. **Duyệt hoặc từ chối** hồ sơ KYC cựu sinh viên.
6. **Khóa hoặc mở khóa** tài khoản người dùng.
7. **Thay đổi vai trò** người dùng / phân quyền / cấp quản trị (Super Admin vs Admin thường).
8. **Xử lý báo cáo vi phạm** (Duyệt ẩn, xóa mềm hoặc bác bỏ báo cáo).
9. **Ẩn, xóa mềm hoặc khôi phục** tài liệu học thuật.
10. **Thay đổi danh mục đào tạo**: Thêm, sửa hoặc đổi trạng thái Khoa, Chuyên ngành và Môn học.
11. **Thay đổi cấu hình hệ thống**: Tham số thùng rác, ngưỡng báo cáo, dung lượng lưu trữ, mở cổng KYC...
12. **Xem ai thực hiện, đối tượng nào bị tác động và thời điểm thực hiện**: Giao diện tra cứu kèm bộ lọc đa tiêu chí và Diff Snapshot chi tiết trước/sau thay đổi.
13. **Bảo đảm người dùng thông thường không thể sửa hoặc xóa audit log**: Ngăn chặn tuyệt đối ở cả tầng phân quyền HTTP Route lẫn tầng ORM Model bất biến (Append-Only).

---

## 2. Kiến trúc 4 Tầng Modular Monolith Chuẩn DDD

Tuân thủ nghiêm ngặt mô hình **Modular Monolith 4 tầng** của bounded context `Administration` và liên kết với các bounded context vệ tinh (`Identity`, `AcademicCatalog`, `Moderation`):

```text
Trình duyệt Quản trị viên (React 18 + Inertia.js v2 + Tailwind CSS v4)
      │
      ▼ (Route: GET /quan-tri/nhat-ky-kiem-toan, Name: admin.audit-logs.index)
[Presentation Layer]
  ├── AdminAuditLogController (Skinny Controller: index, show)
  ├── AdminSystemSettingController (Quản lý tham số cấu hình hệ thống)
  └── AdminRawStudentController (Nạp, cập nhật và xóa roster sinh viên thô)
      │
      ▼ (Gọi Use-cases / Queries / Actions)
[Application Layer]
  ├── GetAuditLogsQuery (Phân trang, bộ lọc đa năng, format ngày giờ và tổng hợp số liệu)
  ├── GetAuditLogDetailQuery (Trích xuất chi tiết bản ghi, đối soát Diff JSON snapshot)
  ├── RecordAuditLogAction (Ghi nhận nhật ký kiểm toán độc lập)
  ├── UpdateSystemSettingAction (Cập nhật cấu hình và tự động kích hoạt audit)
  ├── ImportRawStudentsAction (Nạp danh sách sinh viên thô kèm audit)
  ├── UpdateRawStudentAction (Sửa sinh viên thô kèm audit)
  └── DeleteRawStudentAction (Xóa sinh viên thô kèm audit)
      │
      ▼
[Domain Layer]
  ├── AuditLogger (Domain Service trung tâm chịu trách nhiệm phân tích và ghi nhận)
  ├── HanhDongKiemToan (Enum 22 hành vi kiểm toán chuẩn hóa, nhãn và màu sắc badge)
  └── LoaiDoiTuongKiemToan (Enum 10 nhóm đối tượng bị tác động)
      │
      ▼
[Infrastructure Layer]
  ├── AuditLogRepository (Truy vấn tối ưu, tìm kiếm quan hệ người thực hiện, thống kê)
  ├── SystemSettingRepository (Truy xuất & cập nhật cấu hình hệ thống theo nhóm)
  ├── RawStudentRepository (Truy vấn & xử lý batch roster sinh viên thô)
  └── Model NhatKyKiemToan (Model Eloquent bất biến - Append-Only Immutability Guard)
      │
      ▼
[Database Engine]
  MySQL 8.0 (Production) / SQLite In-Memory (Test Runner)
```

---

## 3. Chi tiết Thành phần Đã Xây dựng & Tái cấu trúc

### 3.1. Thiết kế Cơ sở Dữ liệu (Migrations)

1. **`database/migrations/2026_11_03_000001_create_nhat_ky_kiem_toan_table.php`**:
   - `id`: Khóa chính tự tăng (BigInt unsigned).
   - `ma_nguoi_thuc_hien`: Foreign key trỏ đến `nguoi_dung.ma_nguoi_dung`, `nullOnDelete`.
   - `hanh_dong`: Mã hành động chuẩn hóa (`VARCHAR(50)`).
   - `loai_doi_tuong`: Nhóm đối tượng bị tác động (`VARCHAR(50)`).
   - `ma_doi_tuong`: Định danh đối tượng (`VARCHAR(100)`).
   - `mo_ta`: Mô tả ngôn ngữ tự nhiên tường minh.
   - `du_lieu_cu`: Snapshot dữ liệu JSON trước khi thay đổi.
   - `du_lieu_moi`: Snapshot dữ liệu JSON sau khi thay đổi.
   - `dia_chi_ip`: Địa chỉ IP của client (`VARCHAR(45)`).
   - `user_agent`: Thông tin trình duyệt và thiết bị của người thực hiện.
   - `created_at`: Thời điểm ghi nhận (Bất biến, không tạo cột `updated_at`).
   - Đánh chỉ mục Index tối ưu: `['hanh_dong', 'created_at']`, `['loai_doi_tuong', 'ma_doi_tuong']`, `['ma_nguoi_thuc_hien', 'created_at']`, `created_at`.

2. **`database/migrations/2026_11_03_000002_create_cau_hinh_he_thong_table.php`**:
   - Tạo bảng `cau_hinh_he_thong` quản lý các tham số cấu hình:
     + `documents.trash_retention_days`: Hạn lưu thùng rác (60 ngày).
     + `moderation.auto_hide_report_threshold`: Ngưỡng tự động ẩn tài liệu khi bị báo cáo (3 lượt).
     + `documents.max_upload_size_mb`: Giới hạn dung lượng tải lên (50 MB).
     + `storage.disk_warning_percent`: Ngưỡng cảnh báo vàng dung lượng (80%).
     + `storage.disk_critical_percent`: Ngưỡng nguy cấp đỏ dung lượng (90%).
     + `system.alumni_registration_open`: Trạng thái cổng tiếp nhận KYC cựu sinh viên (bật).

### 3.2. Tầng Domain (Domain Layer)

- **`HanhDongKiemToan.php`**: Khai báo enum chuẩn cho 22 hành vi kiểm toán:
  - Phiên làm việc: `DANG_NHAP_ADMIN`, `DANG_XUAT_ADMIN`.
  - Sinh viên thô: `NAP_SINH_VIEN_RAW`, `CAP_NHAT_SINH_VIEN_RAW`, `XOA_SINH_VIEN_RAW`.
  - KYC Cựu sinh viên: `XEM_HO_SO_KYC`, `DUYET_KYC`, `TU_CHOI_KYC`.
  - Người dùng: `KHOA_TAI_KHOAN`, `MO_KHOA_TAI_KHOAN`, `THAY_DOI_VAI_TRO`, `GHI_NHAN_VI_PHAM`.
  - Kiểm duyệt: `XU_LY_BAO_CAO`, `AN_TAI_LIEU`, `XOA_MEM_TAI_LIEU`, `KHOI_PHUC_TAI_LIEU`, `XOA_VINH_VIEN_TAI_LIEU`.
  - Danh mục đào tạo: `THAY_DOI_KHOA`, `THAY_DOI_CHUYEN_NGANH`, `THAY_DOI_MON_HOC`.
  - Cấu hình & Bảo trì: `THAY_DOI_CAU_HINH`, `DON_DEP_THUNG_RAC`.
- **`LoaiDoiTuongKiemToan.php`**: Phân loại đối tượng: `NGUOI_DUNG`, `SINH_VIEN_RAW`, `YEU_CAU_XAC_THUC`, `BAO_CAO_VI_PHAM`, `TAI_LIEU`, `KHOA`, `CHUYEN_NGANH`, `MON_HOC`, `CAU_HINH_HE_THONG`, `PHIEN_LAM_VIEC`.
- **`AuditLogger.php`**: Domain Service trung tâm cung cấp phương thức `log(...)` tự động bóc tách IP, User-Agent, liên kết Actor và chuẩn hóa Payload snapshot.

### 3.3. Tầng Infrastructure & Cơ chế Bất biến (Immutability Protection)

- **`NhatKyKiemToan.php`**:
  - Triển khai **Model Event Guards**:
    ```php
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new DomainException('Bản ghi nhật ký kiểm toán là bất biến (immutable), không được phép chỉnh sửa.');
        });

        static::deleting(function (): never {
            throw new DomainException('Bản ghi nhật ký kiểm toán là bất biến (immutable), không được phép xóa.');
        });
    }

    public function delete(): ?bool
    {
        throw new DomainException('Bản ghi nhật ký kiểm toán là bất biến (immutable), không được phép xóa.');
    }
    ```
- **`AuditLogRepository.php`**: Tối ưu truy vấn phân trang, tìm kiếm full text (nội dung, mã đối tượng, IP, tên và email người thực hiện) và tính toán 4 chỉ số tổng hợp.
- **`SystemSettingRepository.php` & `RawStudentRepository.php`**: Đảm bảo nghiệp vụ cấu hình và nạp dữ liệu thô vận hành qua Repository Pattern chuẩn mực.

### 3.4. Tầng Application (Application Layer)

- Xây dựng đầy đủ các Use Cases:
  - `GetAuditLogsQuery.php`, `GetAuditLogDetailQuery.php`
  - `RecordAuditLogAction.php`
  - `UpdateSystemSettingAction.php`, `GetSystemSettingsQuery.php`
  - `ImportRawStudentsAction.php`, `UpdateRawStudentAction.php`, `DeleteRawStudentAction.php`, `GetRawStudentsQuery.php`

### 3.5. Tầng Presentation (Presentation Layer)

- **Controllers**:
  - `AdminAuditLogController`: Phục vụ màn hình tra cứu nhật ký và endpoint xem chi tiết Diff JSON.
  - `AdminSystemSettingController`: Cho phép Admin cập nhật tham số cấu hình.
  - `AdminRawStudentController`: Cho phép Admin nạp batch, chỉnh sửa và xóa danh sách sinh viên thô.
- **Frontend Views (React 18 + Inertia.js v2)**:
  - `resources/js/Pages/Admin/AuditLogs/Index.jsx`: Bảng điều khiển tra cứu nhật ký hiện đại, bộ lọc đa năng (từ khóa, hành động, loại đối tượng, khoảng ngày), phân trang và Modal xem Diff JSON trực quan giữa `du_lieu_cu` và `du_lieu_moi`.
  - `resources/js/Pages/Admin/Settings/Index.jsx`: Giao diện chỉnh sửa cấu hình hệ thống phân nhóm trực quan.
  - `resources/js/Pages/Admin/RawStudents/Index.jsx`: Giao diện quản lý danh sách sinh viên thô nạp trước.
- **Sidebar Navigation**: Cập nhật `AdminLayout.jsx` bổ sung 3 lối tắt dành riêng cho Quản trị viên:
  - *Nhật ký kiểm toán* (Icon: `ClipboardList`)
  - *Dữ liệu SV thô* (Icon: `UserCheck`)
  - *Cấu hình hệ thống* (Icon: `Settings`)

---

## 4. Tích hợp Lưu vết Kiểm toán trên Các Nghiệp vụ Toàn Hệ thống

| Nghiệp vụ | Vị trí Tích hợp (Hook Point) | Hành động Ghi nhận | Đối tượng Tác động |
| :--- | :--- | :--- | :--- |
| **Đăng nhập Admin** | `AuthenticatedSessionController::store` | `DANG_NHAP_ADMIN` | `PHIEN_LAM_VIEC` |
| **Đăng xuất Admin** | `AuthenticatedSessionController::destroy` | `DANG_XUAT_ADMIN` | `PHIEN_LAM_VIEC` |
| **Xem hồ sơ KYC cựu SV** | `KycController::xemTruocMinhChung` | `XEM_HO_SO_KYC` | `YEU_CAU_XAC_THUC` |
| **Duyệt hồ sơ KYC** | `ApproveKycAction::execute` | `DUYET_KYC` | `YEU_CAU_XAC_THUC` |
| **Từ chối hồ sơ KYC** | `RejectKycAction::execute` | `TU_CHOI_KYC` | `YEU_CAU_XAC_THUC` |
| **Khóa / Mở khóa TK** | `ToggleUserStatusAction::execute` | `KHOA_TAI_KHOAN` / `MO_KHOA_TAI_KHOAN` | `NGUOI_DUNG` |
| **Thay đổi vai trò / Phân quyền** | `UpdateUserPermissionsAction::execute` | `THAY_DOI_VAI_TRO` | `NGUOI_DUNG` |
| **Ghi nhận vi phạm** | `RecordUserViolationAction::execute` | `GHI_NHAN_VI_PHAM` | `NGUOI_DUNG` |
| **Xử lý báo cáo vi phạm** | `ResolveViolationReportAction::execute` | `XU_LY_BAO_CAO`, `AN_TAI_LIEU` / `XOA_MEM_TAI_LIEU` | `BAO_CAO_VI_PHAM`, `TAI_LIEU` |
| **Kiểm tra tài liệu (Review)** | `DecideDocumentReviewAction::execute` | `AN_TAI_LIEU` / `XOA_MEM_TAI_LIEU` / `KHOI_PHUC_TAI_LIEU` | `TAI_LIEU` |
| **Khôi phục tài liệu đã xóa** | `RestoreDocumentAction::execute` | `KHOI_PHUC_TAI_LIEU` | `TAI_LIEU` |
| **Xóa vĩnh viễn tài liệu** | `PermanentlyDeleteDocumentAction::execute` | `XOA_VINH_VIEN_TAI_LIEU` | `TAI_LIEU` |
| **Thanh lọc thùng rác hết hạn** | `PurgeExpiredTrashAction::execute` | `DON_DEP_THUNG_RAC` | `TAI_LIEU` |
| **Thay đổi Khoa** | `ManageFacultyAction::store/update/toggle` | `THAY_DOI_KHOA` | `KHOA` |
| **Thay đổi Chuyên ngành** | `ManageMajorAction::store/update/toggle` | `THAY_DOI_CHUYEN_NGANH` | `CHUYEN_NGANH` |
| **Thay đổi Môn học** | `ManageCourseAction::store/update/toggle` | `THAY_DOI_MON_HOC` | `MON_HOC` |
| **Nạp dữ liệu SV thô** | `ImportRawStudentsAction::execute` | `NAP_SINH_VIEN_RAW` | `SINH_VIEN_RAW` |
| **Sửa dữ liệu SV thô** | `UpdateRawStudentAction::execute` | `CAP_NHAT_SINH_VIEN_RAW` | `SINH_VIEN_RAW` |
| **Xóa dữ liệu SV thô** | `DeleteRawStudentAction::execute` | `XOA_SINH_VIEN_RAW` | `SINH_VIEN_RAW` |
| **Thay đổi cấu hình hệ thống**| `UpdateSystemSettingAction::execute` | `THAY_DOI_CAU_HINH` | `CAU_HINH_HE_THONG` |

---

## 5. Kết quả Kiểm thử & Xác minh Toàn diện

Bộ test chuyên biệt [`tests/Feature/AdminAuditLogTest.php`](file:///d:/AGU-Academic-Hub/tests/Feature/AdminAuditLogTest.php) bao quát 12 kịch bản trọng yếu:

1. `test_regular_user_and_guest_cannot_access_audit_logs`: Chặn khách (302) và sinh viên/kiểm duyệt viên (403), cấp phép cho quản trị viên (200).
2. `test_audit_logs_are_immutable_and_cannot_be_updated_or_deleted`: Kiểm tra ném ngoại lệ khi cố ý sửa bản ghi nhật ký.
3. `test_audit_log_deletion_throws_exception`: Kiểm tra ném ngoại lệ khi cố ý xóa bản ghi nhật ký.
4. `test_admin_login_and_logout_are_logged`: Xác minh lưu vết sự kiện đăng nhập và đăng xuất của tài khoản quản trị.
5. `test_lock_and_unlock_account_are_logged`: Xác minh lưu vết sự kiện khóa và mở khóa người dùng.
6. `test_update_user_permissions_is_logged`: Xác minh lưu vết thay đổi vai trò phân quyền.
7. `test_approve_and_reject_kyc_are_logged`: Xác minh lưu vết phê duyệt và từ chối KYC cựu sinh viên.
8. `test_system_settings_update_is_logged`: Xác minh cập nhật cấu hình hệ thống và ghi nhận kiểm toán.
9. `test_raw_students_crud_operations_are_logged`: Xác minh nạp hàng loạt, cập nhật và xóa danh sách sinh viên thô.
10. `test_academic_catalog_changes_are_logged`: Xác minh thao tác thêm/sửa/đổi trạng thái Khoa, Chuyên ngành và Môn học.
11. `test_violation_report_resolution_and_document_moderation_are_logged`: Xác minh xử lý báo cáo vi phạm và kiểm duyệt tài liệu.
12. `test_audit_log_show_api_returns_detailed_diff_and_actor`: Xác minh API chi tiết trả về cấu trúc diff snapshot trạng thái cũ/mới và dữ liệu người thực hiện.

**Kết quả chạy PHPUnit:**
```text
PHPUnit 11.5.57 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\Users\Admin\AppData\Local\Temp\phpunit_nobootstrap_*.xml

............                                                      12 / 12 (100%)

Time: 00:12.484, Memory: 50.00 MB

OK (12 tests, 50 assertions)
```

**Biên dịch Vite Frontend:**
```text
✓ 2603 modules transformed.
✓ built in 17.46s (Zero errors, chunks: AdminLayout, Index, Settings, RawStudents)
```

