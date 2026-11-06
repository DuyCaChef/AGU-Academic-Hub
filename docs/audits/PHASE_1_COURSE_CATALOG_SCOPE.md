# TÀI LIỆU KHÓA PHẠM VI TRIỂN KHAI — PHASE 1: ACADEMIC COURSE CATALOG
## AGU Academic Hub — Kho Môn Học (`GET /mon-hoc`)
**Thời điểm lập:** 2026-11-06  
**Trạng thái:** SCOPE LOCKED  
**Phạm vi:** Triển khai độc lập Phase 1 (Kho Môn học). Tuyệt đối không triển khai hoặc sửa đổi các module ngoài phạm vi.

---

## 1. MỤC TIÊU & PHẠM VI TRIỂN KHAI

### 1.1. Mục tiêu duy nhất
Xây dựng hoàn chỉnh trang danh sách và tra cứu môn học tại tuyến đường `GET /mon-hoc` (`courses.index`), kết nối trực tiếp với cơ sở dữ liệu thật của AGU Academic Hub, giúp Sinh viên (`SINH_VIEN`), Cựu sinh viên (`CUU_SINH_VIEN`) và khách vãng lai tìm kiếm, lọc và xem thông tin các học phần đào tạo.

### 1.2. Thành phần Trong Phạm Vi (In-Scope)
- **Frontend:**
  - Tạo mới component `resources/js/Pages/Courses/Index.jsx` bọc trong `StudentLayout`.
  - Hiển thị danh sách môn học dưới dạng lưới thẻ môn học (`CourseCard`).
  - Hiển thị mã học phần (`ma_hoc_phan`, in hoa), tên môn học (`ten_mon_hoc`), số tín chỉ (`so_tin_chi`), Khoa trực thuộc (`khoa.ten_khoa`), và danh sách Chuyên ngành áp dụng (`chuyen_nganhs`).
  - Ô tìm kiếm môn học tức thời (hỗ trợ tìm theo Mã học phần và Tên môn học).
  - Bộ lọc chọn Khoa đào tạo (`ma_khoa`).
  - Bộ lọc chọn Chuyên ngành đào tạo (`ma_chuyen_nganh`), tự động đồng bộ theo Khoa đã chọn.
  - Phân trang chuẩn bằng `resources/js/Components/Common/Pagination.jsx`.
  - Trạng thái tải dữ liệu (Loading State), Trạng thái rỗng không có kết quả (Empty State), Trạng thái lỗi (Error State).
  - Giao diện Responsive cơ bản (Mobile 1 cột, Tablet 2 cột, Desktop 3 cột).
- **Backend:**
  - `app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php`: Chuyển view render từ `Documents/Index` sang `Courses/Index`, truyền đầy đủ `faculties`, `courses`, `filters`.
  - Tái sử dụng nguyên vẹn `GetPublicCoursesQuery` và `AcademicCatalogRepository`.
- **Testing:**
  - Cập nhật test case kiểm tra `courses.index` trong `tests/Feature/PublicAcademicCatalogTest.php`.
  - Viết test case bao phủ toàn bộ 12 Acceptance Criteria của Phase 1.

### 1.3. Thành phần Tuyệt Đối Ngoài Phạm Vi (Out-of-Scope)
- Tuyệt đối KHÔNG triển khai:
  - `Courses/Show.jsx` (Chi tiết môn học - Phase 2).
  - Tabs môn học (Kho tài liệu, Đánh giá, Bounty trong trang chi tiết).
  - Module Đánh giá môn học (Reviews).
  - Module Săn thưởng / Yêu cầu tài liệu (Wishlist/Bounty).
  - Sửa lỗi Dashboard (`Dashboard.jsx`).
  - Trang danh sách Khoa (`Faculties/Index.jsx`).
  - Form đăng tải tài liệu (`Documents/Create.jsx`).
  - Tìm kiếm tài liệu (`DocumentRepository`).
  - Refactor các module khác.
  - Migration hoặc thay đổi cấu trúc bảng CSDL.
- Bất kỳ lỗi phát hiện ngoài phạm vi sẽ được ghi nhận vào `docs/audits/OUT_OF_SCOPE_ISSUES.md`.

---

## 2. HIỆN TRẠNG KỸ THUẬT & KIỂM CHỨNG TRƯỚC TRIỂN KHAI

1. **Tuyến đường (Route):**
   - Định nghĩa tại `app/Modules/AcademicCatalog/routes.php:21`:
     `Route::get('/', [CourseController::class, 'index'])->name('index');`
   - Thuộc nhóm prefix `mon-hoc`, tên định danh `courses.index`.
   - Áp dụng middleware `web`, hỗ trợ đầy đủ phiên làm việc, CSRF và nhận diện danh tính `auth.user`.
2. **Controller:**
   - `CourseController@index` hiện đang render nhầm `Documents/Index` và tải thừa `$documents`. Cần sửa để render `Courses/Index` và truyền đúng `faculties`, `courses`, `filters`.
