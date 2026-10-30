# Báo cáo nghiệp vụ và triển khai KYC cựu sinh viên

## 1. Mục đích

Tài liệu này mô tả đầy đủ luồng đăng ký, đối soát, duyệt/từ chối và thông báo trạng thái hồ sơ KYC dành cho **cựu sinh viên** của AGU Academic Hub.

Phạm vi chính:

- Cựu sinh viên đăng ký tài khoản ngoài danh sách sinh viên hiện hành.
- Admin/Moderator xử lý hàng đợi KYC.
- Bảo vệ ảnh minh chứng bằng private storage và Signed URL.
- Kích hoạt tài khoản sau khi hồ sơ được duyệt.
- Cấp ví điểm ban đầu.
- Hiển thị trạng thái chờ duyệt hoặc lý do từ chối trên màn hình đăng nhập.
- Không gửi email ở giai đoạn hiện tại.

> **Phân biệt nghiệp vụ:** Sinh viên đang học được xác thực bằng dữ liệu nạp trước trong `danh_sach_sinh_vien_raw` và mật khẩu mặc định. Sinh viên đang học không thuộc hàng đợi KYC cựu sinh viên.

## 2. Trạng thái và vai trò

### 2.1. Vai trò người dùng

| Vai trò | Quyền trong luồng này |
|---|---|
| `CUU_SINH_VIEN` | Đăng ký hồ sơ KYC ngoài danh sách sinh viên hiện hành; đăng nhập sau khi được duyệt |
| `SINH_VIEN` | Đăng nhập bằng dữ liệu nhà trường; không nộp KYC cựu sinh viên |
| `KIEM_DUYET_VIEN` | Xem, xem ảnh, duyệt hoặc từ chối hồ sơ cựu sinh viên |
| `QUAN_TRI_VIEN` | Có toàn bộ quyền xử lý KYC của Admin/Moderator |

### 2.2. Trạng thái tài khoản

- `CHUA_XAC_THUC`: tài khoản cựu sinh viên mới đăng ký, đang chờ xử lý.
- `DANG_HOAT_DONG`: hồ sơ đã được duyệt, tài khoản được phép đăng nhập bình thường.
- `TAM_KHOA`: tài khoản bị khóa theo nghiệp vụ quản trị.

### 2.3. Trạng thái hồ sơ KYC

- `CHO_DUYET`: hồ sơ mới gửi, đang nằm trong hàng đợi.
- `DA_DUYET`: Admin/Moderator đã phê duyệt.
- `TU_CHOI`: hồ sơ bị từ chối và có lý do xử lý.

## 3. Luồng đăng ký cựu sinh viên

### Bước 1: Mở biểu mẫu

Guest truy cập route:

```text
GET /cuu-sinh-vien/dang-ky
```

Route name:

```text
alumni.register
```

Controller tải danh sách Khoa để người dùng lựa chọn.

### Bước 2: Nhập dữ liệu

Biểu mẫu yêu cầu:

- Họ tên.
- Email cá nhân.
- MSSV cũ.
- Năm tốt nghiệp.
- Khoa.
- Mật khẩu và xác nhận mật khẩu.
- Loại minh chứng.
- Ảnh minh chứng.

Các loại minh chứng hiện hỗ trợ:

- `BANG_TOT_NGHIEP`
- `GIAY_CHUNG_NHAN`
- `THE_SINH_VIEN`

Ảnh chỉ nhận định dạng JPEG, PNG, JPG hoặc WEBP và tối đa 5 MB.

### Bước 3: Chuẩn hóa và kiểm tra

Trước khi validate:

- Email được trim và chuyển thành chữ thường.
- MSSV được trim và chuyển thành chữ hoa.

Hệ thống từ chối đăng ký nếu MSSV đã tồn tại trong:

```text
danh_sach_sinh_vien_raw
```

Thông báo:

> MSSV này thuộc danh sách sinh viên đang học. Sinh viên hiện hành không nộp hồ sơ KYC cựu sinh viên.

Điều kiện này ngăn sinh viên hiện hành đi vào luồng KYC thủ công.

## 4. Tạo tài khoản và lưu hồ sơ

Sau khi validate thành công, hệ thống chạy trong transaction:

1. Tạo người dùng trong `nguoi_dung`.
2. Gán:
   - `vai_tro = CUU_SINH_VIEN`
   - `trang_thai = CHUA_XAC_THUC`
   - `diem_uy_tin = 100`
3. Băm mật khẩu trước khi lưu.
4. Sinh tên file minh chứng ngẫu nhiên, không dùng tên file gốc có thể chứa PII.
5. Lưu ảnh vào private storage:

