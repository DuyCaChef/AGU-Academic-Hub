# TÀI LIỆU KHÓA PHẠM VI TRIỂN KHAI — PHASE 3A: COURSE DETAIL CORE
## AGU Academic Hub — Chi Tiết Môn Học (`GET /mon-hoc/{course_code}`)
**Thời điểm lập:** 2026-11-06  
**Trạng thái:** SCOPE LOCKED  
**Phạm vi:** Triển khai độc lập Phase 3A (Phần lõi Chi tiết Môn học). Tuyệt đối không can thiệp vào các Phase hoặc Tabs ngoài phạm vi.

---

## 1. MỤC TIÊU & PHẠM VI TRIỂN KHAI

### 1.1. Mục tiêu duy nhất
Xây dựng trang chi tiết môn học chuyên biệt tại `resources/js/Pages/Courses/Show.jsx` cho tuyến đường `GET /mon-hoc/{course_code}` (`courses.show`), hiển thị đầy đủ thông tin học thuật cốt lõi của một học phần từ cơ sở dữ liệu thật khi người dùng bấm vào từ `Courses/Index`.

### 1.2. Thành phần Trong Phạm Vi (In-Scope)
- **Frontend (`resources/js/Pages/Courses/Show.jsx`):**
  - Bọc trong `StudentLayout title={course.ten_mon_hoc}`.
  - Header học thuật:
    - Breadcrumb điều hướng: Trang chủ $\to$ Kho môn học $\to$ Mã học phần.
    - Huy hiệu Mã học phần (`ma_hoc_phan`, in hoa, phông mono, badge xanh AGU).
    - Tên đầy đủ của môn học (`ten_mon_hoc`).
    - Huy hiệu Số tín chỉ (`so_tin_chi`).
    - Khoa phụ trách (`khoa.ten_khoa`).
    - Danh sách Chuyên ngành liên quan (`chuyen_nganhs` dạng pill tags; hiển thị thông báo trang trọng nếu là môn đại cương không thuộc ngành nào).
    - Khối Mô tả học phần (`mo_ta`, có xử lý fallback khi môn học chưa cập nhật mô tả).
    - Nút điều hướng quay lại kho môn học ("Quay lại Kho môn học" dẫn về `route('courses.index')`).
  - Trạng thái giao diện:
    - Loading State.
    - Error / Not Found State (Môn học không tồn tại).
    - Responsive cơ bản trên Mobile, Tablet và Desktop.
- **Backend:**
  - `CourseController@show`: Cập nhật render sang view `Courses/Show` thay vì `Documents/Index`.
  - Tái sử dụng `GetPublicCoursesQuery::findActiveCourseByCode($course_code)` và `AcademicCatalogRepository::findActiveCourseByCode($course_code)`.
  - Xử lý 404 Not Found tự động thông qua `firstOrFail()`.
- **Tương tác từ `Courses/Index.jsx`:**
  - Đảm bảo thẻ môn học và tiêu đề trên `Courses/Index.jsx` liên kết chính xác sang `route('courses.show', course.ma_hoc_phan)`.
- **Testing:**
  - Xây dựng bộ test `tests/Feature/Phase3aCourseDetailTest.php` kiểm chứng toàn bộ 12 Acceptance Criteria.
  - Cập nhật các test case cũ từng assert view `Documents/Index` cho `courses.show`.

### 1.3. Thành phần Tuyệt Đối Ngoài Phạm Vi (Out-of-Scope)
- Tuyệt đối KHÔNG triển khai:
  - Tab Kho tài liệu (Tab 1).
  - Tab Đánh giá môn học (Tab 2).
  - Tab Săn thưởng Bounty (Tab 3).
  - Document Search.
  - Upload Pipeline.
  - Trang danh mục Khoa.
  - Dashboard.
  - Migration hoặc sửa đổi database schema.
  - Refactor Layout.
