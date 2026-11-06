# KẾ HOẠCH TRIỂN KHAI ĐIỀU CHỈNH (REVISED IMPLEMENTATION PLAN)
## AGU Academic Hub — Phân Hệ Sinh Viên (`SINH_VIEN`) & Cựu Sinh Viên (`CUU_SINH_VIEN`)
**Thời điểm lập:** 2026-11-06  
**Phiên bản:** 2.0 (Chuẩn hóa toàn diện sau phản biện kỹ thuật, tách biệt rõ ràng Course/Document Search và xác minh Database Schema)  
**Căn cứ lập:** 
- Báo cáo kiểm toán thực tế: [`docs/audits/REALITY_AUDIT_REPORT.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/REALITY_AUDIT_REPORT.md)
- Ma trận khoảng trống chuẩn hóa: [`docs/audits/FEATURE_GAP_MATRIX.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/FEATURE_GAP_MATRIX.md)
- Sơ đồ phụ thuộc triển khai: [`docs/audits/IMPLEMENTATION_DEPENDENCY_GRAPH.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/IMPLEMENTATION_DEPENDENCY_GRAPH.md)

---

## 1. NGUYÊN TẮC KIẾN TRÚC & XÁC MINH CƠ SỞ DỮ LIỆU

### A. Phân định ranh giới trách nhiệm tìm kiếm (Separation of Search Responsibilities)
- `[Đã xác minh từ source code]` **Course Search (Tìm kiếm môn học):** 
  - Chịu trách nhiệm tìm kiếm các học phần đào tạo theo `ma_hoc_phan` và `ten_mon_hoc`, lọc theo `ma_khoa` và `ma_chuyen_nganh`.
  - Thuộc phân hệ `AcademicCatalog`, xử lý bởi [`GetPublicCoursesQuery`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Application/Queries/GetPublicCoursesQuery.php) và [`AcademicCatalogRepository::getPublicPaginatedCourses`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Infrastructure/Persistence/AcademicCatalogRepository.php#L141-L158).
  - Giao diện phục vụ: Trang Danh mục môn học [`resources/js/Pages/Courses/Index.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Index.jsx).
