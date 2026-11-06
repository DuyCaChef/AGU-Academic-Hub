# PHASE 3: SHARED UI & CONSISTENCY (ĐỒNG BỘ GIAO DIỆN VÀ TRẢI NGHIỆM)
## AGU Academic Hub — Implementation Phase 3 Specification

---

| Thông Tin Phase | Chi Tiết |
| :--- | :--- |
| **Giai Đoạn** | Phase 3: Shared UI & Consistency |
| **Mục Tiêu** | Đồng bộ hóa 100% các màn hình về chuẩn `StudentLayout`, sửa triệt để các lỗi typo prop trong cây môn học và tách riêng màn hình danh mục Khoa |
| **Số Lượng Task** | 4 Tasks (**TSK-301**, **TSK-302**, **TSK-303**, **TSK-304**) |
| **Tổng Thời Gian Ước Lượng** | **12.0 Giờ** |
| **Trọng Tâm** | Tính nhất quán giao diện (UI Consistency), Layout Hierarchy, Nhận diện thương hiệu AGU |

---

### TASK TSK-301: Tách Riêng Giao Diện Danh Mục Khoa (`Faculties/Index.jsx`)

1. **Task ID:** `TSK-301`
2. **Module:** M02 (Academic Catalog)
3. **Mục tiêu:** Tạo mới view `resources/js/Pages/Faculties/Index.jsx` và cập nhật `FacultyController@index` để hiển thị danh mục Khoa đào tạo độc lập, chấm dứt việc tái sử dụng view `Dashboard` gây lỗi thiếu dữ liệu thống kê.
4. **Hiện trạng:**
   - Tuyến `GET /khoa` (`faculties.index`) gọi `FacultyController@index`:
     ```php
     return Inertia::render('Dashboard', [
         'faculties' => $faculties,
     ]);
     ```
   - Trang `Dashboard` cần nhận `stats` và `recentDocuments`. Khi người dùng truy cập từ menu "Danh sách khoa", các ô thống kê bị 0 giả tạo và bảng tài liệu rỗng.
5. **File cần đọc:**
   - `app/Modules/AcademicCatalog/Presentation/Http/Controllers/FacultyController.php`
   - `app/Modules/AcademicCatalog/Application/Queries/GetPublicFacultiesQuery.php`
   - `resources/js/Layouts/StudentLayout.jsx`
6. **File cần sửa hoặc tạo:**
   - Sửa: `app/Modules/AcademicCatalog/Presentation/Http/Controllers/FacultyController.php`
   - Tạo mới: `resources/js/Pages/Faculties/Index.jsx`
7. **Backend dependency:** `GetPublicFacultiesQuery`.
8. **Data Contract:**
   - *Props nhận từ Backend (`Faculties/Index`):*
     ```json
     {
       "faculties": [
         {
           "ma_khoa": 1,
           "ten_khoa": "Khoa Công nghệ thông tin",
           "ky_hieu_khoa": "CNTT",
           "mon_hocs_count": 45,
           "chuyen_nganhs_count": 3
         }
       ]
     }
     ```
9. **Các bước triển khai:**
   - *Bước 1:* Sửa `FacultyController@index`:
     ```php
     public function index(Request $request): Response
     {
         $faculties = $this->facultiesQuery->execute($request->user());
         return Inertia::render('Faculties/Index', [
             'faculties' => $faculties,
         ]);
     }
     ```
   - *Bước 2:* Tạo file `resources/js/Pages/Faculties/Index.jsx` bọc trong `StudentLayout`.
   - *Bước 3:* Thiết kế giao diện dạng Lưới thẻ (Grid Cards):
     - Mỗi thẻ đại diện cho một Khoa: Icon tòa nhà / biểu trưng Khoa, Ký hiệu khoa, Tên đầy đủ của Khoa, số lượng bộ môn/chuyên ngành và số môn học.
     - Nút "Khám phá môn học" $\to$ liên kết điều hướng sang `/mon-hoc?ma_khoa={id}` để xem toàn bộ tài liệu thuộc Khoa đó.
10. **Acceptance Criteria (AC):**
    - [ ] Bấm vào menu "Danh sách khoa" trên Sidebar mở đúng trang `Faculties/Index.jsx`.
    - [ ] Không còn hiển thị sai lệch sang giao diện Dashboard.
    - [ ] Thẻ Khoa hiển thị đầy đủ thông tin và dẫn link chính xác tới kho tài liệu của Khoa.
11. **Test Cases:**
    - `TC-CTR-04`: Truy cập `/khoa` $\to$ hiển thị danh sách các Khoa của AGU, không có lỗi thiếu prop thống kê.
