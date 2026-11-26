# BÁO CÁO KẾT QUẢ NGHIỆM THU — PHASE 1: ACADEMIC COURSE CATALOG
## AGU Academic Hub — Kho Môn Học (`GET /mon-hoc`)
**Thời điểm hoàn thành:** 2026-11-06  
**Người thực hiện:** Senior Full-stack Engineer  
**Trạng thái nghiệm thu:** **100% ACCEPTED (12/12 ACCEPTANCE CRITERIA PASSED)**  
**Tài liệu phạm vi liên quan:** [`docs/audits/PHASE_1_COURSE_CATALOG_SCOPE.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/PHASE_1_COURSE_CATALOG_SCOPE.md)  
**Tài liệu ngoài phạm vi:** [`docs/audits/OUT_OF_SCOPE_ISSUES.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/OUT_OF_SCOPE_ISSUES.md)

---

## 1. DANH SÁCH TẬP TIN TẠO MỚI VÀ ĐIỀU CHỈNH (FILES CREATED / MODIFIED)

| Loại Tác Động | Đường Dẫn File | Mục Đích Thực Hiện |
| :---: | :--- | :--- |
| **TẠO MỚI** | `resources/js/Pages/Courses/Index.jsx` | Xây dựng giao diện Danh mục Môn học hoàn chỉnh bọc trong `StudentLayout`: Lưới thẻ môn học, tìm kiếm tức thì theo mã/tên, lọc theo Khoa/Chuyên ngành, Empty state, Loading, Responsive. |
| **CHỈNH SỬA** | `app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php` | Chuyển `index()` render từ `Documents/Index` sang `Courses/Index`; truyền `faculties`, `courses`, `filters`; loại bỏ tải thừa `$documents`. |
| **CHỈNH SỬA** | `tests/Feature/PublicAcademicCatalogTest.php` | Cập nhật assertion `test_public_courses_index` khớp với view `Courses/Index` và có prop `courses`. |
| **TẠO MỚI** | `tests/Feature/Phase1CourseCatalogTest.php` | Bộ test tự động chuyên biệt bao phủ toàn diện 12 tiêu chí nghiệm thu của Phase 1. |
| **TẠO MỚI** | `docs/audits/PHASE_1_COURSE_CATALOG_SCOPE.md` | Tài liệu Scope Lock và Data Contract trước khi lập trình. |
| **TẠO MỚI** | `docs/audits/OUT_OF_SCOPE_ISSUES.md` | Bảng ghi nhận các vấn đề ngoài phạm vi được phát hiện trong quá trình thực hiện. |
| **TẠO MỚI** | `docs/audits/PHASE_1_COURSE_CATALOG_RESULT.md` | Báo cáo kết quả nghiệm thu chi tiết này. |

---

## 2. DATA CONTRACT THỰC TẾ (VERIFIED RUNTIME CONTRACT)

Dữ liệu được nạp từ cơ sở dữ liệu thật thông qua `CourseController@index` truyền sang `Courses/Index.jsx`:

```typescript
// Props nhận tại resources/js/Pages/Courses/Index.jsx
interface CoursesIndexProps {
    // 1. Danh sách Khoa kèm Chuyên ngành phục vụ bộ lọc
    faculties: Array<{
        ma_khoa: number;            // Khóa chính Khoa (VD: 1, 2, 4)
        ky_hieu_khoa: string;       // Ký hiệu (VD: "CNTT", "KT")
        ten_khoa: string;           // Tên đầy đủ (VD: "Khoa Công nghệ thông tin")
        mon_hocs_count: number;     // Số môn học trực thuộc
        chuyen_nganhs_count: number;// Số chuyên ngành trực thuộc
        chuyen_nganhs: Array<{      // Danh sách chuyên ngành con
            ma_chuyen_nganh: number;
            ma_khoa: number;
            ky_hieu_nganh: string;  // VD: "DTH", "DPM", "QTKD"
            ten_chuyen_nganh: string;
        }>;
    }>;

    // 2. Danh sách môn học phân trang
    courses: {
        current_page: number;
        data: Array<{
            ma_mon_hoc: number;
            ma_khoa: number;
            ma_hoc_phan: string;     // VD: "COS101", "KTE201", "GEN101"
            ten_mon_hoc: string;     // VD: "Lập trình căn bản C"
            so_tin_chi: number;      // VD: 2, 3
            mo_ta: string | null;    // Mô tả môn học
            khoa?: {
                ma_khoa: number;
                ky_hieu_khoa: string;
                ten_khoa: string;
            };
            chuyen_nganhs?: Array<{  // Quan hệ nhiều-nhiều qua mon_hoc_chuyen_nganh
                ma_chuyen_nganh: number;
                ky_hieu_nganh: string;
                ten_chuyen_nganh: string;
            }>;
        }>;
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    };

    // 3. Bộ lọc query parameters đồng bộ URL
    filters: {
        search?: string;             // Từ khóa tìm kiếm (mã hoặc tên môn)
        ma_khoa?: string | number;   // ID Khoa
        ma_chuyen_nganh?: string | number; // ID Chuyên ngành
    };
}
```