```text
private/kyc
```

6. Tạo bản ghi `yeu_cau_xac_thuc`:
   - Loại xác thực: `CUU_SINH_VIEN`.
   - Trạng thái: `CHO_DUYET`.
   - MSSV cũ.
   - Năm tốt nghiệp.
   - Loại minh chứng.
   - Đường dẫn file.
   - MIME type.

Nếu không lưu được file, transaction phát sinh lỗi và không hoàn tất quy trình tạo hồ sơ.

Sau khi gửi thành công, người dùng được chuyển về trang đăng nhập với thông báo:

> Đã gửi hồ sơ đăng ký cựu sinh viên thành công! Quản trị viên sẽ đối soát và kích hoạt tài khoản của bạn.

## 5. Hàng đợi KYC dành cho Admin/Moderator

### 5.1. Truy cập

Route danh sách:

```text
GET /quan-tri/duyet-kyc
```

Route name:

```text
admin.kyc.index
```

Chỉ Admin hoặc Moderator được truy cập. Các vai trò khác nhận HTTP 403.

### 5.2. Điều kiện lọc hàng đợi

Hệ thống chỉ đưa vào danh sách các hồ sơ thỏa mãn đồng thời:

```text
loai_xac_thuc = CUU_SINH_VIEN
trang_thai = CHO_DUYET
nguoi_dung.vai_tro = CUU_SINH_VIEN
MSSV không tồn tại trong danh_sach_sinh_vien_raw
```

Do đó:

- Hồ sơ sinh viên hiện hành không xuất hiện.
- Hồ sơ đã duyệt không xuất hiện lại.
- Hồ sơ đã từ chối không xuất hiện lại.
- Hồ sơ sai loại xác thực không xuất hiện.

### 5.3. Thông tin Admin/Moderator nhìn thấy

Mỗi dòng hồ sơ hiển thị:

- Họ tên.
- Email cá nhân.
- MSSV cũ.
- Năm tốt nghiệp.
- Khoa và ký hiệu Khoa.
- Loại minh chứng.
- Thời gian nộp.
- Liên kết xem ảnh minh chứng.
- Nút duyệt.
- Nút từ chối.

Danh sách được phân trang 15 bản ghi.

## 6. Xem ảnh minh chứng an toàn

Ảnh không được lưu ở public storage và không được truy cập bằng URL tĩnh.

Route xem ảnh:

```text
GET /kyc/xem-anh/{ma_xac_thuc}
```

Route name:

```text
kyc.preview
```

Đặc điểm:

- Có middleware `auth`.
- Có middleware `signed`.
- Signed URL có thời hạn 30 phút.
- Chỉ Admin/Moderator được xem.
- Chỉ truy xuất hồ sơ có `loai_xac_thuc = CUU_SINH_VIEN`.
- File không tồn tại sẽ trả về 404.
- Header cache được cấu hình `no-store`, `no-cache`, `private`.

Trên giao diện Admin, ảnh hỗ trợ:

- Xem trong modal.
- Phóng to.
- Thu nhỏ.
- Xoay 90 độ.
- Đóng modal và reset trạng thái xem.

## 7. Luồng duyệt hồ sơ

Route:

```text
PUT /quan-tri/duyet-kyc/{ma_xac_thuc}/duyet
```

Route name:

```text
admin.kyc.approve
```

### Các bước xử lý

1. Kiểm tra người thao tác là Admin hoặc Moderator.
2. Tìm hồ sơ đúng loại `CUU_SINH_VIEN`.
3. Kiểm tra hồ sơ còn `CHO_DUYET`.
4. Trong transaction, khóa bản ghi bằng `lockForUpdate()`.
5. Kiểm tra lại:
   - Hồ sơ vẫn đang chờ duyệt.
   - Người dùng còn tồn tại.
   - Người dùng có vai trò `CUU_SINH_VIEN`.
   - MSSV không xuất hiện trong danh sách sinh viên hiện hành.
6. Cập nhật:
   - `trang_thai = DA_DUYET`
   - `nguoi_duyet = người thao tác`
   - `thoi_gian_duyet = thời điểm hiện tại`
   - xóa lý do từ chối cũ nếu có.
7. Kích hoạt tài khoản:
   - `trang_thai = DANG_HOAT_DONG`
   - `vai_tro = CUU_SINH_VIEN`
8. Đảm bảo người dùng có ví điểm:
   - Nếu chưa có, tạo ví với 100 điểm khả dụng.
   - Nếu đã có, không tạo trùng.