- Mọi vấn đề phát hiện ngoài phạm vi sẽ được ghi vào [`docs/audits/OUT_OF_SCOPE_ISSUES.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/OUT_OF_SCOPE_ISSUES.md).

---

## 2. HIỆN TRẠNG KỸ THUẬT & KIỂM CHỨNG TRƯỚC TRIỂN KHAI

1. **Tuyến đường (Route):**
   - Đã tồn tại tại `app/Modules/AcademicCatalog/routes.php:22`:
     `Route::get('/{course_code}', [CourseController::class, 'show'])->name('show');` (Prefix: `mon-hoc`).
   - Tham số `{course_code}`: Có thể nhận Mã học phần chuẩn (VD: `COS101`, `KTE201`, `GEN101`) hoặc ID số nguyên (`ma_mon_hoc`).
2. **Khóa định danh & Query:**
   - Đã xác minh tại `AcademicCatalogRepository::findActiveCourseByCode`:
     ```php
     MonHoc::query()
         ->where(function (Builder $query) use ($courseCode): void {
             $query->where('ma_hoc_phan', $courseCode);
             if (is_numeric($courseCode)) {
                 $query->orWhere('ma_mon_hoc', (int) $courseCode);
             }
         })
         ->whereHas('khoa')
         ->with([
             'khoa:ma_khoa,ky_hieu_khoa,ten_khoa',
             'chuyenNganhs:ma_chuyen_nganh,ky_hieu_nganh,ten_chuyen_nganh',
         ])
         ->firstOrFail();
     ```
   - Eager load sẵn sàng: `khoa` và `chuyenNganhs`. Nếu mã không tồn tại, tự động ném ra `ModelNotFoundException` trả về HTTP 404.
3. **Controller:**
   - `CourseController@show` trước đây render nhầm `Documents/Index`. Cần sửa để render `Courses/Show`.

---

## 3. DATA CONTRACT XÁC MINH TỪ CƠ SỞ DỮ LIỆU

```typescript
// Props nhận tại resources/js/Pages/Courses/Show.jsx
interface CourseShowPageProps {
    course: {
        ma_mon_hoc: number;           // Khóa chính số nguyên (VD: 1, 2, 7)
        ma_khoa: number;              // Khóa ngoại liên kết bảng khoa
        ma_hoc_phan: string;          // Mã học phần viết hoa (VD: "COS101")
        ten_mon_hoc: string;          // Tên đầy đủ (VD: "Lập trình căn bản")
        so_tin_chi: number;           // Số tín chỉ (VD: 2, 3)
        mo_ta: string | null;         // Mô tả tóm tắt học phần (có thể null)
        khoa: {                       // Khoa chủ quản (bắt buộc)
            ma_khoa: number;
            ky_hieu_khoa: string;     // VD: "CNTT"
            ten_khoa: string;         // VD: "Khoa Công nghệ thông tin"
        };
        chuyen_nganhs: Array<{        // Danh sách chuyên ngành (có thể rỗng)
            ma_chuyen_nganh: number;
            ky_hieu_nganh: string;    // VD: "DTH", "DPM"
            ten_chuyen_nganh: string; // VD: "Công nghệ thông tin"
        }>;
    };
    faculties: Array<any>;            // Danh sách Khoa cho StudentSidebar
}
```

*Xử lý trường hợp đặc biệt:*
- Nếu `course.chuyen_nganhs` là mảng rỗng `[]`: Hiển thị thông điệp học thuật *"Học phần đại cương / Cơ bản (Áp dụng chung toàn trường, không phân biệt chuyên ngành)"*.
- Nếu `course.mo_ta` là `null`: Hiển thị thông điệp *"Học phần chưa cập nhật mô tả chi tiết từ đề cương đào tạo"*.

---

## 4. MA TRẬN TIÊU CHÍ NGHIỆM THU (AC-01 ĐẾN AC-12)

| Mã AC | Tiêu Chí Nghiệm Thu | Phương Pháp Kiểm Chứng | Trạng Thái Dự Kiến |
| :---: | :--- | :--- | :---: |
| **AC-01** | Click Course Card trên `Courses/Index` mở đúng trang chi tiết `Courses/Show`. | Click thẻ môn học trên giao diện hoặc gọi route `courses.show`. | CHƯA KIỂM CHỨNG |
| **AC-02** | Hiển thị đúng mã học phần và tên môn học từ CSDL. | Kiểm tra DOM hiển thị `course.ma_hoc_phan` và `course.ten_mon_hoc`. | CHƯA KIỂM CHỨNG |
| **AC-03** | Hiển thị đúng số tín chỉ và mô tả môn học. | Kiểm tra hiển thị `so_tin_chi` và đoạn văn bản `mo_ta`. | CHƯA KIỂM CHỨNG |
| **AC-04** | Hiển thị đúng khoa phụ trách môn học. | Kiểm tra hiển thị `khoa.ten_khoa` và ký hiệu khoa. | CHƯA KIỂM CHỨNG |
| **AC-05** | Hiển thị đúng danh sách chuyên ngành liên quan qua quan hệ nhiều-nhiều. | Duyệt danh sách thẻ tags `chuyen_nganhs` trên DOM. | CHƯA KIỂM CHỨNG |
| **AC-06** | Môn học không có chuyên ngành vẫn hiển thị bình thường, không vỡ layout. | Mở môn đại cương `GEN101` (không có chuyên ngành) assert hiển thị fallback. | CHƯA KIỂM CHỨNG |
| **AC-07** | Môn học không tồn tại trả về mã lỗi HTTP 404 phù hợp. | Truy cập `/mon-hoc/KHONG_TON_TAI_999` assert trả về 404 Not Found. | CHƯA KIỂM CHỨNG |
| **AC-08** | Có nút/liên kết điều hướng quay lại kho môn học. | Kiểm tra nút "Quay lại Kho môn học" trỏ về `route('courses.index')`. | CHƯA KIỂM CHỨNG |
| **AC-09** | Refresh trang (F5) không gây lỗi trạng thái hoặc trắng trang. | F5 trực tiếp trên URL `/mon-hoc/{code}` assert nạp đủ dữ liệu. | CHƯA KIỂM CHỨNG |
| **AC-10** | Sinh viên (`SINH_VIEN`), Cựu sinh viên (`CUU_SINH_VIEN`) và Guest truy cập bình thường. | Test phân quyền với cả 3 vai trò người dùng. | CHƯA KIỂM CHỨNG |
| **AC-11** | Giao diện responsive trên Mobile, Tablet và Desktop. | Kiểm tra các breakpoint hiển thị thông tin học thuật. | CHƯA KIỂM CHỨNG |
| **AC-12** | Không làm hỏng các chức năng hiện có (188+ tests pass, Vite build sạch). | Chạy full test suite và build bundle Vite. | CHƯA KIỂM CHỨNG |

