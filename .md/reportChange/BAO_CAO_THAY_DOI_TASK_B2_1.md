# BÁO CÁO CHI TIẾT CÁC THAY ĐỔI VÀ KHẮC PHỤC HỆ THỐNG (TASK B2.1)

**Dự án:** AGU-Academic-Hub  
**Phân hệ:** Xác thực danh tính & KYC (Identity and Access Management - IAM)  
**Nhiệm vụ:** Task B2.1 — Cài đặt và chuẩn hóa `KycController.php`, bảo mật Private Storage và cơ chế Signed URL  
**Ngày thực hiện:** 22/10/2026  

---

## 1. TỔNG QUAN YÊU CẦU & BỐI CẢNH NGHIỆP VỤ

Theo định hướng kiến trúc và phân tách nghiệp vụ mới từ Nhà trường/Hệ thống:

1. **Luồng 1 — Sinh viên đang học (Active Student):**
   - Nhà trường cung cấp bảng dữ liệu thô (`danh_sach_sinh_vien_raw`) chứa thông tin sinh viên: MSSV, email sinh viên do trường cấp, mã khoa và mật khẩu mặc định được mã hóa.
   - Khi sinh viên đăng nhập lần đầu, sinh viên nhập tài khoản (MSSV/Email) và mật khẩu được cấp.
   - Sau khi đăng nhập thành công lần đầu, tài khoản ở trạng thái chưa xác thực (`CHUA_XAC_THUC`) và **bắt buộc phải đổi mật khẩu** trước khi được phép truy cập bất kỳ tính năng nào khác của hệ thống.
   - Khi đổi mật khẩu xong, tài khoản được kích hoạt thành `DANG_HOAT_DONG`.

2. **Luồng 2 — Cựu sinh viên (Alumni KYC):**
   - Cựu sinh viên không có tài khoản trường cấp sẵn, phải tự nộp hồ sơ đăng ký kèm minh chứng (MSSV khóa cũ, năm tốt nghiệp, ảnh chụp Thẻ sinh viên hoặc Bằng tốt nghiệp / Giấy chứng nhận tốt nghiệp).
   - Ảnh minh chứng chứa thông tin cá nhân (PII), bắt buộc phải lưu trữ tại thư mục bảo mật **`storage/app/private/kyc`** (tuyệt đối không để ở thư mục public).
   - Kiểm duyệt viên / Quản trị viên kiểm tra hồ sơ và xem ảnh minh chứng thông qua **Signed URL** (URL có chữ ký bảo mật kèm thời hạn hết hạn 15 phút).
   - Khi kiểm duyệt viên phê duyệt (`approve`), tài khoản cựu sinh viên chuyển sang `DANG_HOAT_DONG` và có thể đăng nhập bình thường.

---

## 2. TỔNG HỢP CÁC FILE ĐÃ TẠO MỚI VÀ CHỈNH SỬA

| STT | Tệp tin (Path) | Thao tác | Mô tả tóm tắt |
| :---: | :--- | :---: | :--- |
| 1 | `app/Http/Controllers/KycController.php` | **Tạo mới / Viết lại hoàn chỉnh** | Controller trung tâm điều phối KYC: upload ảnh bảo mật SHA-256, cấp Signed URL 15 phút, duyệt/từ chối hồ sơ trong Transaction. |
| 2 | `app/Http/Requests/KycCuuSinhVienRequest.php` | **Chỉnh sửa & Chuẩn hóa** | Form Request xác thực thông tin đăng ký cựu SV. Sửa lỗi method `message()` → `messages()`, bổ sung rule kiểm tra MIME & dung lượng ảnh (max 5MB). |
| 3 | `app/Models/YeuCauXacThuc.php` | **Chỉnh sửa** | Thêm trường `loai_tep` vào mảng `$fillable` để lưu trữ MIME type của file minh chứng. |
| 4 | `database/migrations/2026_10_22_000001_add_loai_tep_to_yeu_cau_xac_thuc_table.php` | **Tạo mới** | Migration thêm cột `loai_tep VARCHAR(100) NULLABLE` vào bảng `yeu_cau_xac_thuc`. |
| 5 | `database/migrations/2026_10_22_000002_create_danh_sach_sinh_vien_raw_table.php` | **Tạo mới** | Migration tạo bảng `danh_sach_sinh_vien_raw` chứa dữ liệu thô phục vụ xác thực sinh viên đang học. |
| 6 | `app/Models/DanhSachSinhVienRaw.php` | **Tạo mới** | Eloquent Model cho bảng dữ liệu thô sinh viên kèm các phương thức tra cứu (`findByMssv`, `daKichHoat`, `markAsActivated`). |
| 7 | `app/Http/Middleware/EnsurePasswordChanged.php` | **Tạo mới** | Middleware bảo vệ: ép buộc sinh viên đang học phải đổi mật khẩu lần đầu trước khi vào hệ thống. |
| 8 | `routes/kyc.php` | **Tạo mới** | Tách riêng các routes cho quy trình KYC: Form nộp hồ sơ cựu sinh viên (Guest) và hàng đợi kiểm duyệt admin (Auth + Signed). |
| 9 | `routes/web.php` | **Chỉnh sửa** | Khai báo `require __DIR__ . '/kyc.php';` để kích hoạt bộ routes KYC. |
| 10 | `routes/community.php` | **Chỉnh sửa** | Chuẩn hóa các route KYC nội bộ trỏ chính xác về các action của `KycController`. |