9. Gửi thông báo database nội bộ về kết quả duyệt.

### Chống xử lý đồng thời

Việc dùng `lockForUpdate()` giúp ngăn hai Admin/Moderator cùng duyệt một hồ sơ trong cùng thời điểm.

Hồ sơ đã được xử lý sẽ không được xử lý lại.

## 8. Luồng từ chối hồ sơ

Route:

```text
PUT /quan-tri/duyet-kyc/{ma_xac_thuc}/tu-choi
```

Route name:

```text
admin.kyc.reject
```

### Quy tắc

- Bắt buộc nhập `ly_do_tu_choi`.
- Độ dài tối thiểu 6 ký tự.
- Độ dài tối đa 500 ký tự.
- Chỉ hồ sơ `CHO_DUYET` mới được từ chối.
- Chỉ hồ sơ `CUU_SINH_VIEN` hợp lệ mới được xử lý.
- Lưu người duyệt/xử lý.
- Lưu thời điểm xử lý.
- Lưu lý do từ chối.
- Sử dụng transaction và khóa bản ghi.

Sau khi từ chối:

```text
trang_thai = TU_CHOI
```

Tài khoản vẫn ở trạng thái `CHUA_XAC_THUC`, vì vậy không thể đăng nhập bình thường.

## 9. Kiểm soát đăng nhập của cựu sinh viên

Khi cựu sinh viên đăng nhập:

1. Hệ thống xác minh email/MSSV và mật khẩu.
2. Nếu người dùng là `CUU_SINH_VIEN` và trạng thái là `CHUA_XAC_THUC`:
   - Tìm hồ sơ KYC bị từ chối gần nhất.
   - Nếu có hồ sơ bị từ chối, hiển thị lý do.
   - Nếu không có hồ sơ bị từ chối, xác định hồ sơ đang chờ duyệt và từ chối đăng nhập.
3. Nếu trạng thái là `DANG_HOAT_DONG`, đăng nhập bình thường.
4. Nếu trạng thái là `TAM_KHOA`, từ chối đăng nhập với thông báo tài khoản bị khóa.

Thông báo khi đang chờ duyệt:

> Hồ sơ minh chứng cựu sinh viên của bạn đang chờ Quản trị viên đối soát và kích hoạt. Vui lòng quay lại sau.

Thông báo khi bị từ chối:

> Hồ sơ cựu sinh viên của bạn đã bị từ chối: {lý do}.

## 10. Hiển thị trạng thái trực tiếp trên màn hình đăng nhập

Hiện tại chưa gửi email. Thay vào đó, khi người dùng nhập đúng thông tin đăng nhập nhưng hồ sơ cựu sinh viên còn chờ duyệt:

1. Backend đặt flash session `kyc_status`.
2. Controller trang đăng nhập truyền `kycStatus` sang Inertia.
3. React hiển thị khung cảnh báo màu vàng:

> **Hồ sơ đang chờ duyệt**  
> Hồ sơ cựu sinh viên của bạn đang chờ Admin đối soát. Bạn sẽ có thể đăng nhập sau khi hồ sơ được duyệt.

Thông báo này được hiển thị riêng với lỗi trường email để người dùng dễ nhận biết đây là trạng thái nghiệp vụ, không phải nhập sai mật khẩu.

## 11. Thông báo database nội bộ

Khi hồ sơ được duyệt hoặc từ chối, hệ thống tạo notification database thông qua:

```text
KycDecisionNotification
```

Notification chứa:

- Quyết định: `DA_DUYET` hoặc `TU_CHOI`.
- Tiêu đề.
- Nội dung.

Ở giai đoạn hiện tại, notification database được tạo để làm nền tảng cho khu vực thông báo trong ứng dụng. Người dùng chưa đăng nhập được trước khi duyệt nên kênh hiển thị chính hiện tại vẫn là màn hình đăng nhập.

**Chưa triển khai:**

- Email.
- Google API.
- SMTP.
- Dịch vụ email bên ngoài.
- Push notification.

## 12. Các file đã thực hiện

### Backend

