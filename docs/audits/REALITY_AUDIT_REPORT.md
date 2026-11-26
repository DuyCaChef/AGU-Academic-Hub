# BÁO CÁO KIỂM TOÁN THỰC TẾ & KHOẢNG TRỐNG TÍNH NĂNG
## AGU Academic Hub — Phân Hệ Sinh Viên (`SINH_VIEN`) & Cựu Sinh Viên (`CUU_SINH_VIEN`)
**Người thực hiện:** Senior Full-stack Engineer & Technical Auditor  
**Ngày kiểm toán:** 2026-11-06  
**Kiến trúc:** Laravel 11 Modular Monolith, Inertia.js, React 18, Vite, Tailwind CSS  
**Nguyên tắc kiểm toán:** Kiểm chứng trực tiếp qua chuỗi `Route → Middleware → Controller → Action/Query → Repository → Database → Inertia Response → React Component`. Không mặc định task ghi `DONE` hoặc test ghi `PASSED` là đã hoàn chỉnh trên thực tế.

---

## 1. TỔNG QUAN HIỆN TRẠNG TRIỂN KHAI (EXECUTIVE REALITY CHECK)

Sau khi rà soát độc lập toàn bộ mã nguồn, cấu hình tuyến đường (routes), controller, repository và các component giao diện React, nhóm kiểm toán xác định:

> [!WARNING]
> Mặc dù bộ test tự động backend (`php artisan test`) đạt 171/171 passed và frontend build Vite thành công, **nhiều nghiệp vụ người dùng cốt lõi giữa Frontend và Backend thực tế đang bị ngắt kết nối (disconnected), thiếu trang chuyên biệt, hoặc hiển thị sai lệch bản chất chức năng**.

### Thống kê trạng thái phân loại chức năng (Chuẩn hóa theo 33 Tính năng nguyên tử tại Ma trận):
- **`FULLY_IMPLEMENTED` (Đầy đủ cả FE, BE, Integration & Runtime):** **18 / 33 chức năng** (54.5%) — Bao gồm: F-10, F-12, F-16, F-18, F-19, F-20, F-22, F-23, F-24, F-25, F-26, F-27, F-28, F-29, F-30, F-31, F-32, F-33.
- **`PARTIALLY_IMPLEMENTED` (Triển khai một phần / Thiếu mắt xích tích hợp):** **08 / 33 chức năng** (24.2%) — Bao gồm: F-01 (Khoa thiếu link param), F-06 (Kho tài liệu thiếu vỏ bọc tab môn), F-07 (Đánh giá môn thiếu tab liên thông), F-11 (Duyệt khoa bị hụt luồng tiếp), F-14 (Danh tính Alumni bị gán cứng sinh viên), F-15 (Đăng tải thiếu cấp chuyên ngành), F-17 (Upload thiếu validate size client trước khi hash), F-21 (Upload thiếu validate MIME server và dọn file rác).
- **`BROKEN` (Lỗi logic hiển thị hoặc trỏ nhầm nghiệp vụ):** **04 / 33 chức năng** (12.1%) — Bao gồm: F-03 (Môn học render nhầm tài liệu), F-04 (Tìm môn học gọi nhầm tìm kiếm tài liệu), F-09 (Lối tắt "Tìm môn học" trên Dashboard trỏ nhầm danh sách tài liệu), F-13 (Khối tài liệu mới Dashboard render rỗng vô điều kiện và thẻ thiếu link).
- **`UI_ONLY` (Chỉ có giao diện tóm tắt / Thiếu trang chi tiết):** **01 / 33 chức năng** (03.0%) — Bao gồm: F-05 (Trang chi tiết môn học `Courses/Show.jsx` chưa được tạo).
- **`BACKEND_ONLY` (Máy chủ có sẵn nhưng giao diện người dùng chưa kết nối):** **01 / 33 chức năng** (03.0%) — Bao gồm: F-02 (API lấy môn theo chuyên ngành `FacultyController@layDanhSachMonHocTheoChuyenNganh`).
- **`NOT_IMPLEMENTED` (Chưa tích hợp cả FE và BE):** **01 / 33 chức năng** (03.0%) — Bao gồm: F-08 (Tabs Môn học: Tích hợp bộ lọc Wishlist / Bounty theo môn học vào trang chi tiết môn).
- **`NEEDS_VERIFICATION`:** **00 / 33 chức năng** (00.0%).
- **TỔNG CỘNG:** **33 / 33 chức năng (100.0%)**. Chi tiết đối chiếu từng dòng xem tại [`docs/audits/FEATURE_GAP_MATRIX.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/FEATURE_GAP_MATRIX.md).

---

## 2. ĐÁNH GIÁ CHI TIẾT THEO CÁC NHÓM TRỌNG TÂM

### NHÓM A: KHO MÔN HỌC (ACADEMIC CATALOG)

#### A.1. Phân Cấp: Khoa → Chuyên Ngành → Môn Học
- **Chuỗi thực thi:**
  - Route: `GET /khoa` (`faculties.index`) trong [`app/Modules/AcademicCatalog/routes.php:14`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/routes.php#L14)
  - Controller: [`FacultyController@index`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/FacultyController.php#L19-L26)
  - Query: [`GetPublicFacultiesQuery`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Application/Queries/GetPublicFacultiesQuery.php#L18-L23) $\to$ [`AcademicCatalogRepository@getActiveFacultiesWithCourses`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Infrastructure/Persistence/AcademicCatalogRepository.php#L95-L100)
  - View: [`resources/js/Pages/Faculties/Index.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Faculties/Index.jsx)