3. **Query & Repository:**
   - `GetPublicCoursesQuery@getPaginatedCourses` và `AcademicCatalogRepository@getPublicPaginatedCourses` đã sẵn sàng:
     - Đã hỗ trợ tìm kiếm theo `ma_hoc_phan` và `ten_mon_hoc`.
     - Đã hỗ trợ lọc theo `ma_khoa`.
     - Đã hỗ trợ lọc theo `ma_chuyen_nganh` qua quan hệ nhiều-nhiều `mon_hoc_chuyen_nganh`.
     - Đã eager load `khoa` và `chuyenNganhs`.
     - Đã phân trang và giữ query string bằng `withQueryString()`.

---

## 3. DATA CONTRACT XÁC MINH TỪ CƠ SỞ DỮ LIỆU THẬT

Dữ liệu thực tế tại CSDL (`sqlite` / `mysql`) đã được kiểm chứng qua runtime script:

```typescript
// 1. Cấu trúc Props truyền từ CourseController sang Courses/Index.jsx
interface CoursesIndexPageProps {
    // Danh sách Khoa kèm Chuyên ngành phục vụ bộ lọc
    faculties: Array<{
        ma_khoa: number;
        ky_hieu_khoa: string;
        ten_khoa: string;
        mon_hocs_count: number;
        chuyen_nganhs_count: number;
        chuyen_nganhs: Array<{
            ma_chuyen_nganh: number;
            ma_khoa: number;
            ky_hieu_nganh: string;
            ten_chuyen_nganh: string;
        }>;
    }>;

    // Danh sách Môn học phân trang
    courses: {
        current_page: number;
        data: Array<{
            ma_mon_hoc: number;
            ma_khoa: number;
            ma_hoc_phan: string; // Tự động uppercase (VD: "COS101")
            ten_mon_hoc: string;
            so_tin_chi: number;
            mo_ta: string | null;
            khoa?: {
                ma_khoa: number;
                ky_hieu_khoa: string;
                ten_khoa: string;
            };
            chuyen_nganhs?: Array<{
                ma_chuyen_nganh: number;
                ky_hieu_nganh: string;
                ten_chuyen_nganh: string;
            }>;
        }>;
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    };

    // Bộ lọc đang áp dụng từ URL
    filters: {
        search?: string;
        ma_khoa?: string | number;
        ma_chuyen_nganh?: string | number;
    };
}
```

---

## 4. MA TRẬN TIÊU CHÍ NGHIỆM THU (ACCEPTANCE CRITERIA)

| STT | Tiêu Chí Nghiệm Thu (Acceptance Criteria) | Phương Pháp Kiểm Chứng | Trạng Thái Dự Kiến |
| :---: | :--- | :--- | :---: |
| **AC-01** | Tuyến đường `GET /mon-hoc` render đúng component `Courses/Index`. | Assert Inertia component trong Feature Test | CHƯA KIỂM CHỨNG |
| **AC-02** | Danh sách môn học hiển thị dữ liệu thật từ database (mã HP, tên môn, tín chỉ, khoa). | Kiểm tra dữ liệu trên DOM trùng khớp DB | CHƯA KIỂM CHỨNG |
| **AC-03** | Tìm kiếm theo mã học phần chính xác (VD: gõ `COS` trả về đúng 4 môn COS). | Gửi request `?search=COS`, đối soát kết quả | CHƯA KIỂM CHỨNG |
| **AC-04** | Tìm kiếm theo tên môn học chính xác (VD: gõ `Lập trình` ra `COS101`). | Gửi request `?search=Lập trình`, đối soát kết quả | CHƯA KIỂM CHỨNG |
| **AC-05** | Lọc theo Khoa chính xác (VD: chọn `Khoa Nông nghiệp` ra `NCM101`). | Gửi request `?ma_khoa=4`, đối soát kết quả | CHƯA KIỂM CHỨNG |
| **AC-06** | Lọc theo Chuyên ngành chính xác (VD: chọn `Quản trị kinh doanh` ra `KTC101`, `MLN101`). | Gửi request `?ma_chuyen_nganh=3`, đối soát kết quả | CHƯA KIỂM CHỨNG |
| **AC-07** | Môn học không có chuyên ngành vẫn xuất hiện khi không áp dụng bộ lọc chuyên ngành. | Kiểm tra các môn đại cương khi filter chuyên ngành = `all` | CHƯA KIỂM CHỨNG |
| **AC-08** | Pagination hoạt động, bảo toàn các tham số tìm kiếm/lọc khi chuyển trang. | Kiểm tra `links` và click chuyển trang | CHƯA KIỂM CHỨNG |
| **AC-09** | Thay đổi filter hoặc nhập từ khóa cập nhật kết quả chính xác, có debounce tìm kiếm. | Thử nghiệm tương tác và `router.get` | CHƯA KIỂM CHỨNG |
| **AC-10** | Refresh lại trình duyệt giữ nguyên trạng thái bộ lọc (URL Query Strings). | F5 trình duyệt trên URL có query params | CHƯA KIỂM CHỨNG |
| **AC-11** | Không làm hỏng các route và chức năng hiện có của hệ thống (171+ tests pass). | Chạy toàn bộ test suite `php artisan test` | CHƯA KIỂM CHỨNG |
| **AC-12** | Cả `SINH_VIEN`, `CUU_SINH_VIEN` và Guest đều truy cập tra cứu bình thường. | Test phân quyền với các role người dùng | CHƯA KIỂM CHỨNG |