---

## 3. CHI TIẾT TỪNG THAY ĐỔI

### 3.1. `app/Http/Controllers/KycController.php`
- **Khắc phục 19 lỗi logic & cú pháp từ bản phân tích trước:**
  - Loại bỏ các lỗi syntax như thiếu dấu `$` ở tên biến (`anh_minh_chung` → `$file`), lỗi chính tả biến (`$user` thay vì `$users`), lỗi typo tên method (`storeAlumiRegistration` → `storeAlumniRegistration`).
  - Sửa lỗi sử dụng Enum sai giá trị: thay thế các chuỗi hardcode và giá trị không tồn tại bằng Enum chuẩn của dự án:
    - `VaiTroNguoiDung::CUU_SINH_VIEN` (không phải `ALUMNI`).
    - `TrangThaiNguoiDung::CHUA_XAC_THUC` (không phải `PENDING`).
    - `TrangThaiNguoiDung::DANG_HOAT_DONG` (không phải `ACTIVE`).
    - `TrangThaiXacThuc::CHO_DUYET`, `TrangThaiXacThuc::DA_DUYET`, `TrangThaiXacThuc::TU_CHOI`.
  - Sửa lỗi sai tên cột khóa chính: Khóa chính của bảng `yeu_cau_xac_thuc` là `ma_yeu_cau_xac_thuc`, trước đây gọi sai thành `ma_xac_thuc`.
  - Loại bỏ logic tạo ví điểm thủ công: Trong `NguoiDung::booted()`, hệ thống đã tự động lắng nghe sự kiện `created` để khởi tạo `ViDiem`. Việc tạo thủ công trong Controller trước đó gây lỗi Integrity Constraint Violation (Unique constraint).
- **Triển khai Private Storage & An toàn PII:**
  - Đổi tên tệp bằng hash SHA-256 kết hợp chuỗi ngẫu nhiên (`Str::random(16)`) để chống tấn công lộ danh tính và ghi đè tệp.
  - Tệp được lưu trực tiếp vào thư mục private `storage/app/private/kyc/` bằng disk `local`.
- **Cơ chế Signed URL:**
  - Sinh Signed URL thông qua `URL::temporarySignedRoute('kyc.preview', now()->addMinutes(15), [...])`.
  - Phương thức `preview()` kiểm tra chữ ký thông qua middleware `signed` hoặc `$request->hasValidSignature()`.
  - Phục vụ tệp bằng `response()->file()` kèm theo đúng Header MIME type (`$yeuCau->loai_tep`).

### 3.2. `app/Http/Requests/KycCuuSinhVienRequest.php`
- **Khắc phục lỗi định danh method của Laravel:**
  - Đổi tên phương thức `public function message()` thành `public function messages()`. Trước đó Laravel bỏ qua toàn bộ thông báo tiếng Việt tùy biến vì sai tên quy ước.
