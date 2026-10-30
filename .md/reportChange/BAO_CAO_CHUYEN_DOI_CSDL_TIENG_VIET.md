# BÁO CÁO CHI TIẾT CHUẨN HÓA VÀ CHUYỂN ĐỔI HỆ THỐNG CƠ SỞ DỮ LIỆU, MODELS & ENUMS SANG TIẾNG VIỆT
**Dự án:** AGU Academic Hub  
**Nhánh:** `fix/language-vi-database-iam-reviews-bounty`  
**Ngày thực hiện:** 19/10/2026  
**Người thực hiện:** Đội ngũ phát triển AGU Academic Hub  

---

## I. TỔNG QUAN VÀ BỐI CẢNH THỰC HIỆN

### 1. Bối cảnh dự án
Trước thời điểm chuẩn hóa, dự án **AGU Academic Hub** xảy ra tình trạng phân mảnh và xung đột ngôn ngữ sau khi sáp nhập hai nhánh phát triển:
- **Nhánh Frontend & Core Migrations (`feature/set-up-migration`):** Sử dụng danh pháp tiếng Việt cho các bảng cốt lõi như `khoa`, `mon_hoc`, `tai_lieu`, `bao_cao_vi_pham` và các trường dữ liệu tương ứng (`ma_mon_hoc`, `ma_hoc_phan`, `tieu_de`, `ngay_xoa_tam`...). Giao diện React/Inertia cũng đã được lập trình bám sát các trường tiếng Việt này.
- **Nhánh Phân hệ IAM, Reviews & Bounty (`feat/database-iam-reviews-bounty`):** Sử dụng danh pháp tiếng Anh theo thiết kế tài liệu schema ban đầu như `faculties`, `courses`, `users`, `course_reviews`, `document_requests`, `point_wallets`...

Tình trạng này dẫn đến:
1. **Trùng lặp thực thể:** Tồn tại song song cả `Khoa` lẫn `Faculty`, `MonHoc` lẫn `Course`, `TaiLieu` lẫn `Document`.
2. **Lỗi ràng buộc khóa ngoại (Foreign Key Constraints):** File migration `tai_lieu` (tiếng Việt) tham chiếu đến khóa ngoại `nguoi_dung(ma_nguoi_dung)`, nhưng hệ thống khi đó chỉ có bảng `users(id)` của nhánh tiếng Anh, dẫn đến lỗi khi khởi chạy `php artisan migrate`.
3. **Bất đồng bộ trong mã nguồn:** Giao diện sinh viên (Student Portal) sử dụng trường tiếng Việt (`khoa.mon_hocs`, `mon.ma_hoc_phan`, `document.luot_tai`), trong khi tầng Backend Models và Seeders lại dùng trường tiếng Anh (`course_code`, `downloads_count`).

### 2. Mục tiêu chuẩn hóa
Thực hiện chỉ đạo kỹ thuật: **"Thống nhất cách đặt tên file của dự án này là tiếng Việt, chuyển đổi toàn bộ tên file trong các folder Enums, Models và các trường của database sang tiếng Việt chuẩn (Ví dụ: `course_code` -> `ma_hoc_phan` / `ma_mon_hoc`)"**.

---

## II. CHI TIẾT TỪNG PHÂN HỆ VÀ CÔNG DỤNG CỦA CÁC FILE

---

### 1. Thư mục Enums (`app/Enums/`)

Các file Enum dạng PHP 8.2+ Backed Enums có nhiệm vụ đảm bảo tính toàn vẹn kiểu dữ liệu (Type-Safety), ngăn chặn sai sót nhập liệu chuỗi tự do, đồng thời hỗ trợ xuất nhãn hiển thị tiếng Việt (Human-readable Label) lên giao diện người dùng.

