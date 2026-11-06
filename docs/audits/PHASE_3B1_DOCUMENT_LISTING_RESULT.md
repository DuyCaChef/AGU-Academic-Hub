# BÁO CÁO NGHIỆM THU – PHASE 3B1: COURSE DETAIL - DOCUMENT LISTING INTEGRATION
**Dự án**: AGU Academic Hub  
**Phân hệ**: Sinh viên (`SINH_VIEN`) & Cựu sinh viên (`CUU_SINH_VIEN`)  
**Ngày nghiệm thu**: 2026-11-06  
**Trạng thái**: **HOÀN THÀNH TOÀN DIỆN (ALL ACCEPTANCE CRITERIA PASSED)**

---

## 1. CÁC FILE TẠO MỚI & CHỈNH SỬA

### 1.1. Các file tạo mới:
- [`docs/audits/PHASE_3B1_DOCUMENT_LISTING_SCOPE.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/PHASE_3B1_DOCUMENT_LISTING_SCOPE.md): Tài liệu phạm vi khóa và khảo sát kỹ thuật Phase 3B1.
- [`tests/Feature/Phase3b1DocumentListingTest.php`](file:///d:/CNWEB/AGU-Academic-Hub/tests/Feature/Phase3b1DocumentListingTest.php): Bộ kiểm thử tự động toàn diện bao quát 15 tiêu chí nghiệm thu AC-01 đến AC-15.
- [`docs/audits/PHASE_3B1_DOCUMENT_LISTING_RESULT.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/PHASE_3B1_DOCUMENT_LISTING_RESULT.md): Báo cáo nghiệm thu kỹ thuật và đối soát kiểm thử.