- **Quy tắc kiểm thực (Validation Rules):**
  - `ho_ten`: Chuỗi ký tự, tối đa 255.
  - `email`: Định dạng email, duy nhất trong bảng `nguoi_dung`.
  - `mssv`: Bắt buộc, chuỗi ký tự.
  - `nam_tot_nghiep`: Số nguyên, từ năm 1999 đến năm hiện tại (`date('Y')`).
  - `ma_khoa`: Tồn tại trong bảng `khoa`.
  - `mat_khau`: Bắt buộc, xác nhận lại (`confirmed`), thỏa mãn rule mặc định của Laravel.
  - `loai_minh_chung`: Nằm trong tập hợp `['BANG_TOT_NGHIEP', 'GIAY_CHUNG_NHAN', 'THE_SINH_VIEN']`.
  - `anh_minh_chung`: File ảnh (`image`), định dạng `jpeg,png,jpg,webp`, dung lượng tối đa 5120 KB (5MB).

### 3.3. `app/Models/YeuCauXacThuc.php` & Migration `2026_10_22_000001`
- Thêm cột `loai_tep VARCHAR(100) NULLABLE` vào cấu trúc bảng `yeu_cau_xac_thuc`.
- Cập nhật `$fillable` trong model `YeuCauXacThuc` để cho phép ghi dữ liệu `loai_tep` từ request.

### 3.4. Cấu trúc dữ liệu sinh viên thô: `DanhSachSinhVienRaw.php` & Migration `2026_10_22_000002`
- Tạo bảng `danh_sach_sinh_vien_raw` với các thuộc tính:
  - `ma_ban_ghi`: Khóa chính tự tăng.
  - `mssv`: Mã số sinh viên chính thức (Unique, có index).
  - `ho_ten`: Họ tên sinh viên.
  - `email_sv`: Email trường cấp (`@student.agu.edu.vn` hoặc `@agu.edu.vn`).
  - `mat_khau_mac_dinh`: Mật khẩu mặc định khởi tạo sẵn (dạng bcrypt hash).
  - `ma_khoa`: Mã khoa/bộ môn.
  - `da_kich_hoat`: Trạng thái đã kích hoạt đổi mật khẩu lần đầu hay chưa (`default false`, có index).
  - `kich_hoat_luc`: Thời điểm kích hoạt tài khoản.
- Model `DanhSachSinhVienRaw`:
  - Ẩn trường `mat_khau_mac_dinh` trong `$hidden`.
  - Cung cấp các helper methods: `findByMssv($mssv)`, `daKichHoat(): bool`, `markAsActivated(): bool`.

### 3.5. `app/Http/Middleware/EnsurePasswordChanged.php`
- Kiểm tra điều kiện:
  - Người dùng đã đăng nhập.
  - Vai trò là `SINH_VIEN`.
  - Trạng thái tài khoản là `CHUA_XAC_THUC`.
- Nếu thỏa mãn cả 3 điều kiện:
  - Cho phép đi qua nếu request trỏ tới các routes: `password.change`, `password.change.update`, `logout`.
  - Mọi request khác đều bị chuyển hướng về route `password.change` kèm thông báo cảnh báo yêu cầu đổi mật khẩu.

### 3.6. `routes/kyc.php` & Cập nhật `routes/web.php`
- Cung cấp đầy đủ 6 routes đáp ứng chuẩn RESTful và bảo mật:
  1. `GET /alumni/register` (`alumni.register`) - Form đăng ký cựu sinh viên (Guest).
  2. `POST /alumni/register` (`alumni.register.store`) - Xử lý nộp hồ sơ & ảnh (Guest).
  3. `GET /admin/kyc` (`kyc.queue`) - Danh sách hồ sơ KYC chờ duyệt (Auth).
  4. `GET /admin/kyc/{id}/preview` (`kyc.preview`) - Xem ảnh minh chứng (Auth + Signed URL).
  5. `POST /admin/kyc/{id}/approve` (`kyc.approve`) - Phê duyệt hồ sơ (Auth).
  6. `POST /admin/kyc/{id}/reject` (`kyc.reject`) - Từ chối hồ sơ kèm lý do (Auth).

---

## 4. TỔNG HỢP 19 LỖI ĐÃ ĐƯỢC KHẮC PHỤC TRIỆT ĐỂ