| Tên File Enum | Công dụng & Tính năng chi tiết | Giá trị cấu thành | Đóng góp cho hệ thống |
| :--- | :--- | :--- | :--- |
| `VaiTroNguoiDung.php` | Quản lý vai trò và phân quyền (RBAC) cho người dùng trong toàn bộ hệ sinh thái AGU. | `SINH_VIEN`<br>`CUU_SINH_VIEN`<br>`KIEM_DUYET_VIEN`<br>`QUAN_TRI_VIEN` | Phục vụ trực tiếp cho Middleware xác thực phân quyền `role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN` trên các route quản trị và upload tài liệu. Cung cấp hàm `label()` hiển thị đúng vai trò sinh viên/kiểm duyệt. |
| `TrangThaiNguoiDung.php` | Quản lý trạng thái hoạt động của tài khoản người dùng trong quy trình bảo mật. | `CHUA_XAC_THUC`<br>`DANG_HOAT_DONG`<br>`TAM_KHOA` | Ngăn chặn các tài khoản bị khóa (`TAM_KHOA`) hoặc chưa xác thực email tham gia đăng bài, tải tài liệu hoặc đánh giá môn học; hỗ trợ Mod/Admin quản trị an toàn hệ thống. |
| `TrangThaiDanhGiaMonHoc.php` | Quản lý vòng đời kiểm duyệt của các bài viết review, đánh giá học phần. | `CHO_DUYET`<br>`DA_DUYET`<br>`TAM_AN`<br>`TU_CHOI` | Đảm bảo tính khách quan và văn hóa học đường. Các bài đánh giá có nội dung nhạy cảm sẽ được chuyển trạng thái `CHO_DUYET` hoặc `TAM_AN` để Ban kiểm duyệt phê duyệt trước khi công khai. |
| `TrangThaiYeuCauTaiLieu.php` | Kiểm soát tiến trình xử lý của các phiếu yêu cầu tìm tài liệu (Wishlist/Bounty). | `MO`<br>`DA_HOAN_THANH`<br>`DONG`<br>`DA_HUY` | Giữ trạng thái đóng băng điểm thưởng (Bounty Points) khi yêu cầu đang `MO`, giải phóng điểm khi `DA_HOAN_THANH`, hoặc hoàn trả điểm về ví cho sinh viên khi bị `DA_HUY`. |
| `LoaiGiaoDichDiem.php` | Phân loại bản chất của các dòng giao dịch phát sinh trong ví điểm sinh viên. | `CONG_DIEM`<br>`TAM_GIU`<br>`GIAI_PHONG`<br>`SU_DUNG`<br>`HOAN_TRA` | Đóng vai trò là sổ cái kế toán điểm thưởng, phân định rõ ràng giữa điểm thực nhận khi chia sẻ tài liệu tốt (`CONG_DIEM`), điểm bị khóa tạm thời để tạo yêu cầu (`TAM_GIU`), và điểm chi tiêu (`SU_DUNG`). |
| `LoaiXacThuc.php` | Phân định đối tượng trong hồ sơ nộp minh chứng KYC. | `SINH_VIEN`<br>`CUU_SINH_VIEN` | Phân loại hồ sơ kiểm duyệt: Sinh viên nộp ảnh Thẻ sinh viên / Giấy xác nhận; Cựu sinh viên nộp Bằng tốt nghiệp. |
| `TrangThaiXacThuc.php` | Quản lý tiến trình duyệt yêu cầu cấp tích xanh KYC cho tài khoản sinh viên. | `CHO_DUYET`<br>`DA_DUYET`<br>`TU_CHOI` | Đảm bảo tính xác thực của cộng đồng AGU Academic Hub, chỉ những người có thẻ sinh viên thực sự mới được duyệt gắn huy hiệu uy tín. |

---

### 2. Thư mục Models (`app/Models/`)

Hệ thống Eloquent Model đóng vai trò tầng giao tiếp dữ liệu nghiệp vụ (ORM), thiết lập quan hệ liên kết dữ liệu, xử lý tự động (Model Booted Events) và bảo mật dữ liệu.