12. **Priority:** **`P2` (Medium)**
13. **Dependency Task:** Không có.
14. **Estimated Effort:** **3.5 Giờ**
15. **Risk & Mitigation:** Không có rủi ro kỹ thuật.

---

### TASK TSK-302: Đưa Trang Đánh Giá Môn Học (`CourseReviews`) Vào `StudentLayout`

1. **Task ID:** `TSK-302`
2. **Module:** M04 (Review)
3. **Mục tiêu:** Tái cấu trúc file `resources/js/Pages/Reviews/CourseReviews.jsx` sử dụng `StudentLayout`, loại bỏ thanh Header tự chế để khôi phục luồng điều hướng Sidebar và cây thư mục môn học.
4. **Hiện trạng:**
   - `CourseReviews.jsx` dòng 56 tự định nghĩa:
     `<div className="min-h-screen bg-[#f8fafc]...">` kèm một `<header className="bg-white border-b...">`.
   - Người dùng khi ở trang này bị mất hoàn toàn Sidebar và các công cụ điều hướng của Cổng sinh viên.
5. **File cần đọc:**
   - `resources/js/Pages/Reviews/CourseReviews.jsx`
   - `resources/js/Layouts/StudentLayout.jsx`
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Reviews/CourseReviews.jsx`
7. **Backend dependency:** `CourseReviewController@index` (không đổi).
8. **Data Contract:** Giữ nguyên các props `course`, `mon_hoc`, `stats`, `thong_ke`, `reviews`.
9. **Các bước triển khai:**
   - *Bước 1:* Import `StudentLayout` từ `@/Layouts/StudentLayout`.
   - *Bước 2:* Xóa bỏ thẻ `<header>` tự dựng và wrapper ngoài cùng trong `CourseReviews.jsx`.
   - *Bước 3:* Bọc toàn bộ nội dung trong:
     `<StudentLayout title={`Đánh giá môn học: ${courseCode} - ${courseName}`}>`
   - *Bước 4:* Điều chỉnh Breadcrumbs và tiêu đề trang cho ăn khớp với thiết kế của `StudentLayout`.
10. **Acceptance Criteria (AC):**
    - [ ] Màn hình đánh giá môn học hiển thị đầy đủ Header xanh AGU, Sidebar và cây môn học.
    - [ ] Giữ nguyên 100% chức năng xem review, viết review, chấm sao và vote hữu ích.
11. **Test Cases:**
    - `TC-REV-01`: Truy cập `/mon-hoc/{code}/danh-gia` $\to$ hiển thị trong `StudentLayout`.
12. **Priority:** **`P2` (Medium)**
13. **Dependency Task:** Không có.
14. **Estimated Effort:** **2.5 Giờ**
15. **Risk & Mitigation:** Không có rủi ro kỹ thuật.

---

### TASK TSK-303: Chuyển Đổi Trang Hồ Sơ (`Profile/Edit.jsx`) Sang `StudentLayout`

1. **Task ID:** `TSK-303`
2. **Module:** M08 (Profile)
3. **Mục tiêu:** Chuyển đổi trang `resources/js/Pages/Profile/Edit.jsx` từ giao diện Laravel Breeze tiếng Anh (`AuthenticatedLayout`) sang `StudentLayout`, bổ sung các trường thông tin học thuật chỉ đọc và gắn link điều hướng từ Header.
4. **Hiện trạng:**
   - `Profile/Edit.jsx` đang dùng `AuthenticatedLayout` (tiếng Anh, màu xám nhạt).
   - Nút "Hồ sơ cá nhân" trên menu Avatar ở Header (`StudentHeader.jsx`) chỉ mở modal pop-up xem tĩnh, không có nút nào dẫn sang trang chỉnh sửa `/ho-so`.
   - Form chỉ cho sửa tên và email, chưa hiển thị rõ MSSV, Khoa, Điểm uy tín.
5. **File cần đọc:**
   - `resources/js/Pages/Profile/Edit.jsx`
   - `resources/js/Components/layouts/StudentHeader.jsx`
   - `resources/js/Layouts/StudentLayout.jsx`
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Profile/Edit.jsx`
   - Sửa: `resources/js/Components/layouts/StudentHeader.jsx`
7. **Backend dependency:** `ProfileController@edit` và `@update`.
8. **Data Contract:**
   - Kết nối với Task `TSK-003` để đọc đúng `user.ho_ten`.
