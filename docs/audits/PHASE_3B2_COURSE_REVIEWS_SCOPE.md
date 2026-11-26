# KẾ HOẠCH & PHẠM VI KHÓA – PHASE 3B2: COURSE REVIEWS & COMMUNITY VOTING

## 1. Mục tiêu & Bối cảnh

Mở rộng trang chi tiết môn học [`resources/js/Pages/Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx) (kế thừa từ Phase 3A và Phase 3B1) để tích hợp phân hệ **Đánh giá môn học (Course Reviews)** và nâng cấp cơ chế tương tác hiện tại thành **Hệ thống Bình chọn Cộng đồng hai chiều (Community Voting: Upvote / Downvote)**.

Người dùng thuộc hai nhóm `SINH_VIEN` và `CUU_SINH_VIEN` có thể:
1. Xem các đánh giá đã duyệt của môn học đang truy cập cùng thống kê 3 tiêu chí: Độ khó, Thực tiễn, Khối lượng học tập (thang điểm 1-5 sao).
2. Tạo bài đánh giá môn học mới ngay trong trang chi tiết môn học (kèm bộ lọc từ cấm và tùy chọn đăng ẩn danh).
3. Bình chọn **Upvote (+1)** hoặc **Downvote (-1)** cho từng bài đánh giá của người khác.
4. Hủy bình chọn khi nhấp lại cùng một nút đã chọn.
5. Chuyển đổi hướng bình chọn trực tiếp (Upvote sang Downvote hoặc ngược lại).
6. Quan sát Điểm cộng đồng (`Review Score = Upvotes - Downvotes`) được cập nhật tức thời và chuẩn xác.

---

## 2. Nguyên tắc Bất biến (Core Invariants)

1. **Cô lập Điểm số Tuyệt đối (Strict Point Isolation)**:
   - **Review Score**: Điểm cộng đồng riêng của bài đánh giá = `Tổng Upvotes - Tổng Downvotes`.
   - **User Reputation (`diem_uy_tin`)**: Điểm uy tín tài khoản người dùng trên bảng `nguoi_dung`. Nghiêm cấm mọi hành vi cộng/trừ điểm uy tín khi bài đánh giá nhận Upvote/Downvote.
   - **Wallet Points (`so_du_diem`)**: Điểm thưởng trong ví `vi_diem` / bảng kê `giao_dich_diem`. Tuyệt đối không thay đổi số dư ví của tác giả bài viết hay người bình chọn.
2. **Bảo vệ Danh tính & Quyền Ẩn danh 2 Lớp (2-Layer Anonymity)**:
   - Không xuất `ma_nguoi_dung`, email, MSSV của tác giả bài đánh giá hay người vote trong JSON response công khai.
   - Khi bài viết bật cờ `an_danh = true`, danh tính được hiển thị là `"Sinh viên ẩn danh"`, không avatar và không có bất kỳ thông tin nào có thể suy đoán danh tính.
   - Chỉ tác giả bài viết mới thấy ghi chú nhận diện bài của chính mình và có quyền chỉnh sửa/xóa.
3. **Chống Tự Bình chọn & Giới hạn 1 Vote/User/Review**:
   - Tác giả bài đánh giá không được phép bình chọn cho bài của chính mình.
   - Mỗi người dùng chỉ có tối đa 1 bản ghi vote tại một thời điểm trên một bài đánh giá.
   - Database constraint `UNIQUE(ma_danh_gia, ma_nguoi_dung)` bảo đảm tính toàn vẹn dữ liệu.

---

## 3. Khảo sát Codebase & Data Contract Hiện tại

### 3.1. Tuyến đường (Routes) Hiện tại

| Phương thức | URI | Tên Route | Controller & Action | Mục đích | Trạng thái |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/mon-hoc/{ma_hoc_phan}/danh-gia` | `courses.reviews.index` | `CourseReviewController@index` | Trang/API danh sách đánh giá của môn học | Đã có |
| `POST` | `/mon-hoc/{ma_hoc_phan}/danh-gia` | `courses.reviews.store` | `CourseReviewController@store` | Đăng bài đánh giá mới | Đã có |
| `PUT` | `/danh-gia/{ma_danh_gia}` | `reviews.update` | `CourseReviewController@update` | Cập nhật bài đánh giá | Đã có |
| `DELETE` | `/danh-gia/{ma_danh_gia}` | `reviews.destroy` | `CourseReviewController@destroy` | Xóa mềm bài đánh giá | Đã có |
| `POST` | `/danh-gia/{ma_danh_gia}/huu-ich` | `reviews.helpful` | `CourseReviewController@binhChonHuuIch` | Toggle bình chọn hữu ích (1 chiều) | Đã có |
| `POST` | `/danh-gia/{ma_danh_gia}/bao-cao` | `reviews.report` | `CourseReviewController@baoCaoViPham` | Báo cáo vi phạm | Đã có |

### 3.2. Cấu trúc Database Hiện tại

1. **Bảng `danh_gia_mon_hoc`**:
   - `ma_danh_gia` (PK, bigint unsigned, auto-increment)
   - `ma_mon_hoc` (FK -> `mon_hoc.ma_mon_hoc`, restrict)
   - `ma_nguoi_dung` (FK -> `nguoi_dung.ma_nguoi_dung`, restrict)
   - `hoc_ky` (varchar 20, vd: `2025-1`)
   - `diem_do_kho` (unsigned tinyint, CHECK 1-5)
   - `diem_thuc_tien` (unsigned tinyint, CHECK 1-5)
   - `diem_khoi_luong` (unsigned tinyint, CHECK 1-5)
   - `noi_dung` (text, nullable)
   - `an_danh` (boolean, default true)
   - `trang_thai` (enum: `CHO_DUYET`, `DA_DUYET`, `TAM_AN`, `TU_CHOI`, default `DA_DUYET`)
   - `created_at`, `updated_at`, `ngay_xoa_tam` (soft deletes)
   - Unique Index: `(ma_mon_hoc, ma_nguoi_dung, hoc_ky)`

2. **Bảng `binh_chon_danh_gia` (Hiện tại)**:
   - `ma_binh_chon` (PK, bigint unsigned, auto-increment)
   - `ma_danh_gia` (FK -> `danh_gia_mon_hoc.ma_danh_gia`, cascade on delete)
   - `ma_nguoi_dung` (FK -> `nguoi_dung.ma_nguoi_dung`, cascade on delete)
   - `huu_ich` (boolean / tinyint(1), default true)
   - `created_at`, `updated_at`
   - Unique Index: `(ma_danh_gia, ma_nguoi_dung)`

---

## 4. Phân tích Khoảng cách Kỹ thuật & Đề xuất Migration

### 4.1. Vấn đề của Bảng `binh_chon_danh_gia` Hiện tại
- Cột `huu_ich` hiện đang khai báo kiểu `boolean` (trong MySQL là `TINYINT(1)`).
- Model Eloquent [`BinhChonDanhGia`](file:///d:/CNWEB/AGU-Academic-Hub/app/Models/BinhChonDanhGia.php) khai báo `$casts = ['huu_ich' => 'boolean']`.
- Khi cần bình chọn hai chiều Upvote (`+1`) và Downvote (`-1`):
  - Giá trị `-1` nếu lưu qua Eloquent cast `boolean` sẽ bị PHP ép thành `true` (`1`), làm mất bản chất giá trị số âm.
  - Cột `huu_ich` chỉ mang ngữ nghĩa một chiều "Hữu ích", không thể hiện rõ hai trạng thái trái ngược (Upvote và Downvote).

### 4.2. Phương án Kiến trúc Đề xuất (Cần Phê duyệt)

**Đề xuất: Tạo Migration tiến hóa bảng `binh_chon_danh_gia`**:
- Thêm cột `vote_type` (`tinyint NOT NULL DEFAULT 1 COMMENT '1 = Upvote, -1 = Downvote'`).
- Đồng bộ dữ liệu hiện có: Cập nhật `vote_type = 1` cho các dòng `huu_ich = 1`.
- Giữ lại cột hoặc accessor `huu_ich` để bảo toàn 100% tương thích ngược với các query cũ.
- Cập nhật model [`BinhChonDanhGia`](file:///d:/CNWEB/AGU-Academic-Hub/app/Models/BinhChonDanhGia.php) cast: `'vote_type' => 'integer'`.
- Giữ nguyên ràng buộc khóa chính, khóa ngoại cascade delete và `UNIQUE(ma_danh_gia, ma_nguoi_dung)`.

---

## 5. Ranh giới Triển khai (Scope Boundary)

### Được phép triển khai:
1. Tạo migration nâng cấp cột `vote_type` cho bảng `binh_chon_danh_gia` (sau khi được duyệt).
2. Nâng cấp [`CourseReviewRepository`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Infrastructure/Persistence/CourseReviewRepository.php):
   - Bổ sung hàm `castCommunityVote(int $reviewId, int $userId, int $voteType): array` hỗ trợ: Upvote (+1), Downvote (-1), Hủy vote (delete), Đổi hướng vote (update).
   - Truy vấn tính toán `upvotes_count`, `downvotes_count`, `review_score`, và `my_vote` (1, -1, hoặc null).
3. Cập nhật [`CourseReviewRuleEvaluator`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Domain/Services/CourseReviewRuleEvaluator.php) chặn tự vote.
4. Cập nhật [`CourseReviewResource`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Presentation/Http/Resources/CourseReviewResource.php) trả về: `review_score`, `upvotes_count`, `downvotes_count`, `my_vote`, `is_upvoted`, `is_downvoted`.
5. Bổ sung endpoint `POST /danh-gia/{ma_danh_gia}/vote` vào [`app/Modules/Reviews/routes.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/routes.php) (kèm giữ route cũ `huu-ich` làm alias tương thích ngược).
6. Tích hợp Tab "Đánh giá môn học" vào [`resources/js/Pages/Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx) kèm:
   - Thống kê 3 tiêu chí đánh giá (Độ khó, Thực tiễn, Khối lượng).
   - Component Community Voting (nút Upvote, Downvote, hiển thị Review Score).
   - Tích hợp Modal viết đánh giá môn học ([`CreateReviewModal.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Components/Reviews/CreateReviewModal.jsx)).
   - Chống spam / debounce click.
7. Cung cấp dữ liệu đánh giá từ [`CourseController::show`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php) hoặc tích hợp linh hoạt qua query.
8. Viết bộ Feature Test toàn diện [`tests/Feature/Phase3b2CourseReviewsTest.php`](file:///d:/CNWEB/AGU-Academic-Hub/tests/Feature/Phase3b2CourseReviewsTest.php) bao phủ AC-01 đến AC-36.

### Nghiêm cấm ngoài phạm vi (Out of Scope):
- Tuyệt đối không thay đổi `diem_uy_tin` của người dùng.
- Tuyệt đối không can thiệp vào `vi_diem` hay bảng kê `giao_dich_diem`.
- Không tự chuyển sang Phase 3B3 hoặc các module khác.
- Không sửa đổi logic Document Module (Phase 3B1).

---

## 6. Danh mục Tiêu chí Nghiệm thu (Acceptance Criteria)

| Mã AC | Nhóm | Tiêu chí Kiểm định |
| :--- | :--- | :--- |
| **AC-01** | UI | Trang chi tiết môn học hiển thị đầy đủ Tab Đánh giá bên cạnh Tab Tài liệu. |
| **AC-02** | UI | Hiển thị bảng chỉ số thống kê 3 tiêu chí: Độ khó, Thực tiễn, Khối lượng học tập. |
| **AC-03** | UI | Môn học chưa có đánh giá hiển thị Empty State thân thiện kèm nút viết đánh giá đầu tiên. |
| **AC-04** | Form | Mở được Modal tạo đánh giá với đầy đủ thang điểm 1-5 cho 3 tiêu chí và nội dung nhận xét. |
| **AC-05** | Form | Hỗ trợ checkbox lựa chọn đăng ẩn danh (`an_danh`). |
| **AC-06** | Form | Kiểm duyệt từ ngữ cấm (Profanity Filter) hoạt động chuẩn xác trước khi lưu đánh giá. |
| **AC-07** | Voting | Người dùng có thể bình chọn Upvote (+1) cho bài đánh giá của người khác. |
| **AC-08** | Voting | Người dùng có thể bình chọn Downvote (-1) cho bài đánh giá của người khác. |
| **AC-09** | Voting | Nhấp lại Upvote khi đang Upvote sẽ hủy bình chọn (trở về 0). |
| **AC-10** | Voting | Nhấp lại Downvote khi đang Downvote sẽ hủy bình chọn (trở về 0). |
| **AC-11** | Voting | Chuyển trực tiếp từ Upvote sang Downvote (Review Score giảm 2 điểm). |
| **AC-12** | Voting | Chuyển trực tiếp từ Downvote sang Upvote (Review Score tăng 2 điểm). |
| **AC-13** | Rule | Người dùng không thể tự bình chọn bài đánh giá của chính mình (báo lỗi/chặn tương tác). |
| **AC-14** | Rule | Người chưa đăng nhập không thể bình chọn (chuyển hướng đăng nhập). |
| **AC-15** | UI | Trạng thái nút Upvote/Downvote đổi màu rõ ràng khi người dùng đã vote. |
| **AC-16** | Concurrency | Chống double-click/spam request trong lúc đang xử lý vote. |
| **AC-17** | Formula | Điểm cộng đồng hiển thị chuẩn xác theo công thức: `Review Score = Upvotes - Downvotes`. |
| **AC-18** | Persistence | Trạng thái bình chọn được lưu bền vững vào CSDL và giữ nguyên khi tải lại trang. |
| **AC-19** | Privacy | Response công khai tuyệt đối không lộ `ma_nguoi_dung`, email, MSSV của tác giả. |
| **AC-20** | Privacy | Response công khai tuyệt đối không lộ danh tính người vote. |
| **AC-21** | Privacy | Đánh giá ẩn danh hiển thị tên "Sinh viên ẩn danh" và không avatar. |
| **AC-22** | Isolation | Bình chọn Upvote/Downvote không làm tăng hay giảm `diem_uy_tin` của tác giả. |
| **AC-23** | Isolation | Bình chọn Upvote/Downvote không làm thay đổi số dư ví `so_du_diem` hay tạo giao dịch điểm. |
| **AC-24** | Soft Delete | Xử lý chuẩn xác khi bài đánh giá bị xóa mềm (không tính vote, không hiển thị công khai). |
| **AC-25** | Data Integrity | Ràng buộc cơ sở dữ liệu ngăn chặn hoàn toàn việc một người dùng có 2 bản ghi vote trên cùng 1 review. |
| **AC-26** | Regression | Sinh viên (`SINH_VIEN`) và Cựu sinh viên (`CUU_SINH_VIEN`) thực hiện được mọi tác vụ theo phân quyền. |
| **AC-27** | Regression | Route cũ `reviews.helpful` vẫn hoạt động tương thích ngược. |
| **AC-28** | Regression | Tab Tài liệu môn học (Phase 3B1) hoạt động hoàn toàn bình thường, không bị ảnh hưởng. |
| **AC-29** | Performance | Truy vấn danh sách đánh giá tối ưu, không phát sinh N+1 query. |
| **AC-30** | Responsive | Giao diện hiển thị chuẩn xác, không vỡ khung trên cả thiết bị di động và máy tính bàn. |