| Tên Model | Bảng CSDL | Khóa chính | Công dụng, Tính năng & Mối quan hệ chính |
| :--- | :--- | :--- | :--- |
| `NguoiDung.php` | `nguoi_dung` | `ma_nguoi_dung` | **Trung tâm phân hệ định danh (IAM):**<br>- Kế thừa `Authenticatable`, sử dụng `SoftDeletes` (`ngay_xoa_tam`).<br>- Override phương thức bảo mật `getAuthPasswordName()` trả về cột `mat_khau`.<br>- Thiết lập Model Hook (`created`): Tự động cấp ví điểm `ViDiem` với **100 điểm thưởng khởi tạo** ngay khi đăng ký thành công.<br>- Các Accessor `name` và `id` đảm bảo tương thích ngược 100% với các component Laravel Breeze/Inertia React.<br>- **Quan hệ:** Thuộc về `Khoa`, sở hữu 1 `ViDiem`, có nhiều `DanhGiaMonHoc`, `YeuCauTaiLieu`, `TaiLieuDaDang`, `YeuCauXacThuc`. |
| `Khoa.php` | `khoa` | `ma_khoa` | **Danh mục Khoa/Bộ môn:**<br>- Quản lý các khoa chuyên môn tại AGU (VD: Khoa CNTT, Khoa Kinh tế - QTKD, Khoa Sư phạm...).<br>- **Quan hệ:** Có nhiều `ChuyenNganh`, có nhiều `MonHoc`, có nhiều `NguoiDung`. |
| `ChuyenNganh.php` | `chuyen_nganh` | `ma_chuyen_nganh` | **Danh mục Chuyên ngành đào tạo:**<br>- Quản lý mã ngành (DTH, DPM, DMK...) và tên ngành học chi tiết.<br>- **Quan hệ:** Thuộc về `Khoa`, quan hệ nhiều-nhiều với `MonHoc` qua bảng trung gian `mon_hoc_chuyen_nganh`. |
| `MonHoc.php` | `mon_hoc` | `ma_mon_hoc` | **Danh mục Môn học / Học phần:**<br>- Quản lý mã học phần chuẩn AGU (COS101, KTE201...), số tín chỉ, mô tả đề cương môn học.<br>- **Quan hệ:** Thuộc về `Khoa`, liên kết nhiều `ChuyenNganh`, chứa danh sách các `TaiLieu` học tập, các bài `DanhGiaMonHoc` và các `YeuCauTaiLieu`. |
| `TaiLieu.php` | `tai_lieu` | `ma_tai_lieu` | **Phân hệ Lưu trữ & Chia sẻ tài liệu:**<br>- Quản lý metadata tài liệu, đường dẫn file, định dạng, dung lượng KB.<br>- Quản lý chuỗi phiên bản cha-con (`ma_tai_lieu_goc`) giúp nâng cấp tài liệu v1.0 lên v1.1, v2.0.<br>- Lưu mã băm `ma_bam_file` (SHA-256) phục vụ thuật toán lọc trùng lặp nhị phân 100% ngay từ lúc upload.<br>- Theo dõi tương tác: `luot_tai` (lượt tải), `luot_thich` (upvotes), trạng thái sở hữu cộng đồng.<br>- **Quan hệ:** Thuộc `MonHoc`, người đăng `NguoiDung`, chứa danh sách `BaoCaoViPham`. |
| `BaoCaoViPham.php` | `bao_cao_vi_pham` | `ma_bao_cao` | **Phân hệ Báo cáo vi phạm tài liệu:**<br>- Tiếp nhận khiếu nại của sinh viên về tài liệu: sai môn, trùng lặp, chứa mã độc, vi phạm bản quyền.<br>- **Quan hệ:** Liên kết tài liệu bị báo cáo `TaiLieu`, người gửi báo cáo `NguoiDung`. |
| `DanhGiaMonHoc.php` | `danh_gia_mon_hoc` | `ma_danh_gia` | **Phân hệ Review & Trải nghiệm môn học:**<br>- Đánh giá 3 tiêu chí trực quan (Thang điểm 1-5 sao): `diem_do_kho`, `diem_thuc_tien`, `diem_khoi_luong`.<br>- Hỗ trợ chế độ đánh giá ẩn danh (`an_danh = true`) giúp sinh viên thoải mái đóng góp ý kiến trung thực.<br>- Scope `scopeDaDuyet`: Lọc nhanh các bài đánh giá hợp lệ hiển thị công khai ra portal.<br>- **Quan hệ:** Thuộc về `MonHoc`, tác giả `NguoiDung`, chứa nhiều `BinhChonDanhGia` và `BaoCaoDanhGia`. |
| `BinhChonDanhGia.php` | `binh_chon_danh_gia` | `ma_binh_chon` | **Bình chọn độ hữu ích:**<br>- Cho phép sinh viên bấm "Hữu ích" đối với những review chất lượng, từ đó đẩy review hay lên đầu.<br>- **Quan hệ:** Thuộc về `DanhGiaMonHoc`, người bình chọn `NguoiDung`. |
| `BaoCaoDanhGia.php` | `bao_cao_danh_gia` | `ma_bao_cao` | **Kiểm duyệt bài đánh giá:**<br>- Cho phép báo cáo các review tiêu cực, ngôn từ kích động, bôi nhọ giảng viên hoặc spam quảng cáo.<br>- **Quan hệ:** Liên kết `DanhGiaMonHoc`, `NguoiBaoCao`, `NguoiXuLy` (Mod/Admin). |
| `ViDiem.php` | `vi_diem` | `ma_vi` | **Hệ thống Điểm thưởng (Gamification & Bounty):**<br>- Quản lý hai loại điểm số: `diem_kha_dung` (điểm có thể dùng) và `diem_tam_giu` (điểm đang bị đóng băng khi tạo Wishlist).<br>- Ràng buộc kiểm tra không âm, ngăn chặn rủi ro gian lận điểm.<br>- **Quan hệ:** Thuộc về 1 `NguoiDung`, sở hữu danh sách lịch sử `GiaoDichDiem`. |
| `GiaoDichDiem.php` | `giao_dich_diem` | `ma_giao_dich` | **Sổ cái Giao dịch Điểm:**<br>- Ghi lại chi tiết mọi biến động điểm cộng/trừ kèm biến động delta.<br>- Chứa trường `khoa_chong_trung` (Idempotency Key) chống lỗi trừ điểm lặp lại khi xảy ra sự cố mạng.<br>- **Quan hệ:** Thuộc về `ViDiem`. |
| `YeuCauTaiLieu.php` | `yeu_cau_tai_lieu` | `ma_yeu_cau` | **Phân hệ Wishlist tìm kiếm tài liệu:**<br>- Sinh viên tạo bài đăng cần tìm đề thi/bài giảng hiếm kèm số điểm thưởng `diem_thuong` (Bounty).<br>- Hệ thống tự động đóng băng điểm tương ứng trong ví người tạo.<br>- **Quan hệ:** Thuộc `MonHoc`, người tạo `NguoiDung`, tài liệu đáp ứng `TaiLieu`, danh sách các lượt nộp `DapUngYeuCauTaiLieu`. |
| `DapUngYeuCauTaiLieu.php` | `dap_ung_yeu_cau_tai_lieu` | `ma_dap_ung` | **Nộp tài liệu đáp ứng Wishlist:**<br>- Người dùng khác upload tài liệu để giải quyết yêu cầu và nhận điểm thưởng.<br>- **Quan hệ:** Liên kết `YeuCauTaiLieu`, tài liệu nộp `TaiLieu`, người nộp `NguoiDung`, người duyệt `NguoiDung`. |
| `YeuCauXacThuc.php` | `yeu_cau_xac_thuc` | `ma_yeu_cau_xac_thuc` | **Phân hệ Xác thực danh tính sinh viên (KYC):**<br>- Lưu trữ đường dẫn ảnh minh chứng thẻ sinh viên/bằng tốt nghiệp và thông tin khóa học.<br>- **Quan hệ:** Thuộc về `NguoiDung`, người xét duyệt hồ sơ `NguoiDung` (Mod/Admin). |