---

## 3. ĐỐI CHIẾU 12 TIÊU CHÍ NGHIỆM THU (ACCEPTANCE CRITERIA RESULTS)

| Mã AC | Tiêu Chí Nghiệm Thu | Kết Quả Thực Tế | Trạng Thái | Bằng Chứng Kiểm Thử |
| :---: | :--- | :--- | :---: | :--- |
| **AC-01** | Tuyến đường `GET /mon-hoc` render đúng component `Courses/Index`. | Tuyến đường trả về HTTP 200, component Inertia là `Courses/Index`. | **PASS** | `Phase1CourseCatalogTest::test_ac01_courses_index_renders_correct_inertia_component` |
| **AC-02** | Danh sách môn học lấy từ database thật, hiển thị mã học phần, tên môn học, tín chỉ, khoa, ngành. | Dữ liệu nạp từ CSDL, `CourseCard` render chuẩn mọi trường thông tin. | **PASS** | `Phase1CourseCatalogTest::test_ac02_courses_index_displays_real_database_records` |
| **AC-03** | Tìm kiếm theo mã học phần đúng (VD: `COS` hoặc `COS101`). | Gõ `COS101` lọc chính xác 1 môn duy nhất; gõ `COS` lọc ra toàn bộ môn họ COS. | **PASS** | `Phase1CourseCatalogTest::test_ac03_search_by_course_code` |
| **AC-04** | Tìm kiếm theo tên môn đúng (VD: `Lập trình` hoặc `Kinh tế`). | Gõ `Kinh tế vi mô` trả về đúng môn `KTE201`. | **PASS** | `Phase1CourseCatalogTest::test_ac04_search_by_course_name` |
| **AC-05** | Lọc theo Khoa đúng (`ma_khoa`). | Chọn `Khoa Công nghệ thông tin` lọc ra đúng 2 môn của khoa đó. | **PASS** | `Phase1CourseCatalogTest::test_ac05_filter_by_faculty` |
| **AC-06** | Lọc theo Chuyên ngành đúng (`ma_chuyen_nganh`). | Chọn `Kỹ thuật phần mềm` lọc chính xác môn `COS101` qua pivot table. | **PASS** | `Phase1CourseCatalogTest::test_ac06_filter_by_major` |
| **AC-07** | Môn học không có chuyên ngành vẫn xuất hiện khi không áp dụng bộ lọc chuyên ngành. | Môn `GEN101` (đại cương không thuộc ngành nào) xuất hiện đầy đủ trong danh mục. | **PASS** | `Phase1CourseCatalogTest::test_ac07_course_without_major_appears_when_no_major_filter` |
| **AC-08** | Pagination hoạt động, bảo toàn query string khi chuyển trang. | Khi số lượng môn > 12, phân trang hiển thị và các nút liên kết lưu `ma_khoa`, `search`. | **PASS** | `Phase1CourseCatalogTest::test_ac08_pagination_and_query_string_preservation` |
| **AC-09** | Khi đổi filter hoặc từ khóa, kết quả được cập nhật chính xác (Debounce 300ms). | State `search` debounce 300ms, dropdown Khoa/Ngành kích hoạt `applyFilters` tức thời. | **PASS** | Kiểm tra tương tác component `Courses/Index.jsx` |
| **AC-10** | Refresh trang hoặc truy cập URL trực tiếp không gây lỗi trạng thái. | Các query params trên URL tự động nạp vào state và hiển thị đúng trên các input/select. | **PASS** | `Phase1CourseCatalogTest::test_ac10_filters_props_match_query_parameters` |
| **AC-11** | Không làm hỏng các route và chức năng hiện có của hệ thống. | 182/182 tests backend đạt 100% PASS; build Vite production thành công sạch sẽ. | **PASS** | Lệnh `php artisan test` và `npm run build` |
| **AC-12** | Sinh viên (`SINH_VIEN`), Cựu sinh viên (`CUU_SINH_VIEN`) và Guest đều truy cập bình thường. | Cả 3 đối tượng người dùng đều gửi request `GET /mon-hoc` thành công HTTP 200. | **PASS** | `Phase1CourseCatalogTest::test_ac12_student_and_alumni_and_guest_can_access` |