| File | Nội dung |
|---|---|
| [KycController.php](app/Http/Controllers/KycController.php) | Đăng ký KYC, hàng đợi, Signed URL, duyệt, từ chối, cấp ví và kiểm tra chống sinh viên hiện hành |
| [AlumniKycRequest.php](app/Http/Requests/AlumniKycRequest.php) | Chuẩn hóa dữ liệu, validate biểu mẫu và chặn MSSV thuộc danh sách sinh viên hiện hành |
| [LoginRequest.php](app/Http/Requests/Auth/LoginRequest.php) | Chặn cựu sinh viên chưa được duyệt; hiển thị lý do từ chối hoặc trạng thái chờ duyệt |
| [AuthenticatedSessionController.php](app/Http/Controllers/Auth/AuthenticatedSessionController.php) | Truyền `kycStatus` tới trang đăng nhập |
| [KycDecisionNotification.php](app/Notifications/KycDecisionNotification.php) | Notification database cho quyết định duyệt/từ chối |

### Frontend

| File | Nội dung |
|---|---|
| [Admin/Kyc/Index.jsx](resources/js/Pages/Admin/Kyc/Index.jsx) | Hiển thị hàng đợi, thông tin Khoa, modal ảnh, xoay/phóng to/thu nhỏ, duyệt và từ chối |
| [Auth/Login.jsx](resources/js/Pages/Auth/Login.jsx) | Hiển thị khung “Hồ sơ đang chờ duyệt” trên màn hình đăng nhập |

### Route

| File | Nội dung |
|---|---|
| [routes/community.php](routes/community.php) | Loại bỏ các route upload/status KYC người dùng trỏ tới method không tồn tại; giữ route quản trị KYC |
| [routes/kyc.php](routes/kyc.php) | Bổ sung route Signed URL `kyc.preview` |

### Kiểm thử

| File | Nội dung |
|---|---|
| [KycQueueTest.php](tests/Feature/KycQueueTest.php) | Kiểm tra chặn sinh viên hiện hành, lọc hàng đợi và duyệt/cấp ví/thông báo |

## 13. Kiểm thử đã thực hiện

### KYC regression tests

```text
php artisan test tests\Feature\KycQueueTest.php --no-ansi
```

Kết quả:

```text
3 passed
21 assertions
```

Các trường hợp đã kiểm tra:

1. Sinh viên hiện hành không thể gửi KYC cựu sinh viên.
2. Hàng đợi chỉ chứa hồ sơ cựu sinh viên đang `CHO_DUYET`.
3. Duyệt hồ sơ:
   - Cập nhật trạng thái hồ sơ.
   - Lưu người duyệt.
   - Kích hoạt tài khoản.
   - Tạo ví 100 điểm.
   - Tạo notification.

### Academic catalog regression tests

```text
php artisan test tests\Feature\AcademicCatalogTest.php --no-ansi
```

Kết quả:

```text
11 passed
49 assertions
```

### Frontend build

```text
npm.cmd run build
```

Kết quả: build Vite thành công.

### Syntax và route

Đã kiểm tra cú pháp PHP cho các file backend thay đổi và kiểm tra route KYC:

```text
admin.kyc.index
admin.kyc.approve
admin.kyc.reject
kyc.preview
```

## 14. Phạm vi chưa triển khai trong báo cáo này

Các nội dung dưới đây thuộc nghiệp vụ dữ liệu sinh viên hiện hành nhưng chưa được triển khai trong nhóm thay đổi KYC này:

- Giao diện Admin import file CSV/XLSX vào `danh_sach_sinh_vien_raw`.
- Preview và báo cáo lỗi từng dòng khi import.
- Cập nhật/upsert dữ liệu sinh viên theo file nhà trường.
- Dashboard thống kê tổng số, chưa kích hoạt và đã kích hoạt.
- Validation email `@student.agu.edu.vn` ở toàn bộ luồng import.
- Cơ chế thông báo email.
- Trang tra cứu trạng thái KYC công khai bằng mã hồ sơ hoặc token.

## 15. Kết luận

Luồng KYC cựu sinh viên hiện đã được tách khỏi luồng xác thực sinh viên hiện hành và có các lớp kiểm soát chính:

- Chặn sinh viên hiện hành nộp KYC.
- Chỉ hiển thị hồ sơ cựu sinh viên đang chờ duyệt.
- Bảo vệ ảnh minh chứng bằng private storage và Signed URL.
- Lưu người xử lý, thời điểm và lý do.
- Ngăn xử lý lại hồ sơ.
- Kích hoạt tài khoản và cấp ví sau khi duyệt.
- Không cho cựu sinh viên chưa duyệt đăng nhập.
- Hiển thị trạng thái chờ duyệt trực tiếp trên màn hình đăng nhập.

Ở giai đoạn hiện tại, hệ thống **không cần liên kết Google, không gọi email API và không phát sinh chi phí email**. Người dùng biết trạng thái bằng cách quay lại đăng nhập và nhập thông tin tài khoản.