---

### 3. Thư mục Migrations (`database/migrations/`)

Hệ thống file migration đã được tái cấu trúc triệt để, đảm bảo thứ tự thời gian tạo bảng (timestamp) đi từ bảng cha (ít phụ thuộc) tới bảng con (nhiều phụ thuộc), giải quyết 100% lỗi ràng buộc khóa ngoại khi chạy `migrate:fresh`.

#### Trình tự Migration chuẩn:
1. `2026_10_13_072836_create_khoas_table.php` (`khoa`)
2. `2026_10_13_072837_create_nguoi_dungs_table.php` (`nguoi_dung`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`)
3. `2026_10_13_072840_create_chuyen_nganhs_table.php` (`chuyen_nganh`)
4. `2026_10_13_072843_create_mon_hocs_table.php` (`mon_hoc` & `mon_hoc_chuyen_nganh`)
5. `2026_10_13_072856_create_tai_lieus_table.php` (`tai_lieu`)
6. `2026_10_13_080039_create_bao_cao_vi_phams_table.php` (`bao_cao_vi_pham`)
7. `2026_10_14_000003_create_yeu_cau_xac_thucs_table.php` (`yeu_cau_xac_thuc`)
8. `2026_10_14_000007_create_danh_gia_mon_hocs_table.php` (`danh_gia_mon_hoc`, `binh_chon_danh_gia`, `tu_cam`)
9. `2026_10_14_000008_create_vi_diem_va_yeu_cau_tai_lieus_table.php` (`vi_diem`, `yeu_cau_tai_lieu`, `dap_ung_yeu_cau_tai_lieu`, `giao_dich_diem`)
10. `2026_10_14_000009_create_notifications_table.php` (`notifications`)
11. `2026_10_14_000010_create_personal_access_tokens_table.php` (`personal_access_tokens`)
12. `2026_10_14_000011_create_database_queue_tables.php` (`jobs`, `job_batches`, `failed_jobs`)
13. `2026_10_14_000012_create_bao_cao_danh_gias_table.php` (`bao_cao_danh_gia`)

---

## IV. BẢNG ĐỐI CHIẾU THAY ĐỔI DANH PHÁP (CSDL & CODE)

| Thành phần | Danh pháp cũ (Tiếng Anh) | Danh pháp mới (Tiếng Việt) | Ghi chú & Ý nghĩa nghiệp vụ |
| :--- | :--- | :--- | :--- |
| **Model** | `User.php` | `NguoiDung.php` | Model xác thực người dùng trung tâm |
| **Model** | `Faculty.php` | `Khoa.php` | Hợp nhất vào model Khoa tiếng Việt đã có |
| **Model** | `Course.php` | `MonHoc.php` | Hợp nhất vào model MonHoc tiếng Việt đã có |
| **Model** | `Major.php` | `ChuyenNganh.php` | Chuyên ngành đào tạo tại trường AGU |
| **Model** | `CourseReview.php` | `DanhGiaMonHoc.php` | Đánh giá môn học theo 3 tiêu chí |
| **Model** | `CourseReviewVote.php` | `BinhChonDanhGia.php` | Bình chọn review hữu ích |
| **Model** | `CourseReviewReport.php` | `BaoCaoDanhGia.php` | Báo cáo bài review vi phạm văn hóa |
| **Model** | `DocumentRequest.php` | `YeuCauTaiLieu.php` | Phiếu yêu cầu tìm kiếm tài liệu (Wishlist) |
| **Model** | `DocumentRequestFulfillment.php` | `DapUngYeuCauTaiLieu.php` | Đóng góp tài liệu giải quyết Wishlist |
| **Model** | `PointWallet.php` | `ViDiem.php` | Ví điểm thưởng sinh viên |
| **Model** | `PointTransaction.php` | `GiaoDichDiem.php` | Lịch sử giao dịch sổ cái ví điểm |
| **Model** | `VerificationRequest.php` | `YeuCauXacThuc.php` | Hồ sơ xin xác thực sinh viên/cựu sinh viên |
| **Enum** | `UserRole.php` | `VaiTroNguoiDung.php` | Vai trò: Sinh viên, Kiểm duyệt viên, Admin |
| **Enum** | `UserStatus.php` | `TrangThaiNguoiDung.php` | Trạng thái: Chờ duyệt, Đang hoạt động, Khóa |
| **Enum** | `CourseReviewStatus.php` | `TrangThaiDanhGiaMonHoc.php` | Trạng thái kiểm duyệt review |
| **Cột CSDL** | `course_code` | `ma_hoc_phan` / `ma_mon_hoc` | Mã môn học định danh (VD: COS101, KTE201) |
| **Cột CSDL** | `faculty_id` | `ma_khoa` | Khóa ngoại liên kết bảng khoa |
| **Cột CSDL** | `full_name` | `ho_ten` | Họ và tên sinh viên / cán bộ |
| **Cột CSDL** | `student_code` | `mssv` | Mã số sinh viên AGU (VD: DTH215001) |
| **Cột CSDL** | `password` | `mat_khau` | Mật khẩu tài khoản (Bcrypt hash) |
| **Cột CSDL** | `role` | `vai_tro` | Phân quyền người dùng |
| **Cột CSDL** | `status` | `trang_thai` | Trạng thái hồ sơ / thực thể |
| **Cột CSDL** | `reputation_score` | `diem_uy_tin` | Điểm uy tín cộng đồng |
| **Cột CSDL** | `avatar_path` | `anh_dai_dien` | Đường dẫn ảnh đại diện avatar |
| **Cột CSDL** | `graduation_year` | `nam_tot_nghiep` | Năm tốt nghiệp của cựu sinh viên |
| **Cột CSDL** | `bounty_points` | `diem_thuong` | Số điểm thưởng treo cho tài liệu |
| **Cột CSDL** | `available_points` | `diem_kha_dung` | Điểm ví có thể sử dụng |
| **Cột CSDL** | `held_points` | `diem_tam_giu` | Điểm ví bị phong tỏa tạo Bounty |
| **Cột CSDL** | `difficulty_score` | `diem_do_kho` | Điểm độ khó môn học (1-5 sao) |
| **Cột CSDL** | `practicality_score` | `diem_thuc_tien` | Điểm tính thực tiễn (1-5 sao) |
| **Cột CSDL** | `workload_score` | `diem_khoi_luong` | Điểm khối lượng bài tập (1-5 sao) |
| **Cột CSDL** | `deleted_at` | `ngay_xoa_tam` | Cột xóa mềm (SoftDeletes tiếng Việt) |

---

## V. CÁC ĐIỀU CHỈNH ĐỒNG BỘ ĐI KÈM TRONG HỆ THỐNG

1. **Cấu hình Xác thực Laravel (`config/auth.php`):**
   - Đã cập nhật provider mặc định:
     ```php
     'providers' => [
         'users' => [
             'driver' => 'eloquent',
             'model' => env('AUTH_MODEL', App\Models\NguoiDung::class),
         ],
     ],
     ```
   - Định nghĩa phương thức bảo mật trong model `NguoiDung`:
     ```php
     public function getAuthPasswordName(): string { return 'mat_khau'; }
     public function getAuthPassword(): string { return $this->mat_khau; }
     ```

2. **Cập nhật Controllers & Form Requests xác thực:**
   - `RegisteredUserController.php`: Cập nhật lưu dữ liệu người dùng đăng ký mới vào bảng `nguoi_dung` với các trường `ho_ten`, `email`, `mat_khau`.
   - `ProfileUpdateRequest.php`: Cập nhật quy tắc unique email loại trừ khóa chính `ma_nguoi_dung` của `NguoiDung`.

3. **Cập nhật hệ thống Factories & Seeders:**
   - Tạo mới các Factory tiếng Việt: `NguoiDungFactory.php`, `DanhGiaMonHocFactory.php`, `YeuCauTaiLieuFactory.php`.
   - Bổ sung định nghĩa sinh dữ liệu chuẩn xác cho `KhoaFactory.php` và `MonHocFactory.php`.
   - Tái cấu trúc `CommunitySeeder.php`: Khởi tạo sẵn tài khoản Admin, Kiểm duyệt viên (Ban Học Tập), Sinh viên kiểm thử Khánh Duy (`DTH215001`), Khoa CNTT, Môn học Lập trình căn bản (`COS101`), review đánh giá và Wishlist mẫu hoàn toàn bằng trường tiếng Việt.
