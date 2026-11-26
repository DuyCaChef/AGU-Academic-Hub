# KẾ HOẠCH & PHẠM VI KHÓA – PHASE 3B1: COURSE DETAIL - DOCUMENT LISTING INTEGRATION

## 1. Mục tiêu & Bối cảnh

Mở rộng trang chi tiết môn học [`resources/js/Pages/Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx) (thuộc Phase 3A) để tích hợp khu vực Kho tài liệu học tập của chính môn học đó.

Người dùng thuộc hai phân hệ Sinh viên (`SINH_VIEN`) và Cựu sinh viên (`CUU_SINH_VIEN`) (cùng khách vãng lai tra cứu) có thể:
1. Xem danh sách tài liệu đã được duyệt (`DA_DUYET`) thuộc đúng môn học đang xem.
2. Nắm bắt các thông tin học thuật và siêu dữ liệu: Tiêu đề, phân loại, phiên bản, định dạng, dung lượng, người đăng, ngày đăng, lượt tải, lượt thích.
3. Lọc và tìm kiếm tài liệu theo từ khóa, danh mục phân loại, và thứ tự sắp xếp.
4. Mở trang chi tiết tài liệu ([`documents.show`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/routes.php)).
5. Xem trước tài liệu PDF trực tuyến ([`documents.preview`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/routes.php)).
6. Tải tài liệu về máy ([`documents.download`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/routes.php)).
7. Bình chọn hữu ích (Upvote) thông qua AJAX ([`documents.upvote`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/routes.php)).

---

## 2. Kết quả khảo sát Codebase thực tế

1. **Khóa định danh môn học**:
   - `ma_mon_hoc` (khóa chính số nguyên trong bảng `mon_hoc`, dùng làm khóa ngoại `ma_mon_hoc` trong bảng `tai_lieu`).
   - `ma_hoc_phan` (mã học phần chuẩn, vd: `COS101`, dùng làm route parameter cho `courses.show`: `GET /mon-hoc/{course_code}`).
2. **Quan hệ dữ liệu**:
   - `MonHoc::hasMany(TaiLieu::class, 'ma_mon_hoc', 'ma_mon_hoc')`.
   - `TaiLieu::belongsTo(MonHoc::class, 'ma_mon_hoc', 'ma_mon_hoc')`.
   - `TaiLieu::belongsTo(NguoiDung::class, 'ma_nguoi_dang', 'ma_nguoi_dung')`.
3. **Truy vấn & Phân quyền bảo mật**:
   - `DocumentRepository::paginatePublicDocuments`: Chỉ truy vấn tài liệu có `trang_thai = 'DA_DUYET'` và không bị xóa mềm (`ngay_xoa_tam IS NULL`).
   - Không làm rò rỉ tài liệu chưa duyệt (`CHO_DUYET`, `TU_CHOI`) hoặc tài liệu bị ẩn/xóa.
   - Lọc chính xác theo `ma_mon_hoc`, ngăn chặn tuyệt đối việc lẫn lộn tài liệu giữa các môn học khác nhau.
4. **Data Contract thực tế**:
   - `course`: Đối tượng môn học kèm `khoa` và `chuyen_nganhs`.
   - `documents`: Đối tượng phân trang `LengthAwarePaginator` gồm:
     - `data`: Danh sách tài liệu (`ma_tai_lieu`, `tieu_de`, `phan_loai`, `phien_ban`, `dinh_dang_tep`, `dung_luong_kb`, `luot_tai`, `luot_thich`, `trang_thai`, `created_at`, `nguoi_dang`, `mon_hoc`).
     - `total`: Tổng số tài liệu.
     - `current_page`, `last_page`, `links`: Phân trang.
   - `filters`: Tham số tìm kiếm hiện tại (`search`, `category`, `sort`).

---

## 3. Ranh giới & Tiêu chí khóa (Scope Lock)

### Được phép triển khai:
- Cập nhật [`CourseController::show`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php) để gọi `GetPublicCoursesQuery::getPaginatedDocuments` với `course_id => $course->ma_mon_hoc` và trả thêm prop `documents`, `filters`.
- Nâng cấp component [`DocumentCard.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Components/domain/DocumentCard.jsx) để hỗ trợ đầy đủ các thao tác: Chi tiết, Xem trước (Preview PDF), Tải về (Download), Upvote, hiển thị Người đăng và Ngày đăng.
- Cập nhật [`Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx) để tích hợp khu vực Kho tài liệu với Toolbar tìm kiếm/lọc, đếm số lượng tài liệu, lưới danh sách tài liệu, Empty state khi chưa có tài liệu, Loading/Error state và Pagination.
- Viết bộ Feature Test [`tests/Feature/Phase3b1DocumentListingTest.php`](file:///d:/CNWEB/AGU-Academic-Hub/tests/Feature/Phase3b1DocumentListingTest.php).

### Nghiêm cấm ngoài phạm vi (Out of Scope):
- Không triển khai tab Course Reviews (đánh giá môn học).
- Không triển khai tab Wishlist / Bounty.
- Không sửa đổi quy trình Upload hay form tải tài liệu.
- Không sửa đổi cơ chế ví điểm hoặc thanh toán.
- Không tạo migration hoặc sửa đổi database schema.
- Không thay đổi layout hoặc refactor các module khác ngoài phạm vi.

---

## 4. Bảng Tiêu chí Nghiệm thu (Acceptance Criteria)

| Mã AC | Tiêu chí |
| :--- | :--- |
| **AC-01** | Trang chi tiết môn học `Courses/Show` vẫn hoạt động ổn định sau khi bổ sung Kho tài liệu. |
| **AC-02** | Chỉ hiển thị tài liệu thuộc đúng môn học đang truy cập (`course_id = $course->ma_mon_hoc`). |
| **AC-03** | Không hiển thị tài liệu của các môn học khác. |
| **AC-04** | Hiển thị đúng metadata theo Data Contract: Tiêu đề, phân loại, người đăng, ngày đăng, lượt tải, lượt thích. |
| **AC-05** | Môn học không có tài liệu hiển thị Empty State rõ ràng, trực quan. |
| **AC-06** | Có trạng thái Loading khi lọc/tìm kiếm và Error State khi phát sinh lỗi. |
| **AC-07** | Phân trang (Pagination) hoạt động chính xác trên route môn học hiện tại. |
| **AC-08** | Mở đúng trang chi tiết tài liệu (`documents.show`). |
| **AC-09** | Chức năng Xem trước PDF (`documents.preview`) hoạt động theo quyền hạn hệ thống. |
| **AC-10** | Chức năng Tải về (`documents.download`) hoạt động theo quyền hạn hệ thống. |
| **AC-11** | Chức năng Upvote bình chọn hoạt động mượt mà qua AJAX. |
| **AC-12** | Sinh viên (`SINH_VIEN`) và Cựu sinh viên (`CUU_SINH_VIEN`) truy cập tài liệu đúng chính sách. |
| **AC-13** | Không làm lộ tài liệu chưa được phép công khai (tài liệu chờ duyệt hoặc bị xóa tạm). |
| **AC-14** | Không ảnh hưởng đến Dashboard, Document Index, Document Show và Upload. |
| **AC-15** | Giao diện hiển thị chuẩn xác, responsive trên cả Mobile và Desktop. |