| STT | Vị trí lỗi trước đây | Chi tiết lỗi | Cách thức khắc phục |
| :---: | :--- | :--- | :--- |
| 1 | `KycController@storeAlumniRegistration` | Gọi sai tên method từ routes (`storeAlumiRegistration`) | Sửa lại đúng chính tả `storeAlumniRegistration`. |
| 2 | `KycController` | Sử dụng `$users` (số nhiều không tồn tại) khi tạo User | Đổi thành biến đơn `$user = NguoiDung::create(...)`. |
| 3 | `KycController` | Thiếu dấu `$` ở biến file (`anh_minh_chung->storeAs`) | Sửa thành `$file->storeAs(...)`. |
| 4 | `KycController` | Sử dụng enum không tồn tại `VaiTroNguoiDung::ALUMNI` | Đổi thành `VaiTroNguoiDung::CUU_SINH_VIEN`. |
| 5 | `KycController` | Sử dụng enum không tồn tại `TrangThaiNguoiDung::PENDING` | Đổi thành `TrangThaiNguoiDung::CHUA_XAC_THUC`. |
| 6 | `KycController` | Sử dụng enum không tồn tại `TrangThaiNguoiDung::ACTIVE` | Đổi thành `TrangThaiNguoiDung::DANG_HOAT_DONG`. |
| 7 | `KycController` | Sử dụng hardcode string `'PENDING'` ở trạng thái xác thực | Đổi thành `TrangThaiXacThuc::CHO_DUYET`. |
| 8 | `KycController` | Sử dụng hardcode string `'APPROVED'` ở trạng thái duyệt | Đổi thành `TrangThaiXacThuc::DA_DUYET`. |
| 9 | `KycController` | Sử dụng hardcode string `'REJECTED'` ở trạng thái từ chối | Đổi thành `TrangThaiXacThuc::TU_CHOI`. |
| 10 | `KycController` | Sử dụng enum không tồn tại `LoaiXacThuc::ALUMNI` | Đổi thành `LoaiXacThuc::CUU_SINH_VIEN`. |
| 11 | `KycController@preview` | Sử dụng tên cột khóa chính sai (`ma_xac_thuc`) | Sửa thành `ma_yeu_cau_xac_thuc` chuẩn theo schema CSDL. |
| 12 | `KycController@approve` | Tạo thủ công `ViDiem::create` gây lỗi trùng lặp | Xóa bỏ vì `NguoiDung::booted()` đã tự khởi tạo `viDiem`. |
| 13 | `KycController` | Thiếu xử lý Database Transaction khi tạo tài khoản & hồ sơ | Bọc toàn bộ logic trong `DB::transaction(...)`. |
| 14 | `KycController@preview` | Cấp URL trực tiếp không kiểm tra chữ ký bảo mật | Bắt buộc middleware `signed` và kiểm tra chữ ký hợp lệ. |
| 15 | `KycController@preview` | Trả về chuỗi đường dẫn thay vì stream tệp ảnh | Dùng `response()->file(...)` kèm header `Content-Type`. |
| 16 | `KycCuuSinhVienRequest` | Tên hàm `public function message()` không theo chuẩn Laravel | Đổi thành `public function messages()`. |
| 17 | `KycCuuSinhVienRequest` | Chưa giới hạn dung lượng ảnh (dễ bị tấn công cạn kiệt bộ nhớ) | Thêm rule `max:5120` (5MB). |
| 18 | `YeuCauXacThuc` Model | Thiếu khai báo `loai_tep` trong mảng `$fillable` | Đã bổ sung `loai_tep` vào `$fillable`. |
| 19 | Hệ thống Routes | Thiếu tệp routes chuyên biệt cho KYC dẫn tới xung đột prefix | Tạo `routes/kyc.php` và import vào `routes/web.php`. |

---

## 5. HƯỚNG DẪN CÁC BƯỚC TRIỂN KHAI TIẾP THEO

1. **Chạy Migration CSDL (khi hệ quản trị MySQL hoạt động):**
   ```bash
   php artisan migrate
   ```
2. **Kích hoạt Middleware `EnsurePasswordChanged`:**
   Mở tệp `bootstrap/app.php` và đăng ký middleware vào nhóm `web` hoặc gán alias:
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->alias([
           'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
       ]);
   })
   ```
3. **Liên kết luồng đăng nhập Sinh viên đang học:**
   Trong `AuthenticatedSessionController`, bổ sung logic tra cứu bảng `danh_sach_sinh_vien_raw` khi sinh viên đăng nhập lần đầu bằng MSSV và mật khẩu mặc định.
4. **Cài đặt màn hình đổi mật khẩu lần đầu:**
   Tạo Controller và route cho `password.change` và `password.change.update` phục vụ việc đổi mật khẩu bắt buộc cho sinh viên.