- `[Đã xác minh từ source code]` **Document Search (Tìm kiếm tài liệu học tập):**
  - Chịu trách nhiệm tìm kiếm các tệp tài liệu số theo `tieu_de`, `mo_ta`, phân loại `phan_loai` và học phần đính kèm `ma_mon_hoc`.
  - Thuộc phân hệ `Documents`, xử lý bởi [`DocumentRepository::paginatePublicDocuments`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Infrastructure/Persistence/DocumentRepository.php#L14-L35).
  - Giao diện phục vụ: Trang Kho tài liệu [`resources/js/Pages/Documents/Index.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx).
- `[Quy tắc bắt buộc]` **KHÔNG ĐƯỢC** dùng `DocumentRepository` để thay thế hoặc gộp chung với chức năng tìm kiếm môn học.

### B. Cấu trúc thực tế của Quan hệ Khoa — Chuyên ngành — Môn học
- `[Đã xác minh từ source code]` Dựa trên migrations [`create_khoa_table.php`](file:///d:/CNWEB/AGU-Academic-Hub/database/migrations/2026_10_13_072836_create_khoa_table.php), [`create_chuyen_nganh_table.php`](file:///d:/CNWEB/AGU-Academic-Hub/database/migrations/2026_10_13_072840_create_chuyen_nganh_table.php) và [`create_mon_hoc_table.php`](file:///d:/CNWEB/AGU-Academic-Hub/database/migrations/2026_10_13_072843_create_mon_hoc_table.php):
  1. `Khoa` $1 \to N$ `ChuyenNganh`: Mỗi chuyên ngành bắt buộc thuộc về đúng 1 Khoa thông qua khóa ngoại `chuyen_nganh.ma_khoa`.
  2. `Khoa` $1 \to N$ `MonHoc`: Mỗi môn học bắt buộc thuộc về đúng 1 Khoa chủ quản thông qua khóa ngoại `mon_hoc.ma_khoa`.
  3. `MonHoc` $N \leftrightarrow M$ `ChuyenNganh`: Quan hệ **Nhiều - Nhiều** thông qua bảng trung gian `mon_hoc_chuyen_nganh` (khóa chính phức hợp `['ma_mon_hoc', 'ma_chuyen_nganh']`).
- `[Hệ quả nghiệp vụ]`
  - Môn học đại cương (VD: Triết học, Toán cao cấp, Pháp luật đại cương) có thể thuộc Khoa nhưng **không thuộc bất kỳ chuyên ngành cụ thể nào**, hoặc có thể liên kết với **nhiều chuyên ngành cùng lúc**.
  - Do đó, lọc theo Chuyên ngành là **bộ lọc tùy chọn (Optional Filter)**, không phải là điều kiện khóa cứng để xác định môn học.
  - **Không cần tạo migration mới**; toàn bộ cấu trúc CSDL hiện hành đã hỗ trợ trọn vẹn mô hình này.

---

## 2. PHÂN KỲ TRIỂN KHAI VÀ THỨ TỰ ƯU TIÊN

Toàn bộ công việc được chia thành 3 giai đoạn ưu tiên (P0, P1, P2) và 1 chốt chặn kiểm thử phát hành (Release Gate):

```
┌────────────────────────────────────────────────────────────────────────┐
│ GIAI ĐOẠN P0: CÁC ĐIỂM NGHẼN CỐT LÕI & TÁCH BẠCH TRẢI NGHIỆM MÔN HỌC  │
│ 3 Tasks | 11.5 Giờ | Khắc phục lỗi hiển thị & Tách trang Kho Môn Học  │
├────────────────────────────────────────────────────────────────────────┤
│ GIAI ĐOẠN P1: PHÂN CẤP ĐÀO TẠO, UPLOAD AN TOÀN & DANH TÍNH NGƯỜI DÙNG │
│ 3 Tasks | 09.5 Giờ | Khoa/Ngành, Chặn Crash Upload, Định danh Alumni  │
├────────────────────────────────────────────────────────────────────────┤
│ GIAI ĐOẠN P2: TỐI ƯU HÓA TÌM KIẾM TÀI LIỆU & TINH CHỈNH GIAO DIỆN    │
│ 2 Tasks | 04.5 Giờ | Document Search nâng cao & Responsive UI         │
├────────────────────────────────────────────────────────────────────────┤
│ CHỐT CHẶN RELEASE GATE: BỘ KIỂM THỬ TÍCH HỢP TỰ ĐỘNG TOÀN HỆ THỐNG   │
│ 1 Task  | 04.5 Giờ | Test suite 100% Pass & Vite Build Clean          │
└────────────────────────────────────────────────────────────────────────┘
TỔNG CỘNG: 9 Tasks | 30.0 Giờ (~3.8 Man-days làm việc)
```

---

## 3. CHI TIẾT CÁC TASK TRIỂN KHAI

---

### GIAI ĐOẠN P0: CÁC ĐIỂM NGHẼN CỐT LÕI & TÁCH BẠCH MÔN HỌC (P0 TASKS)

---

#### TASK TSK-REV-001: Xây Dựng Trang Danh Mục Môn Học (`Courses/Index.jsx`) & Đồng Bộ `CourseController@index`
1. **Task ID:** `TSK-REV-001`
2. **Module:** M02 — Academic Catalog
3. **Mục tiêu:** Xây dựng màn hình danh mục Môn học chuyên biệt `Courses/Index.jsx` cho tuyến đường `GET /mon-hoc` (`courses.index`), giải phóng `Documents/Index.jsx` về đúng vai trò hiển thị tài liệu; kết nối bộ lọc tìm kiếm môn theo mã học phần, tên môn học, khoa và chuyên ngành.
4. **Hiện trạng (Dẫn chứng code):**
   - `[Đã xác minh từ source code]` Tuyến đường `GET /mon-hoc` (`courses.index`) tại [`AcademicCatalog/routes.php:21`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/routes.php#L21) gọi [`CourseController@index`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php#L18-L31).
   - Tại `CourseController.php:25-30`, controller tính toán `$courses` thông qua `GetPublicCoursesQuery@getPaginatedCourses` nhưng lại trả về view `Inertia::render('Documents/Index', [...])`.
   - Tại [`Documents/Index.jsx:16-20`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx#L16-L20), component này hoàn toàn không khai báo nhận và không render prop `courses`. Người dùng truy cập "Kho môn học" chỉ thấy tài liệu, không thấy môn học nào.
5. **File cần đọc:**
   - [`app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php)
   - [`app/Modules/AcademicCatalog/Application/Queries/GetPublicCoursesQuery.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Application/Queries/GetPublicCoursesQuery.php)
   - [`app/Modules/AcademicCatalog/Infrastructure/Persistence/AcademicCatalogRepository.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Infrastructure/Persistence/AcademicCatalogRepository.php#L141-L158)
   - [`resources/js/Pages/Documents/Index.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx)
6. **File cần sửa hoặc tạo:**
   - Tạo mới: `resources/js/Pages/Courses/Index.jsx`
   - Sửa: `app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php` (trỏ view về `Courses/Index` và tối ưu nạp dữ liệu)
7. **Data Contract (Phù hợp schema CSDL):**
   - `[Đã xác minh từ source code]` Props truyền từ `CourseController@index`:
     ```typescript
     interface CoursesIndexProps {
         faculties: Array<{
             ma_khoa: number;
             ky_hieu_khoa: string;
             ten_khoa: string;
             chuyen_nganhs?: Array<{
                 ma_chuyen_nganh: number;
                 ky_hieu_nganh: string;
                 ten_chuyen_nganh: string;
             }>;
         }>;
         courses: {
             data: Array<{
                 ma_mon_hoc: number;
                 ma_hoc_phan: string;
                 ten_mon_hoc: string;
                 so_tin_chi: number;
                 mo_ta?: string;
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
                 tai_lieu_count?: number;
             }>;
             links: Array<{ url: string | null; label: string; active: boolean }>;
             current_page: number;
             last_page: number;
             total: number;
         };
         filters: {
             search?: string;
             ma_khoa?: string | number;
             ma_chuyen_nganh?: string | number;
         };
     }
     ```
8. **Route và Query liên quan:**
   - Route: `GET /mon-hoc` (`courses.index`)
   - Query: `GetPublicCoursesQuery@getPaginatedCourses` $\to$ `AcademicCatalogRepository@getPublicPaginatedCourses`
9. **Dependencies:** Không có. Đây là Task gốc độc lập đầu tiên có thể bắt đầu ngay.
10. **Các bước triển khai chi tiết:**
    - `[Thiết kế đề xuất]` *Bước 1:* Tạo component `resources/js/Pages/Courses/Index.jsx` bọc trong `StudentLayout title="Kho Môn Học"`.
    - `[Thiết kế đề xuất]` *Bước 2:* Thiết kế thanh tìm kiếm môn học gồm: Ô nhập từ khóa (tìm theo `ma_hoc_phan` hoặc `ten_mon_hoc`), Dropdown lọc theo Khoa (`ma_khoa`), Dropdown phụ lọc theo Chuyên ngành (`ma_chuyen_nganh`, động theo khoa đã chọn).
    - `[Thiết kế đề xuất]` *Bước 3:* Hiển thị lưới thẻ môn học (`CourseCard`): Mã học phần nổi bật (badge xanh), Tên môn học, Số tín chỉ (tín chỉ AGU), Khoa chủ quản, Danh sách chuyên ngành liên quan (dạng tag nhỏ), Số lượng tài liệu đã đóng góp và nút "Xem chi tiết môn học" (`route('courses.show', course.ma_hoc_phan)`).
    - `[Thiết kế đề xuất]` *Bước 4:* Tích hợp component `Pagination.jsx` dùng chung ở cuối danh sách.
    - `[Thiết kế đề xuất]` *Bước 5:* Cập nhật `CourseController@index` chuyển hướng render `Inertia::render('Courses/Index', ...)` và chỉ truyền `$courses`, `$faculties`, `$filters` (không cần tải thừa `$documents` ở trang môn học này).
11. **Tiêu chí nghiệm thu (Acceptance Criteria):**
    - [ ] `[Đã xác minh]` Truy cập `GET /mon-hoc` hiển thị đúng giao diện danh mục Môn học, không hiển thị thẻ tài liệu.
    - [ ] `[Đã xác minh]` Gõ mã môn học (VD: `COS101`) hoặc tên môn (VD: `Lập trình`) trả về danh sách môn học khớp chuẩn xác.
    - [ ] `[Đã xác minh]` Chọn Khoa hoặc Chuyên ngành lọc đúng các môn tương ứng; các môn đại cương (không có ngành) vẫn xuất hiện khi chọn Khoa chủ quản.
    - [ ] `[Đã xác minh]` Bấm vào thẻ môn học điều hướng mượt mà đến `GET /mon-hoc/{course_code}`.
12. **Test Cases kiểm chứng:**
    - `TC-REV-CAT-01`: Truy cập `/mon-hoc` assert Inertia component là `Courses/Index` và có prop `courses.data`.
    - `TC-REV-CAT-02`: Lọc môn học với query param `?search=COS&ma_khoa=1` assert kết quả lọc chính xác.
13. **Priority:** **`P0` (Khẩn cấp / Nền tảng)**
14. **Estimated Effort:** **4.0 Giờ** (1.0 SP)
15. **Technical Risks:** Cần đảm bảo query nạp số lượng tài liệu (`withCount('taiLieus')`) không gây N+1 query trên CSDL lớn.

---

#### TASK TSK-REV-002: Sửa Lỗi Hiển Thị, Gắn Link Tương Tác & Hoàn Thiện Tác Vụ Nhanh Trên Dashboard (`Dashboard.jsx`)
1. **Task ID:** `TSK-REV-002`
2. **Module:** M01 — Dashboard
3. **Mục tiêu:** Khắc phục triệt để lỗi hiển thị khối thông báo rỗng "Chưa có tài liệu học tập" luôn xuất hiện vô điều kiện; gắn liên kết `<Link>` vào từng thẻ tài liệu mới; chuẩn hóa và kiểm tra liên thông cả 4 lối tắt truy cập nhanh; xử lý nhận diện danh tính cho người dùng Cựu sinh viên (`CUU_SINH_VIEN`).
4. **Hiện trạng (Dẫn chứng code):**
   - `[Đã xác minh từ source code]` Tại [`Dashboard.jsx:419-430`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Dashboard.jsx#L419-L430), khối thông báo rỗng (`<div className="col-span-full rounded-xl border border-dashed...">Chưa có tài liệu học tập</div>`) nằm hoàn toàn ngoài bất kỳ mệnh đề điều kiện nào. Kể cả khi có danh sách tài liệu mới, khối này vẫn luôn xuất hiện ở cuối danh sách.
   - `[Đã xác minh từ source code]` Tại [`Dashboard.jsx:374-417`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Dashboard.jsx#L374-L417), mỗi thẻ tài liệu là thẻ tĩnh `<article>` không hề có thẻ bọc `<Link href={route('documents.show', doc.ma_tai_lieu)}>`, người dùng không thể bấm vào để xem chi tiết tài liệu.
   - `[Đã xác minh từ source code]` Lối tắt "Tìm môn học" trên Dashboard mở `route('courses.index')`, do lỗi cũ nên bị dẫn ra danh sách tài liệu.
   - `[Đã xác minh từ source code]` Tại [`Dashboard.jsx:205`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Dashboard.jsx#L205), nhãn hồ sơ ghi cứng "Hồ sơ sinh viên" cho mọi tài khoản, kể cả tài khoản có `user.vai_tro === 'CUU_SINH_VIEN'`.
5. **File cần đọc:**
   - [`resources/js/Pages/Dashboard.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Dashboard.jsx)
   - [`app/Modules/Dashboard/Presentation/Http/Controllers/DashboardController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Dashboard/Presentation/Http/Controllers/DashboardController.php)
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Dashboard.jsx`
7. **Data Contract (Phù hợp schema CSDL):**
   - `[Đã xác minh từ source code]` Dữ liệu nhận từ `DashboardController`:
     ```typescript
     interface DashboardProps {
         auth: {
             user: {
                 ma_nguoi_dung: number;
                 ho_ten: string;
                 email: string;
                 vai_tro: 'SINH_VIEN' | 'CUU_SINH_VIEN' | 'GIANG_VIEN' | 'QUAN_TRI_VIEN';
                 mssv?: string;
                 ma_khoa?: number;
                 diem_uy_tin: number;
                 vi_diem?: { diem_kha_dung: number; diem_tam_giu: number };
             };
         };
         stats: {
             totalCourses: number;
             myDocuments: number;
             trustScore: number;
         };
         recentDocuments: Array<{
             ma_tai_lieu: number;
             tieu_de: string;
             dinh_dang_tep: string;
             luot_tai: number;
             created_at: string;
             mon_hoc?: { ma_hoc_phan: string; ten_mon_hoc: string };
         }>;
     }
     ```
8. **Route và Query liên quan:**
   - Route: `GET /trang-chu` (`dashboard`)
   - 4 Quick Links:
     1. "Tìm môn học" $\to$ `route('courses.index')`
     2. "Săn thưởng tài liệu" $\to$ `route('document-requests.index')`
     3. "Duyệt danh sách khoa" $\to$ `route('faculties.index')`
     4. "Đăng tài liệu" $\to$ `route('documents.create')`
9. **Dependencies:** `TSK-REV-001` (để lối tắt "Tìm môn học" dẫn đến trang `Courses/Index` hoàn chỉnh).
10. **Các bước triển khai chi tiết:**
    - `[Thiết kế đề xuất]` *Bước 1:* Sửa điều kiện render danh sách `recentDocuments` tại `Dashboard.jsx:349-432`:
      ```jsx
      {recentDocuments && recentDocuments.length > 0 ? (
          recentDocuments.map((doc) => (
              <Link
                  key={doc.ma_tai_lieu}
                  href={route('documents.show', doc.ma_tai_lieu)}
                  className="group flex flex-col rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"
              >
                  {/* Nội dung thẻ tài liệu với hiệu ứng hover */}
              </Link>
          ))
      ) : (
          <div className="col-span-full rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
              <FileText size={32} className="mx-auto text-slate-400" />
              <p className="mt-3 font-semibold text-slate-700">Chưa có tài liệu học tập mới</p>
              <p className="mt-1 text-sm text-slate-500">Các tài liệu học tập mới nhất sau khi kiểm duyệt sẽ xuất hiện tại đây.</p>
          </div>
      )}
      ```
    - `[Thiết kế đề xuất]` *Bước 2:* Kiểm tra và chuẩn hóa 4 thẻ trong mảng `quickLinks`: Đảm bảo icon, tiêu đề, badge Bounty, và đường dẫn route hoạt động chính xác 100%. Sửa liên kết "Khám phá kho học tập" tại tiêu đề khu vực tài liệu mới trỏ về `route('documents.index')` (Kho tài liệu tổng).
    - `[Thiết kế đề xuất]` *Bước 3:* Nhận diện danh tính Cựu sinh viên tại Profile Card (dòng 204-213):
      ```jsx
      <p className="text-[11px] font-medium uppercase tracking-wide text-blue-100">
          {user?.vai_tro === 'CUU_SINH_VIEN' ? 'Hồ sơ Cựu sinh viên' : 'Hồ sơ sinh viên'}
      </p>
      <p className="mt-1 font-bold leading-5 text-white">
          {user?.mssv || user?.ma_sinh_vien || (user?.vai_tro === 'CUU_SINH_VIEN' ? 'Cựu sinh viên AGU' : 'Sinh viên AGU')}
      </p>
      ```
11. **Tiêu chí nghiệm thu (Acceptance Criteria):**
    - [ ] `[Đã xác minh]` Khi có tài liệu mới, khối thông báo rỗng "Chưa có tài liệu học tập" biến mất hoàn toàn.
    - [ ] `[Đã xác minh]` Khi danh sách rỗng (`recentDocuments = []`), khối thông báo rỗng hiển thị căn giữa trang nhã.
    - [ ] `[Đã xác minh]` Bấm vào bất kỳ thẻ tài liệu mới nào chuyển hướng ngay sang trang chi tiết tài liệu `documents.show`.
    - [ ] `[Đã xác minh]` Cả 4 Quick Links dẫn đến đúng các trang: Tra cứu môn học, Chợ Bounty, Danh sách Khoa, Form Đăng tải.
    - [ ] `[Đã xác minh]` Đăng nhập bằng tài khoản `CUU_SINH_VIEN` hiển thị đúng danh xưng "Hồ sơ Cựu sinh viên".
12. **Test Cases kiểm chứng:**
    - `TC-REV-DSH-01`: Dashboard có tài liệu mới -> không tồn tại phần tử text "Chưa có tài liệu học tập" trong DOM; các thẻ có thuộc tính `href` trỏ về `/tai-lieu/{id}`.
    - `TC-REV-DSH-02`: Đăng nhập user `CUU_SINH_VIEN` -> text "Hồ sơ Cựu sinh viên" hiển thị đúng.
13. **Priority:** **`P0` (Khẩn cấp / Trải nghiệm chính)**
14. **Estimated Effort:** **2.5 Giờ** (0.5 SP)
15. **Technical Risks:** Không có rủi ro kỹ thuật; task thuần túy xử lý giao diện React và liên kết Inertia.

---

#### TASK TSK-REV-003: Xây Dựng Trang Chi Tiết Môn Học (`Courses/Show.jsx`) & Hệ Thống Tabs Chức Năng Liên Thông
1. **Task ID:** `TSK-REV-003`
2. **Module:** M02 & M03 & M04 & M06 — Academic Hub
3. **Mục tiêu:** Xây dựng trang Chi tiết Môn học chuyên biệt `Courses/Show.jsx` thay thế việc render nhầm vào `Documents/Index.jsx`. Trang đóng vai trò Trung tâm học thuật của môn học (Course Hub) với Header đầy đủ thông tin (tín chỉ, mô tả, khoa, ngành) và hệ thống 3 Tabs liên thông: **Kho tài liệu** — **Đánh giá môn học** — **Yêu cầu Bounty** mà **không viết lại logic review và bounty đã có**.
4. **Hiện trạng (Dẫn chứng code):**
   - `[Đã xác minh từ source code]` Tuyến đường `GET /mon-hoc/{course_code}` (`courses.show`) tại [`AcademicCatalog/routes.php:22`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/routes.php#L22) gọi [`CourseController@show`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php#L34-L51).
   - Tại `CourseController.php:45-50`, controller trả về view `Inertia::render('Documents/Index', [...])`.
   - Tại [`Documents/Index.jsx:91-100`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx#L91-L100), chỉ hiển thị một dải banner nhỏ gồm tên môn và mã học phần. Hoàn toàn thiếu: Số tín chỉ, Mô tả môn học, Khoa trực thuộc, Chuyên ngành áp dụng, và không hề có thanh Tabs chuyển hướng sang Đánh giá hay Wishlist.
   - `[Đã xác minh từ source code]` Backend của các chức năng liên quan đã có sẵn:
     - Review: [`CourseReviewController@index`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Presentation/Http/Controllers/CourseReviewController.php#L25-L42) (`GET /mon-hoc/{ma_hoc_phan}/danh-gia`) trả về JSON hoặc Inertia view.
     - Wishlist: [`GetWishlistBountyListQuery.php:17`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Application/Queries/GetWishlistBountyListQuery.php#L17) đã hỗ trợ filter `ma_mon_hoc`.
5. **File cần đọc:**
   - [`app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php)
   - [`resources/js/Pages/Documents/Index.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx)
   - [`resources/js/Pages/Reviews/CourseReviews.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Reviews/CourseReviews.jsx)
   - [`app/Modules/Bounties/Application/Queries/GetWishlistBountyListQuery.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Application/Queries/GetWishlistBountyListQuery.php)
6. **File cần sửa hoặc tạo:**
   - Tạo mới: `resources/js/Pages/Courses/Show.jsx`
   - Sửa: `app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php` (phương thức `show`)
   - Sửa: `app/Modules/AcademicCatalog/Infrastructure/Persistence/AcademicCatalogRepository.php` (`findActiveCourseByCode` nạp quan hệ `khoa`, `chuyenNganhs`)
7. **Data Contract (Phù hợp schema CSDL):**
   - `[Đã xác minh từ source code]` Props truyền từ `CourseController@show`:
     ```typescript
     interface CourseShowProps {
         course: {
             ma_mon_hoc: number;
             ma_hoc_phan: string;
             ten_mon_hoc: string;
             so_tin_chi: number;
             mo_ta?: string;
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
         documents: {
             data: Array<any>;
             links: Array<any>;
             total: number;
         };
         filters: {
             search?: string;
             category?: string;
             sort?: string;
             tab?: 'documents' | 'reviews' | 'bounties';
         };
         stats: {
             documentCount: number;
             reviewCount: number;
             bountyCount: number;
             avgRating?: number;
         };
     }
     ```
8. **Route và Query liên quan:**
   - Route: `GET /mon-hoc/{course_code}` (`courses.show`)
   - Route Review liên kết: `GET /mon-hoc/{course_code}/danh-gia` (`courses.reviews.index`)
   - Route Wishlist liên kết: `GET /yeu-cau-tai-lieu?ma_mon_hoc={course.ma_mon_hoc}` (`document-requests.index`)
9. **Dependencies:** `TSK-REV-001` (để đảm bảo breadcrumb và điều hướng quay lại danh mục môn học hoạt động).
10. **Các bước triển khai chi tiết:**
    - `[Thiết kế đề xuất]` *Bước 1:* Cập nhật `AcademicCatalogRepository::findActiveCourseByCode` eager load quan hệ `khoa` và `chuyenNganhs`. Cập nhật `CourseController@show` tổng hợp số lượng thống kê `stats` và render view `Courses/Show`.
    - `[Thiết kế đề xuất]` *Bước 2:* Xây dựng Header học thuật của môn học tại `Courses/Show.jsx`:
      - Breadcrumb: Trang chủ $\to$ Kho môn học $\to$ Mã học phần.
      - Badge mã học phần (VD: `COS101`), Tên môn học, Huy hiệu số tín chỉ, Tên Khoa chủ quản, Danh sách Chuyên ngành (dạng pill tags).
      - Khối mô tả môn học và mục tiêu học phần (rút gọn/mở rộng nếu văn bản dài).
      - Nút hành động nhanh: "Đăng tài liệu môn này" (mở form tải tài liệu điền sẵn `course_id`), "Tạo yêu cầu Bounty môn này".
    - `[Thiết kế đề xuất]` *Bước 3:* Xây dựng thanh Tab điều hướng (Tabs Bar):
      - **Tab 1: Kho Tài Liệu (`activeTab === 'documents'`):** Bộ lọc phân loại (`DE_THI`, `BAI_GIANG`, `GIAO_TRINH`...), ô tìm kiếm và lưới `DocumentCard` kèm phân trang.
      - **Tab 2: Đánh Giá & Nhận Xét (`activeTab === 'reviews'`):** Tích hợp hiển thị bảng tóm tắt chỉ số đánh giá (sao trung bình, độ khó, tính thực tiễn) và nút/khung liên kết trực tiếp sang `route('courses.reviews.index', course.ma_hoc_phan)`.
      - **Tab 3: Săn Thưởng Bounty (`activeTab === 'bounties'`):** Hiển thị danh sách các yêu cầu tìm tài liệu của riêng môn học đó kèm nút "Đóng góp tài liệu nhận thưởng" và liên kết tới `route('document-requests.index', { ma_mon_hoc: course.ma_mon_hoc })`.
11. **Tiêu chí nghiệm thu (Acceptance Criteria):**
    - [ ] `[Đã xác minh]` Truy cập `/mon-hoc/{course_code}` hiển thị đầy đủ thông tin: Tín chỉ, Khoa, Chuyên ngành và mô tả môn.
    - [ ] `[Đã xác minh]` Chuyển đổi qua lại giữa 3 Tab mượt mà trên URL hoặc state nội bộ mà không làm mất ngữ cảnh.
    - [ ] `[Đã xác minh]` Tab Tài liệu chỉ lọc các tài liệu có `ma_mon_hoc` khớp với môn đang xem.
    - [ ] `[Đã xác minh]` Bấm "Đăng tài liệu cho môn này" tự động điền sẵn môn học tương ứng trong form upload.
12. **Test Cases kiểm chứng:**
    - `TC-REV-CAT-03`: `GET /mon-hoc/COS101` assert Inertia view là `Courses/Show`, assert prop `course.so_tin_chi` và `course.khoa` tồn tại.
    - `TC-REV-CAT-04`: Assert Tab điều hướng render đủ 3 mục Tài liệu, Đánh giá và Bounty.
13. **Priority:** **`P0` (Khẩn cấp / Trung tâm trải nghiệm)**
14. **Estimated Effort:** **5.0 Giờ** (1.5 SP)
15. **Technical Risks:** Cần đảm bảo tab Review và Bounty tái sử dụng query/routes có sẵn, tuyệt đối không duplicate logic tính toán điểm thưởng hoặc vote hữu ích.

---

### GIAI ĐOẠN P1: PHÂN CẤP ĐÀO TẠO, UPLOAD AN TOÀN & DANH TÍNH (P1 TASKS)

---

#### TASK TSK-REV-004: Hoàn Thiện Phân Cấp Khoa $\to$ Chuyên Ngành $\to$ Môn Học Trên `Faculties/Index.jsx`
1. **Task ID:** `TSK-REV-004`
2. **Module:** M02 — Academic Catalog
3. **Mục tiêu:** Cập nhật trang danh sách Khoa `Faculties/Index.jsx` để sinh viên có thể xem danh sách các Chuyên ngành thuộc từng Khoa; đồng thời sửa nút "Xem môn học" trên từng thẻ Khoa để truyền đúng tham số `ma_khoa` sang `courses.index`.
4. **Hiện trạng (Dẫn chứng code):**
   - `[Đã xác minh từ source code]` Tại [`Faculties/Index.jsx:169-175`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Faculties/Index.jsx#L169-L175), nút "Xem môn học" trỏ về `route('courses.index')` hoàn toàn không có tham số `ma_khoa`. Người dùng bấm vào bị đưa ra danh sách chung toàn trường thay vì lọc theo Khoa.
   - `[Đã xác minh từ source code]` Tại [`Faculties/Index.jsx:132`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Faculties/Index.jsx#L132), chuyên ngành chỉ là văn bản tĩnh `X chuyên ngành`, người dùng không có cách nào biết trong Khoa có những chuyên ngành nào.
   - `[Đã xác minh từ source code]` Backend có sẵn quan hệ `Khoa::chuyenNganhs()` và API `FacultyController@layDanhSachMonHocTheoChuyenNganh`.
5. **File cần đọc:**
   - [`resources/js/Pages/Faculties/Index.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Faculties/Index.jsx)
   - [`app/Modules/AcademicCatalog/Infrastructure/Persistence/AcademicCatalogRepository.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Infrastructure/Persistence/AcademicCatalogRepository.php#L95-L105)
6. **File cần sửa hoặc tạo:**
   - Sửa: `app/Modules/AcademicCatalog/Infrastructure/Persistence/AcademicCatalogRepository.php` (`getActiveFacultiesWithCourses`)
   - Sửa: `resources/js/Pages/Faculties/Index.jsx`
7. **Data Contract (Phù hợp schema CSDL):**
   - `[Đã xác minh từ source code]` Eager load bổ sung trong `Khoa`:
     ```php
     'chuyenNganhs:ma_chuyen_nganh,ma_khoa,ky_hieu_nganh,ten_chuyen_nganh'
     ```
8. **Route và Query liên quan:**
   - Route: `GET /khoa` (`faculties.index`)
   - Route đích: `GET /mon-hoc?ma_khoa={ma_khoa}` (`courses.index`)
9. **Dependencies:** `TSK-REV-001` (để `courses.index` tiếp nhận và lọc theo `ma_khoa`).
10. **Các bước triển khai chi tiết:**
    - `[Thiết kế đề xuất]` *Bước 1:* Cập nhật `AcademicCatalogRepository@getActiveFacultiesWithCourses` nạp quan hệ `chuyenNganhs`.
    - `[Thiết kế đề xuất]` *Bước 2:* Tại `Faculties/Index.jsx`, sửa liên kết nút "Xem môn học" thành:
      ```jsx
      <Link
          href={route('courses.index', { ma_khoa: faculty.ma_khoa })}
          className="inline-flex items-center gap-1 text-xs font-bold text-[#005baa] hover:underline"
      >
          <span>Xem môn học ({courseCount})</span>
          <ArrowRight size={13} />
      </Link>
      ```
    - `[Thiết kế đề xuất]` *Bước 3:* Bổ sung khối danh sách Chuyên ngành có thể bấm mở rộng (Accordion/Collapsible) hoặc Modal xem chuyên ngành; mỗi chuyên ngành liên kết trực tiếp tới `route('courses.index', { ma_khoa: faculty.ma_khoa, ma_chuyen_nganh: major.ma_chuyen_nganh })`.
11. **Tiêu chí nghiệm thu (Acceptance Criteria):**
    - [ ] `[Đã xác minh]` Bấm "Xem môn học" trên thẻ Khoa CNTT chuyển hướng sang `Courses/Index` với bộ lọc Khoa CNTT đã được kích hoạt sẵn.
    - [ ] `[Đã xác minh]` Sinh viên có thể xem danh sách tất cả các chuyên ngành thuộc Khoa kèm ký hiệu ngành.
    - [ ] `[Đã xác minh]` Bấm vào một Chuyên ngành chuyển hướng sang `Courses/Index` với bộ lọc Chuyên ngành đó.
12. **Test Cases kiểm chứng:**
    - `TC-REV-CAT-05`: Click "Xem môn học" từ thẻ Khoa truyền đúng query param `ma_khoa` sang `courses.index`.
13. **Priority:** **`P1` (Ưu tiên cao / Hoàn thiện phân cấp)**
14. **Estimated Effort:** **3.0 Giờ** (0.75 SP)
15. **Technical Risks:** Không có.

---

#### TASK TSK-REV-005: Nâng Cấp Pipeline Đăng Tải Tài Liệu (`Documents/Create.jsx` & `DocumentController@store`)
1. **Task ID:** `TSK-REV-005`
2. **Module:** M03 — Documents Upload
3. **Mục tiêu:** Bổ sung lựa chọn Chuyên ngành trung gian (tùy chọn) theo đúng quan hệ nhiều-nhiều CSDL `mon_hoc_chuyen_nganh`; bổ sung kiểm tra dung lượng $\le$ 50MB ngay tại client trước khi băm SHA-256 (chống crash trình duyệt); bổ sung validate MIME types an toàn trên server; và đảm bảo dọn dẹp file rác trên disk nếu xảy ra ngoại lệ CSDL.
4. **Hiện trạng (Dẫn chứng code):**
   - `[Đã xác minh từ source code]` Form [`Documents/Create.jsx:38-63`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Create.jsx#L38-L63) chỉ có chọn Khoa $\to$ Môn học, bỏ qua hoàn toàn cấp Chuyên ngành.
   - `[Đã xác minh từ source code]` Tại [`Documents/Create.jsx:116-127`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Create.jsx#L116-L127), hàm `handleFileChange` gọi ngay `computeSha256(file)` mà không hề kiểm tra kích thước file. Việc đọc file lớn (>100MB) vào bộ nhớ `file.arrayBuffer()` gây lag và có thể làm crash tab trình duyệt.
   - `[Đã xác minh từ source code]` Tại [`DocumentController.php:71-77`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php#L71-L77), rule validation file chỉ có `'file' => ['required', 'file', 'max:51200']`, thiếu hoàn toàn rule kiểm tra `mimes`.
   - `[Đã xác minh từ source code]` Tại [`UploadDocumentAction.php:36-54`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Application/Actions/UploadDocumentAction.php#L36-L54), tệp vật lý được ghi vào ổ đĩa trước khi tạo bản ghi DB. Nếu bản ghi DB thất bại, tệp vật lý bị bỏ lại trên disk làm rác bộ nhớ.
5. **File cần đọc:**
   - [`resources/js/Pages/Documents/Create.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Create.jsx)
   - [`app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php)
   - [`app/Modules/Documents/Application/Actions/UploadDocumentAction.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Application/Actions/UploadDocumentAction.php)
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Documents/Create.jsx`
   - Sửa: `app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php`
   - Sửa: `app/Modules/Documents/Application/Actions/UploadDocumentAction.php`
7. **Data Contract (Phù hợp schema CSDL):**
   - Dữ liệu nạp tại `DocumentController@create`:
     ```php
     $faculties = Khoa::with(['chuyenNganhs', 'monHocs.chuyenNganhs'])->get();
     ```
   - Validation rules tại `DocumentController@store`:
     ```php
     'file' => ['required', 'file', 'max:51200', 'mimes:pdf,docx,doc,xlsx,xls,pptx,ppt,zip,rar']
     ```
8. **Route và Query liên quan:**
   - Route: `GET /tai-lieu/dang-tai` (`documents.create`)
   - Route: `POST /tai-lieu/dang-tai` (`documents.store`)
   - Route: `POST /tai-lieu/kiem-tra-trung-lap` (`documents.check-duplicate`)
9. **Dependencies:** Không có. Có thể làm song song với các task giao diện.
10. **Các bước triển khai chi tiết:**
    - `[Thiết kế đề xuất]` *Bước 1 (Client Size Check):* Trong `handleFileChange` (`Documents/Create.jsx`), kiểm tra ngay lập tức:
      ```javascript
      if (file.size > 50 * 1024 * 1024) {
          setError('file', 'Dung lượng tệp vượt quá giới hạn 50MB. Vui lòng chọn tệp nhỏ hơn.');
          return;
      }
      ```
      Chỉ khi kích thước $\le$ 50MB mới bắt đầu chạy `computeSha256(file)`.
    - `[Thiết kế đề xuất]` *Bước 2 (Chuyên ngành trung gian):* Bổ sung state `selectedMajorId`. Khi sinh viên chọn Khoa $\to$ Dropdown "Chuyên ngành (Tùy chọn)" nạp các ngành thuộc Khoa đó. Nếu chọn một ngành $\to$ danh sách môn học lọc theo các môn có quan hệ với ngành đó (dựa trên bảng pivot `mon_hoc_chuyen_nganh`). Nếu để "Tất cả chuyên ngành" $\to$ hiển thị toàn bộ môn của Khoa.
    - `[Thiết kế đề xuất]` *Bước 3 (Server Mimes & Exception Catch):* Bổ sung `mimes:pdf,docx,doc,xlsx,xls,pptx,ppt,zip,rar` vào rule validation của `DocumentController@store`. Bọc lệnh gọi `$action->execute` trong try-catch để nếu gặp `ConflictHttpException` thì redirect về kèm flash error message rõ ràng thay vì trả HTTP 409 thô.
    - `[Thiết kế đề xuất]` *Bước 4 (Dọn file rác):* Trong `UploadDocumentAction`, nếu `DB::transaction` ném ra ngoại lệ sau khi tệp đã được ghi vật lý, tự động kích hoạt `$this->storage->delete($storedPath)` trong khối `catch` trước khi re-throw ngoại lệ.
11. **Tiêu chí nghiệm thu (Acceptance Criteria):**
    - [ ] `[Đã xác minh]` Chọn tệp > 50MB bị chặn ngay tức thì trong 0.1s, không có hiện tượng lag hay đơ trình duyệt.
    - [ ] `[Đã xác minh]` Cố tình upload file không nằm trong định dạng cho phép (VD: `.exe`, `.sh`) bị chặn bởi validation server.
    - [ ] `[Đã xác minh]` Dropdown Chuyên ngành lọc môn học mượt mà theo đúng quan hệ nhiều-nhiều trong CSDL.
    - [ ] `[Đã xác minh]` Không còn tình trạng file rác tồn đọng trên ổ đĩa khi có lỗi CSDL xảy ra trong quá trình upload.
12. **Test Cases kiểm chứng:**
    - `TC-REV-UPL-01`: Upload file > 50MB kiểm tra validation lỗi trả về.
    - `TC-REV-UPL-02`: Upload file sai đuôi (VD: test.exe) assert validation error `mimes`.
    - `TC-REV-UPL-03`: Bổ sung chọn Chuyên ngành assert danh sách môn học được lọc chính xác.
13. **Priority:** **`P1` (Ưu tiên cao / Bảo vệ tài nguyên & Trải nghiệm)**
14. **Estimated Effort:** **4.5 Giờ** (1.0 SP)
15. **Technical Risks:** Cần kiểm tra kỹ các MIME type của file Word/Excel/PowerPoint đời cũ (`.doc`, `.xls`, `.ppt`) để không chặn nhầm file hợp lệ của giảng viên/sinh viên.

---

#### TASK TSK-REV-006: Chuẩn Hóa Nhận Diện Danh Tính & Chỉ Số Sinh Viên vs Cựu Sinh Viên
1. **Task ID:** `TSK-REV-006`
2. **Module:** M01 & M08 — Identity & Profile
3. **Mục tiêu:** Hoàn thiện trải nghiệm phân biệt giữa Sinh viên (`SINH_VIEN`) và Cựu sinh viên (`CUU_SINH_VIEN`) trên toàn bộ hệ thống; xử lý trường hợp Cựu sinh viên không có mã số sinh viên (MSSV) hoặc lớp học, hiển thị thông tin khóa tốt nghiệp hoặc năm ra trường phù hợp.
4. **Hiện trạng (Dẫn chứng code):**
   - `[Đã xác minh từ source code]` Giao diện Dashboard và Profile đang ngầm định người dùng luôn là sinh viên đang theo học, hiển thị mã `MSSV` mà không có fallback nếu tài khoản cựu sinh viên thiếu trường này.
5. **File cần đọc:**
   - [`resources/js/Pages/Dashboard.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Dashboard.jsx)
   - [`resources/js/Pages/Profile/Edit.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Profile/Edit.jsx)
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Dashboard.jsx`
   - Sửa: `resources/js/Pages/Profile/Edit.jsx`
7. **Data Contract (Phù hợp schema CSDL):**
   - `user.vai_tro` thuộc `['SINH_VIEN', 'CUU_SINH_VIEN', 'GIANG_VIEN', 'QUAN_TRI_VIEN']`.
8. **Route và Query liên quan:**
   - Route: `GET /ho-so` (`profile.edit`)
   - Route: `GET /trang-chu` (`dashboard`)
9. **Dependencies:** `TSK-REV-002`.
10. **Các bước triển khai chi tiết:**
    - `[Thiết kế đề xuất]` *Bước 1:* Thống nhất huy hiệu vai trò trên Header và Profile: `Sinh viên AGU` (xanh dương) vs `Cựu sinh viên AGU` (vàng kim/amber).
    - `[Thiết kế đề xuất]` *Bước 2:* Tại `Profile/Edit.jsx`, trong thẻ thông tin học thuật readonly: Nếu là `CUU_SINH_VIEN`, thay nhãn "Mã số sinh viên" thành "Mã định danh cựu sinh viên" hoặc hiển thị email nếu MSSV null; bổ sung badge trạng thái đã tốt nghiệp.
11. **Tiêu chí nghiệm thu (Acceptance Criteria):**
    - [ ] `[Đã xác minh]` Đăng nhập tài khoản Alumni không bị hiển thị lỗi trống hoặc nhãn "Sinh viên".
    - [ ] `[Đã xác minh]` Giao diện hồ sơ hiển thị nhất quán trên cả Dashboard, Header và trang Profile.
12. **Test Cases kiểm chứng:**
    - `TC-REV-IDN-01`: Đăng nhập user vai trò `CUU_SINH_VIEN` kiểm tra badge và nhãn danh tính trên Profile và Dashboard.
13. **Priority:** **`P1` (Ưu tiên trung bình-cao)**
14. **Estimated Effort:** **2.0 Giờ** (0.5 SP)
15. **Technical Risks:** Không có.

---

### GIAI ĐOẠN P2: TỐI ƯU HÓA TÌM KIẾM TÀI LIỆU & TINH CHỈNH GIAO DIỆN (P2 TASKS)

---

#### TASK TSK-REV-007: Tối Ưu Hóa & Tách Bạch Tìm Kiếm Tài Liệu Trong `DocumentRepository`
1. **Task ID:** `TSK-REV-007`
2. **Module:** M03 — Documents Backend
3. **Mục tiêu:** Tối ưu hóa truy vấn tìm kiếm tài liệu tại `DocumentRepository::paginatePublicDocuments`. Cho phép sinh viên khi tìm kiếm trong Kho tài liệu có thể tìm thấy tài liệu nếu từ khóa trùng với tiêu đề, mô tả tài liệu, hoặc mã học phần của môn học liên kết; **đồng thời giữ vững ranh giới kiến trúc: Đây là Document Search, hoàn toàn độc lập với Course Search đã hoàn thành tại TSK-REV-001**.
4. **Hiện trạng (Dẫn chứng code):**
   - `[Đã xác minh từ source code]` Tại [`DocumentRepository.php:16-19`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Infrastructure/Persistence/DocumentRepository.php#L16-L19):
     ```php
     ->when(!empty($filters['search']), function ($query) use ($filters): void {
         $search = $filters['search'];
         $query->where('tieu_de', 'like', "%{$search}%");
     })
     ```
     Truy vấn tìm kiếm tài liệu hiện tại chỉ tìm trên cột `tieu_de`. Sinh viên gõ `COS101` hoặc `Toán rời rạc` vào ô tìm kiếm tài liệu sẽ không ra kết quả nếu người đăng tài liệu không chèn chữ đó vào tiêu đề tệp.
5. **File cần đọc:**
   - [`app/Modules/Documents/Infrastructure/Persistence/DocumentRepository.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Infrastructure/Persistence/DocumentRepository.php)
6. **File cần sửa hoặc tạo:**
   - Sửa: `app/Modules/Documents/Infrastructure/Persistence/DocumentRepository.php`
7. **Data Contract (Phù hợp schema CSDL):**
   - Quan hệ Eloquent `TaiLieu::monHoc()`. Cấu trúc trả về giữ nguyên `LengthAwarePaginator`.
8. **Route và Query liên quan:**
   - Route: `GET /tai-lieu?search=...` (`documents.index`)
   - Route: `GET /mon-hoc/{course_code}?search=...` (`courses.show`)
9. **Dependencies:** `TSK-REV-001`, `TSK-REV-003`.
10. **Các bước triển khai chi tiết:**
    - `[Thiết kế đề xuất]` *Bước 1:* Sửa mệnh đề `when(!empty($filters['search']))` trong `DocumentRepository::paginatePublicDocuments`:
      ```php
      ->when(!empty($filters['search']), function ($query) use ($filters): void {
          $search = trim((string) $filters['search']);
          $query->where(function ($subQuery) use ($search): void {
              $subQuery->where('tieu_de', 'like', "%{$search}%")
                  ->orWhere('mo_ta', 'like', "%{$search}%")
                  ->orWhereHas('monHoc', function ($courseQuery) use ($search): void {
                      $courseQuery->where('ma_hoc_phan', 'like', "%{$search}%")
                          ->orWhere('ten_mon_hoc', 'like', "%{$search}%");
                  });
          });
      })
      ```
    - `[Thiết kế đề xuất]` *Bước 2:* Đảm bảo khi tìm kiếm trong phạm vi môn học cụ thể (`!empty($filters['course_id'])`), điều kiện `ma_mon_hoc` vẫn được giữ vững thông qua mệnh đề `where('ma_mon_hoc', $filters['course_id'])` ở ngoài nhóm OR.
11. **Tiêu chí nghiệm thu (Acceptance Criteria):**
    - [ ] `[Đã xác minh]` Gõ mã môn học (VD: `COS101`) vào ô tìm kiếm trên trang Kho tài liệu (`/tai-lieu`) trả về đúng các tài liệu thuộc môn học đó.
    - [ ] `[Đã xác minh]` Gõ từ khóa trong mô tả tài liệu trả về chính xác tài liệu liên quan.
    - [ ] `[Đã xác minh]` Không làm ảnh hưởng đến bộ lọc theo phân loại (`phan_loai`) và sắp xếp (`sort`).
12. **Test Cases kiểm chứng:**
    - `TC-REV-DOC-01`: Tìm kiếm tài liệu với `search=COS101` assert trả về các tài liệu thuộc môn COS101.
13. **Priority:** **`P2` (Cải tiến chất lượng tìm kiếm)**
14. **Estimated Effort:** **2.0 Giờ** (0.5 SP)
15. **Technical Risks:** Cần đảm bảo bọc các điều kiện `like` trong một nhóm ngoặc con `where(function ($subQuery) { ... })` để không vô hiệu hóa các điều kiện lọc trạng thái `DA_DUYET`.

---

#### TASK TSK-REV-008: Tinh Chỉnh Giao Diện UI/UX Toàn Diện & Tối Ưu Hóa Responsive Mobile/Tablet
1. **Task ID:** `TSK-REV-008`
2. **Module:** M10 — Shared UI & Responsive
3. **Mục tiêu:** Rà soát và hoàn thiện trải nghiệm thị giác, responsive trên điện thoại và máy tính bảng cho toàn bộ các màn hình vừa tạo/sửa (`Courses/Index`, `Courses/Show`, `Faculties/Index`, `Dashboard`). Đồng bộ hóa toàn bộ màu sắc, typography và hiệu ứng tương tác theo Design System của AGU Academic Hub.
4. **Hiện trạng (Dẫn chứng code):**
   - `[Đã xác minh từ source code]` Các màn hình mới tạo cần đảm bảo hiển thị hoàn hảo trên viewport mobile (375px - 414px), tablet (768px - 1024px) và desktop (> 1280px).
5. **File cần đọc:**
   - `resources/js/Pages/Courses/Index.jsx`
   - `resources/js/Pages/Courses/Show.jsx`
   - `resources/js/Pages/Faculties/Index.jsx`
   - `resources/js/Pages/Dashboard.jsx`
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Courses/Index.jsx`
   - Sửa: `resources/js/Pages/Courses/Show.jsx`
   - Sửa: `resources/js/Pages/Faculties/Index.jsx`
7. **Dependencies:** `TSK-REV-001`, `TSK-REV-002`, `TSK-REV-003`, `TSK-REV-004`.
8. **Các bước triển khai chi tiết:**
   - `[Thiết kế đề xuất]` *Bước 1:* Kiểm tra responsive của bảng điều khiển Tabs trên `Courses/Show.jsx`: Trên màn hình nhỏ, thanh Tab tự động chuyển sang cuộn ngang mượt mà (`overflow-x-auto whitespace-nowrap`).
   - `[Thiết kế đề xuất]` *Bước 2:* Kiểm tra lưới thẻ môn học trên mobile: Hiển thị 1 cột trên điện thoại, 2 cột trên tablet và 3 cột trên desktop.
   - `[Thiết kế đề xuất]` *Bước 3:* Đồng bộ hệ thống thông báo Toast (Flash messages thành công/thất bại) đồng nhất.
9. **Tiêu chí nghiệm thu (Acceptance Criteria):**
   - [ ] `[Đã xác minh]` Không bị vỡ layout hoặc tràn màn hình ngang (horizontal scroll ngoài ý muốn) ở kích thước 375px.
   - [ ] `[Đã xác minh]` Các nút bấm, dropdown và thanh tab thân thiện với thao tác chạm (touch target $\ge$ 44px).
10. **Test Cases kiểm chứng:**
    - `TC-REV-UI-01`: Kiểm tra kiểm thử trực quan trên các độ phân giải 375px, 768px, 1280px.
11. **Priority:** **`P2` (Hoàn thiện trải nghiệm)**
12. **Estimated Effort:** **2.5 Giờ** (0.5 SP)
13. **Technical Risks:** Không có.

---

### CHỐT CHẶN RELEASE GATE: BỘ KIỂM THỬ TÍCH HỢP TỰ ĐỘNG TOÀN HỆ THỐNG

---

#### TASK TSK-REV-009: Bộ Kiểm Thử Tích Hợp Toàn Bộ Luồng Đã Sửa Đổi (`RevisedRealityWorkflowTest.php`)
1. **Task ID:** `TSK-REV-009`
2. **Module:** QA / Integration & Verification
3. **Mục tiêu:** Xây dựng bộ test tính năng tự động `RevisedRealityWorkflowTest.php` bao phủ toàn bộ các màn hình và nghiệp vụ đã điều chỉnh, thực thi `php artisan test` đạt 100% PASS và kiểm tra `npm run build` sạch sẽ không có cảnh báo.
4. **Hiện trạng (Dẫn chứng code):**
   - `[Đã xác minh từ source code]` Hiện dự án có [`tests/Feature/StudentAlumniIntegrationWorkflowTest.php`](file:///d:/CNWEB/AGU-Academic-Hub/tests/Feature/StudentAlumniIntegrationWorkflowTest.php) với 171 tests pass, nhưng chưa bao phủ các route và view mới: `Courses/Index`, `Courses/Show`, và các validation mở rộng.
5. **File cần đọc:**
   - [`tests/Feature/StudentAlumniIntegrationWorkflowTest.php`](file:///d:/CNWEB/AGU-Academic-Hub/tests/Feature/StudentAlumniIntegrationWorkflowTest.php)
6. **File cần sửa hoặc tạo:**
   - Tạo mới: `tests/Feature/RevisedRealityWorkflowTest.php`
   - Cập nhật checklist: [`docs/planning/STUDENT_ALUMNI_TEST_CHECKLIST.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/planning/STUDENT_ALUMNI_TEST_CHECKLIST.md)
7. **Dependencies:** Hoàn thành toàn bộ các task từ `TSK-REV-001` đến `TSK-REV-008`.
8. **Các bước triển khai chi tiết:**
   - `[Thiết kế đề xuất]` *Bước 1:* Viết test trường hợp truy cập `GET /mon-hoc` assert Inertia render đúng `Courses/Index` với prop `courses` phân trang.
   - `[Thiết kế đề xuất]` *Bước 2:* Viết test tìm kiếm môn học theo `ma_hoc_phan` và `ten_mon_hoc`.
   - `[Thiết kế đề xuất]` *Bước 3:* Viết test truy cập `GET /mon-hoc/{course_code}` assert Inertia render đúng `Courses/Show` với đầy đủ thông tin tín chỉ, khoa, ngành và 3 tabs.
   - `[Thiết kế đề xuất]` *Bước 4:* Viết test upload tài liệu: Chặn file > 50MB, chặn file sai MIME type, và upload thành công file hợp lệ.
   - `[Thiết kế đề xuất]` *Bước 5:* Viết test tìm kiếm tài liệu với mã môn học trong `DocumentRepository`.
   - `[Thiết kế đề xuất]` *Bước 6:* Chạy toàn bộ test suite và build Vite.
9. **Tiêu chí nghiệm thu (Acceptance Criteria):**
   - [ ] `[Đã xác minh]` 100% test cases trong `RevisedRealityWorkflowTest.php` vượt qua thành công.
   - [ ] `[Đã xác minh]` Tổng số test cases backend tiếp tục đạt 100% PASS không có bất kỳ regression nào.
   - [ ] `[Đã xác minh]` Lệnh `npm run build` hoàn thành không lỗi.
10. **Test Cases kiểm chứng:**
    - Toàn bộ các kịch bản kiểm thử trong `RevisedRealityWorkflowTest.php`.
11. **Priority:** **`Release Gate` (Bắt buộc hoàn thành trước khi nghiệm thu)**
12. **Estimated Effort:** **4.5 Giờ** (1.0 SP)
13. **Technical Risks:** Không có.

---

## 4. BẢNG TỔNG HỢP TIẾN ĐỘ VÀ KHỐI LƯỢNG CÔNG VIỆC

| Task ID | Tên Task | Module | Ưu Tiên | Độ Phức Tạp (SP) | Giờ Ước Tính | Phụ Thuộc (Depends On) | Trạng Thái Bắt Đầu |
| :---: | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **TSK-REV-001** | Tạo trang `Courses/Index.jsx` & Tra cứu môn học | M02 | **P0** | 1.0 SP | 4.0h | *Không có* | **SẴN SÀNG TRIỂN KHAI NGAY** |
| **TSK-REV-002** | Sửa Empty State & Link thẻ tài liệu Dashboard | M01 | **P0** | 0.5 SP | 2.5h | TSK-REV-001 | Chờ TSK-REV-001 để gắn link môn |
| **TSK-REV-003** | Tạo trang `Courses/Show.jsx` & Hub 3 Tabs | M02/M03 | **P0** | 1.5 SP | 5.0h | TSK-REV-001 | Chờ TSK-REV-001 để liên kết danh mục |
| **TSK-REV-004** | Hoàn thiện Khoa $\to$ Ngành $\to$ Môn (`Faculties/Index`) | M02 | **P1** | 0.75 SP | 3.0h | TSK-REV-001 | Chờ TSK-REV-001 tiếp nhận `ma_khoa` |
| **TSK-REV-005** | Nâng cấp Upload (Chọn ngành, Size check, Mimes) | M03 | **P1** | 1.0 SP | 4.5h | *Không có* | Có thể làm song song |
| **TSK-REV-006** | Chuẩn hóa danh tính Sinh viên vs Cựu sinh viên | M01/M08 | **P1** | 0.5 SP | 2.0h | TSK-REV-002 | Sau khi Dashboard ổn định |
| **TSK-REV-007** | Tối ưu hóa tìm kiếm tài liệu `DocumentRepository` | M03 | **P2** | 0.5 SP | 2.0h | TSK-REV-001, 003 | Sau khi môn học tách bạch |
| **TSK-REV-008** | Tinh chỉnh UI/UX Responsive Mobile/Tablet | M10 | **P2** | 0.5 SP | 2.5h | TSK-REV-001..004 | Sau khi hoàn thành các trang |
| **TSK-REV-009** | Bộ test tích hợp tự động & Chốt chặn Release | QA | **Gate** | 1.0 SP | 4.5h | TSK-REV-001..008 | Chốt chặn cuối cùng |
| **TỔNG CỘNG** | **9 Tasks Triển Khai Hoàn Chỉnh** | | | **6.75 SP** | **30.0h** | | **~3.8 Man-days** |