- **Bằng chứng mã nguồn & Thực tế hoạt động:**
  - `Faculties/Index.jsx` hiển thị danh sách các Khoa kèm thống kê tổng số môn học và số lượng chuyên ngành (`faculty.chuyen_nganhs_count`).
  - **KHOẢNG TRỐNG NGHIÊM TRỌNG:**
    1. Trên từng thẻ Khoa, chuyên ngành chỉ xuất hiện dưới dạng chuỗi văn bản tĩnh: `<strong className="text-slate-700">{majorCount}</strong> chuyên ngành` ([`Faculties/Index.jsx:132`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Faculties/Index.jsx#L132)). **Hoàn toàn không có giao diện (trang, danh sách, modal hay dropdown) để người dùng xem danh sách các chuyên ngành thuộc khoa đó**.
    2. Nút "Xem môn học" ở góc dưới mỗi thẻ Khoa ([`Faculties/Index.jsx:169-175`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Faculties/Index.jsx#L169-L175)) trỏ thẳng về `route('courses.index')` **mà không hề truyền tham số `ma_khoa`**! Người dùng nhấn vào nút này sẽ bị đẩy ra danh sách tài liệu toàn trường thay vì danh sách môn của khoa đó.
    3. Backend có sẵn API `GET /khoa/chuyen-nganh/{id}/mon-hoc` ([`FacultyController.php:39-46`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/FacultyController.php#L39-L46)) nhưng phía Frontend người dùng cuối chưa hề có màn hình nào gọi và render API này.
- **Kết luận:** `PARTIALLY_IMPLEMENTED` (Khoa có giao diện, nhưng Chuyên ngành bị bỏ quên, liên kết lọc môn học bị thiếu param).

---

#### A.2. Tìm Kiếm Môn Học Theo Mã Học Phần & Tên Môn
- **Chuỗi thực thi:**
  - Route: `GET /mon-hoc` (`courses.index`) trong [`app/Modules/AcademicCatalog/routes.php:21`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/routes.php#L21)
  - Controller: [`CourseController@index`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php#L18-L31)
  - Query: [`GetPublicCoursesQuery@getPaginatedCourses`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Application/Queries/GetPublicCoursesQuery.php#L33-L40)
  - View đích: [`resources/js/Pages/Documents/Index.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx)
- **Bằng chứng mã nguồn & Thực tế hoạt động:**
  - Tại `CourseController@index`:
    ```php
    $courses = $this->coursesQuery->getPaginatedCourses($filters);
    $documents = $this->coursesQuery->getPaginatedDocuments($filters);

    return Inertia::render('Documents/Index', [
        'faculties' => $faculties,
        'courses' => $courses,
        'documents' => $documents,
        'filters' => $filters,
    ]);
    ```
    Backend đã tính toán và truyền prop `courses` sang Inertia.
  - **LỖI NGHIỆM TRỌNG TRÊN FRONTEND:**
    Kiểm tra component [`resources/js/Pages/Documents/Index.jsx:16-20`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx#L16-L20):
    ```jsx
    export default function Index({
        documents = { data: [] },
        filters = {},
        course = null,
    })
    ```
    Component này **hoàn toàn không khai báo nhận và không hề render biến `courses`**!
    Trang này chỉ render duy nhất danh sách thẻ `DocumentCard` ([`Documents/Index.jsx:210-212`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx#L210-L212)).
  - **HỆ QUẢ:** Khi sinh viên vào route `/mon-hoc` ("Kho môn học"), họ **KHÔNG THỂ tìm kiếm môn học hay xem danh sách môn học**. Họ chỉ thấy danh sách tài liệu. Mọi dữ liệu tìm kiếm môn học ở backend đều bị Frontend nuốt mất (discarded).
  - Ngoài ra, ô input tìm kiếm trên `Documents/Index.jsx:131` ghi placeholder: *"Tìm kiếm theo tiêu đề tài liệu, tên môn, mã học phần..."*. Tuy nhiên tại [`DocumentRepository.php:16-19`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Infrastructure/Persistence/DocumentRepository.php#L16-L19):
    ```php
    ->when(!empty($filters['search']), function ($query) use ($filters): void {
        $search = $filters['search'];
        $query->where('tieu_de', 'like', "%{$search}%");
    })
    ```
    Hệ thống **chỉ tìm theo `tieu_de` của tài liệu**, hoàn toàn không `orWhereHas('monHoc')` theo `ten_mon_hoc` hoặc `ma_hoc_phan`. Tìm kiếm gõ mã môn như `COS101` trên thanh search này sẽ trả về 0 kết quả nếu tiêu đề file không chứa chuỗi `COS101`!
- **Kết luận:** `BROKEN / MISMATCH`.

---

#### A.3. Trang Chi Tiết Môn Học & Hệ Thống Tabs Chức Năng
- **Chuỗi thực thi:**
  - Route: `GET /mon-hoc/{course_code}` (`courses.show`) trong [`app/Modules/AcademicCatalog/routes.php:22`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/routes.php#L22)
  - Controller: [`CourseController@show`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php#L34-L51)
  - View đích: [`resources/js/Pages/Documents/Index.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx)
- **Bằng chứng mã nguồn & Thực tế hoạt động:**
  - Tuyến đường `courses.show` tái sử dụng giao diện `Documents/Index.jsx` và truyền vào prop `course` (đối tượng Model `MonHoc`).
  - Giao diện `Documents/Index.jsx` chỉ render một banner nhỏ hiển thị `course.ten_mon_hoc` và badge `course.ma_hoc_phan` ([`Documents/Index.jsx:91-100`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Documents/Index.jsx#L91-L100)).
  - **KHOẢNG TRỐNG CHỨC NĂNG:**
    1. **Không có trang chi tiết môn học đúng nghĩa:** Không hiển thị số tín chỉ (`so_tin_chi`), mô tả môn học (`mo_ta`), khoa chủ quản, giảng viên phụ trách hoặc đề cương môn.
    2. **Không có hệ sinh thái Tabs liên kết:** Yêu cầu nghiệp vụ cần có 3 Tabs chuyển đổi:
       - Tab 1: **Kho tài liệu môn học** (Tài liệu đã duyệt, đề thi, bài giảng).
       - Tab 2: **Đánh giá môn học** (Trải nghiệm học tập, điểm số độ khó/thực tiễn/áp lực).
       - Tab 3: **Yêu cầu tài liệu (Wishlist/Bounty)** của riêng môn học đó.
    3. Hiện tại trên `Documents/Index.jsx`, **không hề có thanh điều hướng Tab nào**. Muốn xem Đánh giá môn học, người dùng phải tự gõ thủ công URL `/mon-hoc/{code}/danh-gia` hoặc tìm đường vòng.
- **Kết luận:** `UI_ONLY / PARTIALLY_IMPLEMENTED`.

---

### NHÓM B: CÁC TÁC VỤ NHANH TRÊN DASHBOARD

Kiểm tra trực tiếp file [`resources/js/Pages/Dashboard.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Dashboard.jsx):

```
DASHBOARD INTERACTION AUDIT MATRIX
┌───────────────────────┬────────────────────────────┬────────────────────────────┬───────────────────────────────────────┐
│ Tên Thẻ / Thao Tác    │ Href / Route Đích          │ Trạng Thái Trang Đích      │ Kết Luận Kỹ Thuật                     │
├───────────────────────┼────────────────────────────┼────────────────────────────┼───────────────────────────────────────┤
│ 1. Tìm môn học        │ route("courses.index")     │ Render Documents/Index     │ ⚠️ SAI NỘI DUNG: Không hiện môn học,  │
│                       │ (GET /mon-hoc)             │ (HTTP 200)                 │ chỉ hiện danh sách tài liệu.          │
├───────────────────────┼────────────────────────────┼────────────────────────────┼───────────────────────────────────────┤
│ 2. Săn thưởng         │ route("document-requests.  │ Render Wishlist/Index      │ ✅ HOẠT ĐỘNG TỐT: Lấy đủ data từ DB,  │
│    tài liệu (Bounty)  │ index") (GET /yeu-cau-...) │ (HTTP 200)                 │ có lọc trạng thái, phân trang chuẩn.  │
├───────────────────────┼────────────────────────────┼────────────────────────────┼───────────────────────────────────────┤
│ 3. Duyệt danh sách    │ route("faculties.index")   │ Render Faculties/Index     │ ⚠️ THIẾU TƯƠNG TÁC: Vào được khoa     │
│    khoa               │ (GET /khoa)                │ (HTTP 200)                 │ nhưng nút "Xem môn" không truyền param│
├───────────────────────┼────────────────────────────┼────────────────────────────┼───────────────────────────────────────┤
│ 4. Đăng tài liệu      │ route("documents.create")  │ Render Documents/Create    │ ✅ HOẠT ĐỘNG: Mở form upload, lọc     │
│                       │ (GET /tai-lieu/dang-tai)   │ (HTTP 200)                 │ khoa/môn, hash file SHA-256.          │
└───────────────────────┴────────────────────────────┴────────────────────────────┴───────────────────────────────────────┘
```

#### B.1. Lỗi Hiển Thị Trống Vô Điều Kiện trên Khối "Tài liệu học tập mới"
- **Vị trí file:** [`resources/js/Pages/Dashboard.jsx:348-432`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Dashboard.jsx#L348-L432)
- **Bằng chứng mã nguồn:**
  ```jsx
  <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      {recentDocuments.map((doc) => {
          // Render các thẻ tài liệu...
      })}
      
      {/* KHỐI EMPTY STATE NẰM NGOÀI ĐIỀU KIỆN! */}
      <div className="col-span-full rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
          <FileText size={32} className="mx-auto text-slate-400" />
          <p className="mt-3 font-semibold text-slate-700">Chưa có tài liệu học tập</p>
          <p className="mt-1 text-sm text-slate-500">Các tài liệu đã được kiểm duyệt sẽ hiển thị tại đây.</p>
      </div>
  </div>
  ```
- **Hành vi lỗi:** Khối báo trống `"Chưa có tài liệu học tập"` **luôn luôn hiển thị** ngay bên dưới danh sách tài liệu mới, kể cả khi database có 3 hoặc 10 tài liệu được map ra! Thiếu mệnh đề kiểm tra điều kiện `{recentDocuments.length === 0 ? (...) : (...)}`.
- **Thẻ tài liệu thiếu liên kết:** Các thẻ tài liệu con trong `recentDocuments.map` là thẻ `<article>` không có thẻ `<Link>` hoặc sự kiện `onClick` để chuyển hướng tới `documents.show`. Nút icon Download ([`Dashboard.jsx:409-415`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Dashboard.jsx#L409-L415)) là thẻ `<span>` trơ, không bấm được.

#### B.2. Khả Năng Tương Thích Giữa Student & Alumni
- Thẻ thông tin cá nhân ([`Dashboard.jsx:204-213`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Dashboard.jsx#L204-L213)) fix cứng nhãn `"Hồ sơ sinh viên"` và `"Trường Đại học An Giang"`. Khi tài khoản Cựu sinh viên (`CUU_SINH_VIEN`) đăng nhập, giao diện vẫn gọi họ là sinh viên thay vì hiển thị danh xưng cựu sinh viên.
- Thống kê `stats.totalCourses` ([`DashboardController.php:23`](file:///d:/CNWEB/AGU-Academic-Hub/app/Http/Controllers/DashboardController.php#L23)) truy vấn theo `$user->ma_khoa`. Nếu cựu sinh viên đăng ký mà chưa gán `ma_khoa`, số liệu hiển thị mặc định là `0`.

---

### NHÓM C: QUY TRÌNH ĐĂNG TẢI TÀI LIỆU (DOCUMENT UPLOAD PIPELINE)

Kiểm tra luồng 13 bước theo chuỗi:

| Bước | Hạng Mục Kiểm Tra | Tệp & Phương Thức Thực Hiện | Trạng Thái | Đánh Giá Kỹ Thuật Thực Tế |
| :---: | :--- | :--- | :---: | :--- |
| **C.01** | Truy cập trang đăng tải | `DocumentController@create` $\to$ `Documents/Create.jsx` | `PASS` | Mở trang độc lập, giao diện chuyên nghiệp, phản hồi mượt mà. |
| **C.02** | Tải danh sách Khoa | `Khoa::with('monHocs')->get()` | `PASS` | Nhận đủ mảng `faculties` từ backend qua Inertia props. |
| **C.03** | Chọn Chuyên ngành | `Documents/Create.jsx` | ❌ `FAIL` | **CHƯA CÓ GIAO DIỆN CHỌN NGÀNH:** Form nhảy thẳng từ Khoa sang Môn học, không có dropdown lọc chuyên ngành. |
| **C.04** | Chọn Môn học | `availableCourses` phụ thuộc vào `selectedFacultyId` | `PASS` | Tự động reset và load danh sách môn học trực thuộc khoa được chọn. |
| **C.05** | Nhập tiêu đề & mô tả | Input `tieu_de`, textarea `mo_ta` | `PASS` | Tự động điền tiêu đề từ tên file (bỏ extension), validate độ dài. |
| **C.06** | Chọn phân loại tài liệu | Dropdown `phan_loai` | `PASS` | Đủ 5 loại: `BAI_GIANG`, `DE_THI`, `BAI_TAP`, `DO_AN`, `KHAC`. |
| **C.07** | Chọn/Kéo thả file | Dropzone + Hidden File Input | `PASS` | Kéo thả hoạt động mượt, có chỉ báo trực quan khi hover tệp. |
| **C.08** | Validate định dạng & dung lượng | Client & Backend Validator | ⚠️ `WARN` | **LỖI:** Client chưa validate dung lượng trước khi hash (dễ đơ tab nếu file >200MB). Backend thiếu rule MIME type `mimes:pdf,doc...`. |
| **C.09** | Băm file SHA-256 tầng 1 | Web Crypto API `crypto.subtle.digest` | `PASS` | Tính toán mã băm nhị phân Hex SHA-256 ngay trên trình duyệt. |
| **C.10** | Gửi AJAX lọc trùng trước | `POST /tai-lieu/kiem-tra-trung-lap` | `PASS` | Trả về 409 nếu trùng file trong cùng môn học; vô hiệu hóa nút submit. |
| **C.11** | Submit Multipart/Form-data | `post(route('documents.store'), { forceFormData: true })` | `PASS` | Gửi dữ liệu đa phần an toàn, có CSRF token đính kèm. |
| **C.12** | Tiến trình upload (Progress) | `progress.percentage` từ Inertia useForm | `PASS` | Thanh tiến trình chạy mượt mà từ 0% đến 100%. |
| **C.13** | Lưu Storage & Database | `DocumentFileStorage@store` & `TaiLieu::create` | `PASS` | Lưu tệp vào ổ đĩa local thư mục `documents/`, mã băm và metadata ghi đầy đủ vào CSDL. |
| **C.14** | Chuyển hướng sau đăng tải | `redirect()->route('documents.show', $id)` | `PASS` | Chuyển sang `Documents/Show.jsx` kèm flash toast thành công. |
| **C.15** | Ngăn gửi form nhiều lần | `disabled={processing \|\| isHashing...}` | `PASS` | Nút đăng tải bị disable hoàn toàn khi tiến trình upload đang xử lý. |

---

## 3. ĐÁNH GIÁ CÁC MODULE LIÊN QUAN KHÁC (M04 - M10)

### M04: Đánh Giá Môn Học (Reviews)
- **Trạng thái:** `FULLY_IMPLEMENTED` về mặt chức năng đơn lẻ, nhưng `DISCONNECTED` về mặt luồng người dùng.
- **Hiện trạng:**
  - Trang [`Reviews/CourseReviews.jsx`](file:///d:/CNWEB\AGU-Academic-Hub\resources\js\Pages\Reviews\CourseReviews.jsx) có giao diện đẹp, bảng điểm 3 tiêu chí trực quan.
  - Form viết nhận xét có tích hợp kiểm tra từ khóa nhạy cảm thời gian thực bằng `ProfanityFilterService`.
  - Tương tác bình chọn "Hữu ích" hoạt động qua AJAX.
  - **Điểm yếu:** Người dùng không có cách nào từ trang môn học (`Documents/Index`) bấm sang trang đánh giá này vì thiếu hệ thống Tab.

### M05 & M06: Săn Thưởng Tài Liệu & Bounty
- **Trạng thái:** `FULLY_IMPLEMENTED`.
- **Hiện trạng:**
  - Bộ lọc danh sách yêu cầu (`trang_thai`, `ma_mon_hoc`, `phan_loai`, `sap_xep`) hoạt động đồng bộ với backend.
  - Đặt cọc điểm (trừ điểm khả dụng, tăng điểm tạm giữ) chuẩn xác qua DB transaction.
  - Modal đáp ứng yêu cầu (`FulfillRequestModal`) đã tích hợp Document Picker tra cứu tài liệu cá nhân.
  - Pháo hoa `canvas-confetti` kích hoạt chuẩn khi chủ bài đăng nghiệm thu giải ngân điểm.

### M07: Ví Điểm Cá Nhân (Wallet)
- **Trạng thái:** `FULLY_IMPLEMENTED`.
- **Hiện trạng:**
  - Trang [`Wallet/Index.jsx`](file:///d:/CNWEB\AGU-Academic-Hub\resources\js\Pages\Wallet\Index.jsx) hiển thị chuẩn 4 chỉ số điểm từ bảng `vi_diem`.
  - Bảng lịch sử giao dịch đọc từ `giao_dich_diem`, có phân trang `Pagination` và lọc tab `ALL / REWARD / FROZEN`.

### M08: Hồ Sơ Cá Nhân (Profile)
- **Trạng thái:** `FULLY_IMPLEMENTED`.
- **Hiện trạng:**
  - Trang [`Profile/Edit.jsx`](file:///d:/CNWEB\AGU-Academic-Hub\resources\js\Pages\Profile\Edit.jsx) đã chuyển đổi hoàn toàn sang `StudentLayout`.
  - Có Thẻ thông tin học thuật (Readonly Academic Info) hiển thị MSSV, Khoa, Điểm uy tín, Điểm ví.

### M09: Thông Báo Hệ Thống (Notifications)
- **Trạng thái:** `FULLY_IMPLEMENTED`.
- **Hiện trạng:**
  - Dropdown [`NotificationDropdown.jsx`](file:///d:/CNWEB\AGU-Academic-Hub\resources\js\Components\NotificationDropdown.jsx) gắn trên Header, fetch API `/thong-bao`, đánh dấu đọc từng tin hoặc đọc tất cả trơn tru.

---

## 4. TỔNG KẾT DANH SÁCH LỖI & THIẾU SÓT THỰC TẾ

1. **Bug B01 (Catalog Mismatch):** Tuyến `courses.index` (`GET /mon-hoc`) trỏ vào `Documents/Index.jsx` và chỉ hiển thị tài liệu; danh sách môn học (`$courses`) bị bỏ qua hoàn toàn.
2. **Bug B02 (Search Scope Limitation):** `DocumentRepository::paginatePublicDocuments` chỉ tìm kiếm theo `tieu_de` tài liệu, không hỗ trợ tìm theo `ten_mon_hoc` hoặc `ma_hoc_phan`.
3. **Bug B03 (Missing Navigation Tabs):** Không có thanh Tabs chuyển đổi giữa "Tài liệu môn", "Đánh giá môn học" và "Yêu cầu Wishlist" trên màn hình chi tiết môn học.
4. **Bug B04 (Dashboard Broken Empty State):** Khối thông báo "Chưa có tài liệu học tập" trên Dashboard nằm ngoài điều kiện kiểm tra, luôn hiển thị đè dưới danh sách tài liệu thực tế.
5. **Bug B05 (Dashboard Unclickable Cards):** Các thẻ tài liệu mới trên Dashboard không có đường dẫn tới `documents.show`; icon tải về không có sự kiện click.
6. **Bug B06 (Faculty Card Missing Param):** Nút "Xem môn học" trên thẻ Khoa trỏ về `courses.index` chung chung, không kèm query parameter `ma_khoa`.
7. **Bug B07 (Missing Major Hierarchy):** Chưa có giao diện để sinh viên duyệt danh sách Chuyên ngành thuộc Khoa, cả trên trang Khoa lẫn form Đăng tải tài liệu.
8. **Bug B08 (Upload Security & Validation Gap):** Backend `DocumentController@store` thiếu validate MIME types cho tệp upload; `handleFileChange` ở client thiếu chặn kích thước tệp trước khi tính toán SHA-256.