9. **Các bước triển khai:**
   - *Bước 1:* Trong `Profile/Edit.jsx`, thay thế `AuthenticatedLayout` bằng `StudentLayout`.
   - *Bước 2:* Việt hóa toàn bộ tiêu đề: "Thông tin cá nhân", "Cập nhật mật khẩu", "Xóa tài khoản".
   - *Bước 3:* Bổ sung khối Thẻ Học Thuật Chỉ Đọc (Read-only Academic Card):
     - Mã số sinh viên: `{user.mssv || 'N/A'}` (Có khóa bảo vệ, không cho sửa).
     - Khoa đào tạo: `{user.khoa?.ten_khoa || 'Chưa cập nhật'}` (Readonly).
     - Điểm uy tín học thuật: `{user.diem_uy_tin} điểm` (Readonly).
   - *Bước 4:* Trong `StudentHeader.jsx`, tại modal xem hồ sơ và menu avatar, thêm nút bấm:
     `<Link href={route('profile.edit')}>Chỉnh sửa hồ sơ & Bảo mật &rarr;</Link>`.
10. **Acceptance Criteria (AC):**
    - [ ] Trang `/ho-so` mang nhận diện thương hiệu AGU, tích hợp Sidebar chuẩn.
    - [ ] Sinh viên từ Header có thể click mở trang chỉnh sửa hồ sơ dễ dàng.
    - [ ] Các trường nhạy cảm (MSSV, Khoa, Điểm uy tín) hiển thị rõ ràng và được khóa chỉ đọc.
11. **Test Cases:**
    - `TC-CTR-01`: Đăng nhập, mở menu avatar $\to$ click Chỉnh sửa hồ sơ $\to$ chuyển đến trang `/ho-so`.
12. **Priority:** **`P2` (Medium)**
13. **Dependency Task:** `TSK-003`.
14. **Estimated Effort:** **3.0 Giờ**
15. **Risk & Mitigation:** Không có rủi ro kỹ thuật.

---

### TASK TSK-304: Chuẩn Hóa Prop `faculties` & Hiển Thị Cấp Chuyên Ngành

1. **Task ID:** `TSK-304`
2. **Module:** M02 / M10 (Academic Catalog / Sidebar)
3. **Mục tiêu:** Sửa dứt điểm lỗi chính tả `faculities` thành `faculties` trên `StudentSidebar.jsx` và `FacultyCourseTree.jsx`, đồng thời cải tiến cây danh mục hỗ trợ hiển thị phân cấp Khoa $\to$ Bộ môn/Chuyên ngành $\to$ Môn học.
4. **Hiện trạng:**
   - `StudentSidebar.jsx` dòng 104 truyền `<FacultyCourseTree faculities={faculties} />`.
   - `FacultyCourseTree.jsx` dòng 5 khai báo `export default function FacultyCourseTree({ faculities = [] })`.
   - Cây danh mục hiện chỉ duyệt trực tiếp `khoa -> mon_hocs`, bỏ qua bảng `chuyen_nganh`.
5. **File cần đọc:**
   - `resources/js/Components/layouts/StudentSidebar.jsx`
   - `resources/js/Components/layouts/FacultyCourseTree.jsx`
   - `app/Modules/AcademicCatalog/Infrastructure/Persistence/AcademicCatalogRepository.php:60`
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Components/layouts/StudentSidebar.jsx`
   - Sửa: `resources/js/Components/layouts/FacultyCourseTree.jsx`
7. **Backend dependency:** `GetPublicFacultiesQuery`.
8. **Data Contract:** Chuẩn hóa prop nhận vào thành `faculties = []`.
9. **Các bước triển khai:**
   - *Bước 1:* Trong `FacultyCourseTree.jsx`, sửa khai báo props:
     `export default function FacultyCourseTree({ faculties = [], faculities = [] })`
     (hỗ trợ fallback tương thích ngược: `const facultyList = faculties.length > 0 ? faculties : faculities;`).
   - *Bước 2:* Trong `StudentSidebar.jsx`, sửa lệnh truyền prop:
     `<FacultyCourseTree faculties={faculties} />`.
   - *Bước 3:* Nâng cấp giao diện hiển thị cây thư mục:
     Nếu môn học có gắn chuyên ngành, gom nhóm theo chuyên ngành hoặc hiển thị badge mã chuyên ngành bên cạnh mã học phần.
10. **Acceptance Criteria (AC):**
    - [ ] Tên prop được chuẩn hóa thành `faculties` chuẩn tiếng Anh kỹ thuật.
    - [ ] Cây danh mục Sidebar hiển thị mượt mà, không phát sinh warning trên Console.
11. **Test Cases:**
    - `TC-CTR-03`: Kiểm tra Sidebar trên tất cả các trang $\to$ cây môn học mở/đóng ổn định.
12. **Priority:** **`P2` (Medium)**
13. **Dependency Task:** Không có.
14. **Estimated Effort:** **3.0 Giờ**
15. **Risk & Mitigation:** Không có rủi ro kỹ thuật.