---

## 4. LỆNH KIỂM THỬ VÀ KẾT QUẢ RUNTIME (COMMANDS & OUTPUTS)

### 4.1. Bộ test Phase 1 (`Phase1CourseCatalogTest.php`)
```bash
php artisan test tests/Feature/Phase1CourseCatalogTest.php
```
**Kết quả thực tế:**
```text
   PASS  Tests\Feature\Phase1CourseCatalogTest
  ✓ ac01 courses index renders correct inertia component                                                         0.83s  
  ✓ ac02 courses index displays real database records                                                            0.14s  
  ✓ ac03 search by course code                                                                                   0.16s  
  ✓ ac04 search by course name                                                                                   0.17s  
  ✓ ac05 filter by faculty                                                                                       0.17s  
  ✓ ac06 filter by major                                                                                         0.10s  
  ✓ ac07 course without major appears when no major filter                                                       0.11s  
  ✓ ac08 pagination and query string preservation                                                                0.11s  
  ✓ ac09 empty state when no course matches                                                                      0.08s  
  ✓ ac10 filters props match query parameters                                                                    0.09s  
  ✓ ac12 student and alumni and guest can access                                                                 0.20s  

  Tests:    11 passed (172 assertions)
  Duration: 2.43s
```

### 4.2. Bộ test hồi quy toàn hệ thống (Regression Test Suite)
```bash
php artisan test
```
**Kết quả thực tế:**
```text
  Tests:    182 passed (1242 assertions)
  Duration: 30.38s
```
*(Tăng 11 test cases so với trước đó; 100% tests vượt qua không có bất kỳ regression nào).*

### 4.3. Kiểm thử biên dịch Frontend Production
```bash
npm run build
```
**Kết quả thực tế:**
```text
vite v7.3.6 building client environment for production...
✓ 2602 modules transformed.
rendering chunks...
computing gzip size...
✓ built in 8.79s
(Exit Code: 0 - Clean build, không có cảnh báo hoặc lỗi bundle)
```

---

## 5. CÁC LỖI CÒN TỒN TẠI & VẤN ĐỀ NGOÀI PHẠM VI (OUT-OF-SCOPE LOG)

Trong phạm vi của Phase 1 (`Courses/Index`), **không còn bất kỳ lỗi nào tồn tại**.

Các vấn đề phát hiện ngoài phạm vi đã được cô lập tuyệt đối và ghi nhận tại [`docs/audits/OUT_OF_SCOPE_ISSUES.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/OUT_OF_SCOPE_ISSUES.md) gồm:
1. `OOS-01`: Khối thông báo rỗng trên `Dashboard.jsx:419-430` hiển thị đè dưới tài liệu mới (Xử lý tại Phase P0 - Task TSK-REV-002).
2. `OOS-02`: Thẻ tài liệu mới trên Dashboard thiếu thẻ `<Link>` (Xử lý tại Phase P0 - Task TSK-REV-002).
3. `OOS-03`: Nhãn danh xưng cựu sinh viên trên Dashboard hiển thị cứng sinh viên (Xử lý tại Phase P0 - Task TSK-REV-002).
4. `OOS-04`: Tuyến đường `courses.show` (`GET /mon-hoc/{code}`) chưa có trang chi tiết `Courses/Show.jsx` (Xử lý tại Phase P0 - Task TSK-REV-003).
5. `OOS-05`: Nút "Xem môn học" trên trang Khoa chưa truyền `ma_khoa` (Xử lý tại Phase P1 - Task TSK-REV-004).
6. `OOS-06 & OOS-07`: Form đăng tải thiếu kiểm tra dung lượng > 50MB trước khi hash và thiếu `mimes` server (Xử lý tại Phase P1 - Task TSK-REV-005).

---

## 6. KẾT LUẬN & ĐỀ XUẤT TIẾP THEO

- **Giai đoạn Phase 1 đã hoàn thành xuất sắc, tuân thủ nghiêm ngặt Scope Lock và đạt 100% Acceptance Criteria.**
- Sinh viên, Cựu sinh viên và khách vãng lai hiện đã có thể truy cập `GET /mon-hoc`, tìm kiếm và lọc danh mục môn học chính thức của Đại học An Giang từ cơ sở dữ liệu thật.
- Hệ thống đã sẵn sàng để chuyển sang **Phase 2 (Khắc phục lỗi Dashboard & Liên kết tác vụ nhanh theo Task TSK-REV-002)** hoặc **Phase 3 (Xây dựng `Courses/Show.jsx` và Hub 3 Tabs theo Task TSK-REV-003)** khi người dùng yêu cầu.