### 1.2. Các file chỉnh sửa:
- [`app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php): Bổ sung truy vấn tài liệu theo môn học (`course_id => $course->ma_mon_hoc`), hỗ trợ tìm kiếm (`search`), phân loại (`category`), sắp xếp (`sort`) và phân trang (`page`) trong phương thức `show`.
- [`resources/js/Components/domain/DocumentCard.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Components/domain/DocumentCard.jsx): Nâng cấp hiển thị người đăng, ngày đăng, dung lượng, định dạng, nút tải về trực tiếp, nút xem trước PDF trực tuyến và AJAX upvote.
- [`resources/js/Pages/Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx): Tích hợp hoàn chỉnh khu vực Kho tài liệu học tập của học phần với toolbar tìm kiếm/lọc, đếm tài liệu, grid danh sách, empty state, loading indicator và pagination.

---

## 2. API & ROUTE ĐƯỢC TÁI SỬ DỤNG / BỔ SUNG

| Tên Route | Phương thức & URI | Mục đích sử dụng |
| :--- | :--- | :--- |
| `courses.show` | `GET /mon-hoc/{course_code}` | Route trang chi tiết môn học kèm tham số lọc `search`, `category`, `sort`, `page` |
| `documents.show` | `GET /tai-lieu/{id}` | Xem chi tiết tài liệu học tập |
| `documents.preview` | `GET /tai-lieu/{id}/xem-truoc` | Xem trước PDF trực tuyến trong tab mới (`PreviewDocumentAction`) |
| `documents.download` | `GET /tai-lieu/{id}/tai-ve` | Tải file về máy và tự động tăng số lượt tải (`DownloadDocumentAction`) |
| `documents.upvote` | `POST /tai-lieu/{id}/binh-chon` | Bình chọn tài liệu hữu ích qua AJAX (`UpvoteDocumentAction`) |
| `documents.create` | `GET /tai-lieu/dang-tai` | CTA đăng tải tài liệu mới cho môn học |
| `courses.index` | `GET /mon-hoc` | Điều hướng quay lại kho danh mục môn học |

---

## 3. DATA CONTRACT THỰC TẾ

Props được truyền sang `Courses/Show.jsx` qua Inertia Response:

```typescript
interface CourseShowPageProps {
  course: {
    ma_mon_hoc: number;
    ma_khoa: number;
    ma_hoc_phan: string;
    ten_mon_hoc: string;
    so_tin_chi: number;
    mo_ta: string | null;
    trang_thai: string;
    khoa: {
      ma_khoa: number;
      ky_hieu_khoa: string;
      ten_khoa: string;
    };
    chuyen_nganhs: Array<{
      ma_chuyen_nganh: number;
      ky_hieu_nganh: string;
      ten_chuyen_nganh: string;
    }>;
  };
  faculties: Array<{
    ma_khoa: number;
    ky_hieu_khoa: string;
    ten_khoa: string;
  }>;
  documents: {
    data: Array<{
      ma_tai_lieu: number;
      ma_mon_hoc: number;
      ma_nguoi_dang: number;
      tieu_de: string;
      phan_loai: 'DE_THI' | 'BAI_GIANG' | 'DO_AN' | 'BAI_TAP' | string;
      phien_ban: string;
      dinh_dang_tep: string;
      dung_luong_kb: number;
      luot_tai: number;
      luot_thich: number;
      trang_thai: 'DA_DUYET';
      created_at: string;
      nguoi_dang: {
        ma_nguoi_dung: number;
        ho_ten: string;
      };
      mon_hoc: {
        ma_mon_hoc: number;
        ten_mon_hoc: string;
        ma_hoc_phan: string;
      };
    }>;
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
    links: Array<{
      url: string | null;
      label: string;
      active: boolean;
    }>;
  };
  filters: {
    search?: string;
    category?: string;
    sort?: 'latest' | 'downloads' | 'upvotes';
  };
}
```

---

## 4. KẾT QUẢ TỪNG TIÊU CHÍ NGHIỆM THU (ACCEPTANCE CRITERIA)

| Mã AC | Tiêu chí nghiệm thu | Trạng thái | Bằng chứng kiểm thử / Mã nguồn |
| :--- | :--- | :---: | :--- |
| **AC-01** | Trang chi tiết môn học vẫn hoạt động sau khi bổ sung Kho tài liệu | **PASS** | `test_course_detail_shows_approved_documents_with_full_metadata` |
| **AC-02** | Chỉ hiển thị tài liệu thuộc đúng môn học đang truy cập | **PASS** | `test_documents_from_different_courses_are_strictly_isolated` |
| **AC-03** | Không hiển thị tài liệu thuộc môn học khác | **PASS** | `test_documents_from_different_courses_are_strictly_isolated` |
| **AC-04** | Hiển thị đúng metadata: Tiêu đề, phân loại, người đăng, ngày đăng, lượt tải | **PASS** | `DocumentCard.jsx`, `test_course_detail_shows_approved_documents_with_full_metadata` |
| **AC-05** | Môn học không có tài liệu hiển thị Empty State | **PASS** | `test_course_without_documents_returns_empty_collection`, UI empty banner |
| **AC-06** | Có Loading và Error State phù hợp | **PASS** | `Courses/Show.jsx` lines 369-378 (Overlay loading animation) |
| **AC-07** | Phân trang (Pagination) hoạt động chính xác theo route hiện tại | **PASS** | `test_document_filtering_and_pagination_on_course_detail`, `Pagination.jsx` |
| **AC-08** | Mở đúng trang chi tiết tài liệu (`documents.show`) | **PASS** | `test_document_detail_preview_download_and_upvote_workflows` |
| **AC-09** | Preview PDF hoạt động theo quyền hiện tại (`documents.preview`) | **PASS** | `test_document_detail_preview_download_and_upvote_workflows` |
| **AC-10** | Download hoạt động theo quyền hiện tại (`documents.download`) | **PASS** | `test_document_detail_preview_download_and_upvote_workflows` (lượt tải tự tăng) |
| **AC-11** | Upvote hoạt động qua AJAX | **PASS** | `test_document_detail_preview_download_and_upvote_workflows` (lượt thích tăng) |
| **AC-12** | Student và Alumni đều truy cập đúng chính sách | **PASS** | `test_student_and_alumni_can_view_course_documents` |
| **AC-13** | Không làm lộ tài liệu chưa được phép công khai (`CHO_DUYET`, `TU_CHOI`, soft-deleted) | **PASS** | `test_unapproved_or_soft_deleted_documents_are_never_leaked` |
| **AC-14** | Không ảnh hưởng Dashboard, Document Index, Document Show và Upload | **PASS** | Regression tests suite 202/202 tests PASS |
| **AC-15** | Giao diện hiển thị chuẩn xác, responsive trên desktop và mobile | **PASS** | Grid Tailwind `grid-cols-1 md:grid-cols-2`, responsive flex bars |

---

## 5. BẰNG CHỨNG KIỂM THỬ THỰC TẾ

### 5.1. Feature Test Phase 3B1: `tests/Feature/Phase3b1DocumentListingTest.php`
```
   PASS  Tests\Feature\Phase3b1DocumentListingTest
  ✓ course detail shows approved documents with full metadata                                                    0.95s  
  ✓ documents from different courses are strictly isolated                                                       0.19s  
  ✓ course without documents returns empty collection                                                            0.09s  
  ✓ unapproved or soft deleted documents are never leaked                                                        0.10s  
  ✓ document filtering and pagination on course detail                                                           0.21s  
  ✓ student and alumni can view course documents                                                                 0.16s  
  ✓ document detail preview download and upvote workflows                                                        0.27s  

  Tests:    7 passed (189 assertions)
  Duration: 2.23s
```

### 5.2. Toàn bộ Test Suite hệ thống (Regression Check)
```
  Tests:    202 passed (1633 assertions)
  Duration: 35.07s
```
*Kết quả: Toàn bộ 202 bài test đỗ tuyệt đối, 0 failures, 0 errors.*

### 5.3. Frontend Vite Production Build
```
vite v7.3.6 building client environment for production...
✓ 2603 modules transformed.
public/build/assets/DocumentCard-DheBSmh9.js    5.65 kB │ gzip: 2.09 kB
public/build/assets/Show-BgDU7WeD.js          15.93 kB │ gzip: 4.52 kB
✓ built in 10.70s
```
*Kết quả: Build thành công trong 10.70s, 0 lỗi cú pháp hoặc cảnh báo import.*

---

## 6. CÁC VẤN ĐỀ ĐÃ XỬ LÝ & BẢO VỆ RUNTIME

### 6.1. Khắc phục lỗi `TypeError: Cannot convert undefined or null to object at sort`
- **Nguyên nhân**: Khi request không có query params, backend PHP có thể serialize mảng `$filters` rỗng thành `[]` (Array) trong JSON thay vì Object `{}`. Trong JavaScript, `[].sort` là phương thức có sẵn `Array.prototype.sort`. Khi component truyền `safeFilters.sort` vào `useState(safeFilters.sort || "latest")`, React phát hiện giá trị khởi tạo là một `function` nên đã thực thi `sort()` ngầm (lazy state initialization) với context `this = undefined`, dẫn đến ngoại lệ `TypeError: Cannot convert undefined or null to object`.
- **Giải pháp xử lý**:
  - Tại [`resources/js/Pages/Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx): Chuẩn hóa `safeFilters` không nhận kiểu `Array`, đồng thời kiểm tra tường minh `typeof safeFilters.sort === "string"` trước khi gán vào `useState`.
  - Tại [`app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php): Đảm bảo mảng `filters` luôn được trả về dưới dạng associative array gồm 3 keys `search`, `category`, `sort`.
  - Áp dụng giải pháp tương tự cho [`resources/js/Pages/Documents/Index.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx).
  - Tích hợp `CourseDetailErrorBoundary` để bảo vệ component tree trước mọi lỗi bất ngờ.

### 6.2. Ranh giới Scope Lock
- **Ranh giới Scope Lock được bảo lưu 100%**: Tab Course Reviews, Tab Wishlist/Bounty và quy trình upload không bị can thiệp.
- **Không có vấn đề phát sinh ngoài phạm vi (0 issues)**: Hệ thống tái sử dụng triệt để kiến trúc Query/Repository sẵn có của Modular Monolith.

