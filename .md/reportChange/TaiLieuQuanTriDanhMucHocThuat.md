# Tài liệu quản trị danh mục học thuật

## 1. Mục đích

Tài liệu này mô tả đầy đủ phạm vi, nghiệp vụ, quy tắc dữ liệu, luồng xử lý và các thay đổi đã triển khai cho chức năng quản trị danh mục học thuật của AGU Academic Hub.

Danh mục học thuật gồm ba cấp:

```text
Khoa
└── Chuyên ngành
    └── Môn học
```

Chức năng dành cho Quản trị viên, được thực hiện trên trang quản trị bằng Laravel, Inertia.js và React.

## 2. Phạm vi chức năng

Quản trị viên có thể:

- Xem danh sách Khoa, Chuyên ngành và Môn học.
- Tìm kiếm danh mục.
- Lọc theo Khoa.
- Lọc theo trạng thái:
  - Đang hoạt động.
  - Đã vô hiệu hóa.
  - Tất cả trạng thái.
- Phân trang độc lập cho từng bảng danh mục.
- Thêm Khoa.
- Sửa Khoa.
- Vô hiệu hóa hoặc kích hoạt lại Khoa.
- Thêm Chuyên ngành.
- Sửa Chuyên ngành.
- Vô hiệu hóa hoặc kích hoạt lại Chuyên ngành.
- Thêm Môn học.
- Sửa Môn học.
- Vô hiệu hóa hoặc kích hoạt lại Môn học.
- Gán Môn học vào một hoặc nhiều Chuyên ngành.
- Thay đổi lại danh sách Chuyên ngành được gán cho Môn học.
- Quản lý mã, tên, số tín chỉ và mô tả.
- Giữ nguyên các liên kết tài liệu, review và yêu cầu tài liệu khi danh mục thay đổi.

## 3. Các thành phần đã triển khai

### 3.1. Backend

- [AcademicCatalogController.php](../app/Http/Controllers/AcademicCatalogController.php)
  - Hiển thị danh mục.
  - Tìm kiếm, lọc và phân trang.
  - CRUD Khoa, Chuyên ngành và Môn học.
  - Vô hiệu hóa/kích hoạt lại.
  - Kiểm tra liên kết trước khi vô hiệu hóa Môn học.
  - Tính trạng thái hiệu lực thực tế của bản ghi con.
- [AcademicCatalogRequest.php](../app/Http/Requests/Admin/AcademicCatalogRequest.php)
  - Xác nhận quyền Quản trị viên.
  - Chuẩn hóa mã trước validation.
  - Trim dữ liệu dạng chuỗi.
- [StoreFacultyRequest.php](../app/Http/Requests/Admin/StoreFacultyRequest.php)
  - Validation tạo Khoa.
- [UpdateFacultyRequest.php](../app/Http/Requests/Admin/UpdateFacultyRequest.php)
  - Validation cập nhật Khoa.
- [StoreMajorRequest.php](../app/Http/Requests/Admin/StoreMajorRequest.php)
  - Validation tạo Chuyên ngành.
- [UpdateMajorRequest.php](../app/Http/Requests/Admin/UpdateMajorRequest.php)
  - Validation cập nhật Chuyên ngành.
- [StoreCourseRequest.php](../app/Http/Requests/Admin/StoreCourseRequest.php)
  - Validation tạo Môn học.
- [UpdateCourseRequest.php](../app/Http/Requests/Admin/UpdateCourseRequest.php)
  - Validation cập nhật Môn học.

### 3.2. Frontend

- [Index.jsx](../resources/js/Pages/Admin/AcademicCatalog/Index.jsx)
  - Giao diện quản trị toàn bộ danh mục.
  - Form thêm/sửa.
  - Bảng danh sách.
  - Tìm kiếm, lọc, phân trang.
  - Hiển thị trạng thái và nút thao tác.
  - Hiển thị lỗi validation ngay tại trường nhập tương ứng.

### 3.3. Model và các luồng liên quan

- [Khoa.php](../app/Models/Khoa.php)
- [ChuyenNganh.php](../app/Models/ChuyenNganh.php)
- [MonHoc.php](../app/Models/MonHoc.php)
- [FacultyController.php](../app/Http/Controllers/FacultyController.php)
- [CourseController.php](../app/Http/Controllers/CourseController.php)
- [CourseReviewController.php](../app/Http/Controllers/CourseReviewController.php)
- [WishlistBountyController.php](../app/Http/Controllers/WishlistBountyController.php)

Các Controller liên quan được cập nhật để không công khai hoặc thao tác với Môn học thuộc Khoa đã bị vô hiệu hóa.

### 3.4. Kiểm thử

- [AcademicCatalogTest.php](../tests/Feature/AcademicCatalogTest.php)

Test bao phủ quyền truy cập, CRUD, validation mã, gán Chuyên ngành, liên kết review và trạng thái hiệu lực kế thừa.

## 4. Quyền truy cập

Chỉ tài khoản có quyền Quản trị viên được phép quản lý danh mục.

Quy tắc được kiểm tra ở backend bằng `AcademicCatalogRequest::authorize()`. Frontend không phải là lớp bảo mật cuối cùng; việc ẩn hoặc hiện menu không thay thế cho kiểm tra quyền trên server.

Tài khoản không phải Quản trị viên:

- Không được mở trang quản trị danh mục.
- Không được thêm, sửa hoặc chuyển trạng thái Khoa.
- Không được thêm, sửa hoặc chuyển trạng thái Chuyên ngành.
- Không được thêm, sửa hoặc chuyển trạng thái Môn học.

## 5. Mô hình trạng thái và soft delete

### 5.1. Trạng thái lưu trong database

Các bản ghi sử dụng cơ chế soft delete thông qua trường `ngay_xoa_tam`:

| Giá trị `ngay_xoa_tam` | Trạng thái bản ghi |
|---|---|
| `NULL` | Bản ghi chưa bị vô hiệu hóa ở cấp chính nó |
| Có giá trị thời gian | Bản ghi đã bị vô hiệu hóa ở cấp chính nó |

Vô hiệu hóa không xóa vật lý dữ liệu. Vì vậy:

- Mã và nội dung bản ghi vẫn được bảo toàn.
- Khóa liên kết tới tài liệu, review và wishlist không bị mất.
- Có thể kích hoạt lại khi thỏa điều kiện nghiệp vụ.

### 5.2. Không đồng bộ soft delete xuống bản ghi con

Khi Khoa bị vô hiệu hóa:

- Chỉ cập nhật trạng thái của Khoa.
- Không cập nhật `ngay_xoa_tam` của Chuyên ngành thuộc Khoa.
- Không cập nhật `ngay_xoa_tam` của Môn học thuộc Khoa.

Đây là quyết định có chủ ý để:

- Không làm thay đổi trạng thái lưu trữ thật của bản ghi con.
- Không cần thêm bảng hoặc cột mới.
- Không phải thực hiện thao tác hàng loạt lên nhiều bản ghi.
- Có thể tự động khôi phục hiệu lực cho toàn bộ cây khi Khoa được kích hoạt lại.

## 6. Trạng thái hiệu lực thực tế kế thừa từ Khoa

### 6.1. Quy tắc tổng quát

Một Chuyên ngành hoặc Môn học chỉ được xem là **đang hoạt động và khả dụng** khi đồng thời thỏa cả hai điều kiện:

```text
Bản thân bản ghi đang hoạt động
AND
Khoa cha đang hoạt động
```

Có thể biểu diễn như sau:

```text
effective_active(child)
    = child.ngay_xoa_tam IS NULL
      AND parent_khoa.ngay_xoa_tam IS NULL
```

### 6.2. Khi Khoa bị vô hiệu hóa

Luồng đã chốt và triển khai:

```text
Khoa = vô hiệu hóa
    ↓
Chuyên ngành thuộc Khoa = không khả dụng về nghiệp vụ
    ↓
Môn học thuộc Khoa = không khả dụng về nghiệp vụ
```

Tuy nhiên, database vẫn giữ:

```text
Chuyên ngành.ngay_xoa_tam = NULL
Môn học.ngay_xoa_tam = NULL
```

Nói cách khác, bản ghi con không bị soft delete nhưng bị loại khỏi trạng thái hiệu lực thực tế do Khoa cha không còn hoạt động.

### 6.3. Khi Khoa được kích hoạt lại

Khi Khoa được restore:

- Chuyên ngành không bị soft delete trước đó tự động khả dụng trở lại.
- Môn học không bị soft delete trước đó tự động khả dụng trở lại.
- Không cần cập nhật từng bản ghi con.
- Không cần job đồng bộ hoặc migration bổ sung.

Nếu một Chuyên ngành hoặc Môn học đã bị vô hiệu hóa độc lập từ trước, việc kích hoạt Khoa không tự động restore bản ghi đó. Bản ghi vẫn phải được kích hoạt riêng.

### 6.4. Chặn kích hoạt bản ghi con không hợp lệ

Không cho phép kích hoạt riêng:

- Chuyên ngành khi Khoa cha đang bị vô hiệu hóa.
- Môn học khi Khoa cha đang bị vô hiệu hóa.

Mục tiêu là tránh trạng thái lưu trữ gây hiểu nhầm: bản ghi con có `ngay_xoa_tam = NULL` nhưng cha vẫn không khả dụng.

## 7. Nghiệp vụ Khoa

### 7.1. Thêm Khoa

Dữ liệu bắt buộc:

- Ký hiệu Khoa.
- Tên Khoa.

Khi tạo thành công:

- Khoa được lưu ở trạng thái hoạt động.
- Ký hiệu được chuẩn hóa thành chữ hoa.
- Ký hiệu phải duy nhất.

### 7.2. Sửa Khoa

Quản trị viên có thể sửa ký hiệu và tên Khoa.

Khi sửa:

- Bản ghi hiện tại được tìm bằng `withTrashed()`, nên có thể sửa cả Khoa đang vô hiệu hóa.
- Mã định danh nội bộ của Khoa không thay đổi.
- Các bản ghi Chuyên ngành và Môn học không bị xóa hoặc tự động thay đổi liên kết.

### 7.3. Vô hiệu hóa/kích hoạt Khoa

Vô hiệu hóa Khoa sử dụng soft delete.

Khi Khoa bị vô hiệu hóa:

- Khoa không xuất hiện trong danh sách hoạt động.
- Chuyên ngành và Môn học thuộc Khoa không còn hiệu lực thực tế.
- Không xóa Chuyên ngành, Môn học, tài liệu, review hoặc wishlist.

Khi kích hoạt lại:

- Khoa trở lại trạng thái hoạt động.
- Các bản ghi con chưa bị vô hiệu hóa độc lập có hiệu lực trở lại.

## 8. Nghiệp vụ Chuyên ngành

### 8.1. Thêm Chuyên ngành

Dữ liệu gồm:

- Khoa cha.
- Ký hiệu Chuyên ngành.
- Tên Chuyên ngành.
- Mô tả không bắt buộc.

Khoa được chọn phải:

- Tồn tại.
- Chưa bị vô hiệu hóa.

### 8.2. Sửa Chuyên ngành

Quản trị viên có thể:

- Đổi Khoa cha sang một Khoa hoạt động khác.
- Đổi ký hiệu.
- Đổi tên.
- Đổi mô tả.

Không cho phép chuyển sang Khoa không tồn tại hoặc đang bị vô hiệu hóa.

### 8.3. Vô hiệu hóa/kích hoạt Chuyên ngành

- Vô hiệu hóa Chuyên ngành không xóa vật lý.
- Kích hoạt lại chỉ thành công khi Khoa cha đang hoạt động.
- Nếu Khoa cha bị vô hiệu hóa, hệ thống trả lỗi nghiệp vụ thay vì âm thầm bỏ qua thao tác.

## 9. Nghiệp vụ Môn học

### 9.1. Thêm Môn học

Dữ liệu quản lý:

- Khoa.
- Mã học phần.
- Tên môn học.
- Số tín chỉ.
- Mô tả.
- Danh sách Chuyên ngành được gán.

Khoa được chọn phải đang hoạt động.

### 9.2. Sửa Môn học

Quản trị viên có thể cập nhật:

- Khoa.
- Mã học phần.
- Tên môn học.
- Số tín chỉ.
- Mô tả.
- Danh sách Chuyên ngành.

Khi cập nhật danh sách Chuyên ngành, hệ thống dùng `sync()` để đồng bộ đúng các liên kết mới:

- Liên kết được chọn sẽ được giữ/thêm.
- Liên kết bị bỏ chọn sẽ được gỡ khỏi bảng trung gian.
- Không tác động đến tài liệu, review hoặc yêu cầu tài liệu của Môn học.

### 9.3. Gán Môn học vào Chuyên ngành

Một Môn học có thể được gán cho nhiều Chuyên ngành.

Mỗi Chuyên ngành được gán phải:

- Là một ID hợp lệ.
- Tồn tại trong database.
- Chưa bị vô hiệu hóa.
- Thuộc đúng Khoa đang được chọn cho Môn học.
- Không bị lặp trong danh sách gửi lên.

Nếu danh sách chứa ID không hợp lệ, hệ thống trả lỗi validation. Hệ thống không âm thầm bỏ qua ID đó và vẫn tạo Môn học.

## 10. Quy tắc mã danh mục

### 10.1. Chuẩn hóa chung

Trước khi validation, các trường mã được:

1. Ép về chuỗi.
2. Loại bỏ khoảng trắng đầu/cuối bằng `trim()`.
3. Chuyển thành chữ hoa bằng `mb_strtoupper()`.

Vì vậy, giá trị người dùng nhập như:

```text
 pkd101
```

sẽ được xử lý thành:

```text
PKD101
```

Database lưu mã chuẩn hóa và frontend cũng hiển thị mã chữ hoa.

### 10.2. Mã Khoa

Mã Khoa:

- Được chuẩn hóa thành chữ hoa.
- Dài từ 2 đến 20 ký tự.
- Chỉ gồm chữ cái in hoa, chữ số và dấu gạch ngang.
- Phải duy nhất.

Ví dụ:

```text
CNTT
KTPM
KT-01
```

### 10.3. Mã Chuyên ngành

Mã Chuyên ngành bắt buộc theo dạng:

```text
DXX
```

Trong đó:

- Ký tự đầu tiên bắt buộc là `D`.
- Hai ký tự tiếp theo phải là chữ cái.
- Tổng cộng đúng 3 ký tự.
- Toàn bộ mã được lưu và hiển thị in hoa.

Ví dụ hợp lệ:

```text
DMK
DTH
DPM
```

Ví dụ không hợp lệ:

```text
DQTKD
D1K
MKD
DD
```

### 10.4. Mã học phần

Mã học phần bắt buộc theo dạng:

```text
LLLNNN
```

Trong đó:

- `LLL` là đúng 3 chữ cái.
- `NNN` là đúng 3 chữ số.
- Tổng cộng đúng 6 ký tự.
- Mã phải duy nhất.
- Mã được lưu và hiển thị in hoa.

Regex backend:

```text
^[A-Z]{3}[0-9]{3}$
```

Ví dụ hợp lệ:

```text
PKD101
COS101
MAT201
```

Ví dụ không hợp lệ:

```text
PK101       // chỉ có 2 chữ cái
PKDD01      // có 4 chữ cái
PKD1001     // có 4 chữ số
PKD-01      // có ký tự gạch ngang
PKD10A      // chữ cái không nằm ở phần đầu
```

## 11. Quy tắc số tín chỉ

Số tín chỉ của một Môn học:

- Bắt buộc là số nguyên.
- Tối thiểu là 1.
- Tối đa là 8.

Quy tắc backend:

```text
required
integer
min:1
max:8
```

Frontend sử dụng:

- `min="1"`.
- `max="8"`.
- `step="1"`.
- Gợi ý: `Từ 1 đến 8 tín chỉ`.

Giá trị 9 hoặc lớn hơn bị từ chối và hiển thị lỗi:

```text
Số tín chỉ phải từ 1 đến 8.
```

## 12. Validation backend và frontend

### 12.1. Phân tách trách nhiệm

Validation đầu vào được tách khỏi Controller và đặt trong Form Request:

- `StoreFacultyRequest`.
- `UpdateFacultyRequest`.
- `StoreMajorRequest`.
- `UpdateMajorRequest`.
- `StoreCourseRequest`.
- `UpdateCourseRequest`.

Controller chỉ xử lý nghiệp vụ sau validation, gồm:

- Tạo/cập nhật model.
- Đồng bộ Chuyên ngành với Môn học.
- Kiểm tra tài liệu, review và yêu cầu tài liệu.
- Kiểm tra trạng thái cha-con khi kích hoạt.
- Trả thông báo thành công hoặc lỗi nghiệp vụ.

### 12.2. Hiển thị lỗi trên giao diện

Form quản trị hiển thị lỗi ngay bên dưới trường tương ứng, gồm:

- Ký hiệu Khoa.
- Tên Khoa.
- Khoa của Chuyên ngành.
- Mã Chuyên ngành.
- Tên Chuyên ngành.
- Mô tả Chuyên ngành.
- Mã học phần.
- Tên Môn học.
- Số tín chỉ.
- Danh sách Chuyên ngành được gán.

Khi trường có lỗi:

- Hiển thị nội dung lỗi bằng tiếng Việt.
- Viền trường chuyển sang màu cảnh báo.
- Có `aria-invalid` cho trường phù hợp.
- Có gợi ý định dạng đối với mã ngành và mã học phần.
- Lỗi của trường được xóa khi người dùng bắt đầu chỉnh sửa lại.

Frontend chỉ cung cấp phản hồi sớm và cải thiện UX. Backend vẫn là nơi quyết định dữ liệu có hợp lệ hay không.

## 13. Bảo toàn liên kết dữ liệu

### 13.1. Khi đổi thông tin danh mục

Việc sửa tên hoặc mã hiển thị không xóa các liên kết nghiệp vụ hiện có.

Các liên kết của Môn học với:

- Tài liệu.
- Review.
- Yêu cầu tài liệu.
- Wishlist/bounty.

được giữ lại khi Môn học được cập nhật hoặc Khoa cha thay đổi trạng thái.

### 13.2. Khi vô hiệu hóa Môn học

Môn học không được vô hiệu hóa nếu đang có ít nhất một liên kết thuộc các nhóm:

- Tài liệu, kể cả bản ghi đã soft delete.
- Review, kể cả bản ghi đã soft delete.
- Yêu cầu tài liệu, kể cả bản ghi đã soft delete.

Lý do là không được làm mất hoặc phá vỡ dữ liệu lịch sử.

Thông báo lỗi:

```text
Không thể vô hiệu hóa môn học đang có tài liệu, review hoặc yêu cầu tài liệu liên kết.
```

### 13.3. Khi Khoa bị vô hiệu hóa

Không xóa:

- Chuyên ngành.
- Môn học.
- Tài liệu.
- Review.
- Wishlist.
- Yêu cầu tài liệu.

Chỉ thay đổi khả dụng thực tế thông qua điều kiện trạng thái của Khoa cha.

## 14. Tìm kiếm, lọc và phân trang

### 14.1. Tìm kiếm

Ô tìm kiếm được debounce khoảng 350ms để tránh gửi request cho từng ký tự.

Các trường tìm kiếm chính:

- Khoa: ký hiệu và tên Khoa.
- Chuyên ngành: ký hiệu và tên Chuyên ngành.
- Môn học: mã học phần và tên Môn học.

### 14.2. Lọc

Có thể lọc theo:

- Khoa.
- Trạng thái hoạt động.
- Trạng thái vô hiệu hóa.
- Tất cả trạng thái.

Với Chuyên ngành và Môn học, bộ lọc trạng thái dựa trên **hiệu lực thực tế**, không chỉ dựa vào `ngay_xoa_tam` của chính bản ghi.

Ví dụ:

- Môn học có `ngay_xoa_tam = NULL` nhưng Khoa cha bị vô hiệu hóa vẫn nằm trong nhóm không khả dụng.

### 14.3. Phân trang

Khoa, Chuyên ngành và Môn học có bộ phân trang độc lập. Tham số truy vấn được giữ lại khi chuyển trang để không mất bộ lọc hiện tại.

## 15. Luồng thao tác chính

### 15.1. Luồng tạo Môn học

```text
Quản trị viên mở form Môn học
    ↓
Chọn Khoa đang hoạt động
    ↓
Nhập mã học phần, tên, số tín chỉ, mô tả
    ↓
Chọn các Chuyên ngành
    ↓
Frontend hiển thị lỗi sớm nếu định dạng không hợp lệ
    ↓
Form Request chuẩn hóa và validate lại ở backend
    ↓
Kiểm tra Chuyên ngành có thuộc đúng Khoa hay không
    ↓
Tạo Môn học
    ↓
Đồng bộ bảng liên kết Môn học - Chuyên ngành
    ↓
Thông báo thành công
```

### 15.2. Luồng sửa mã học phần

```text
Quản trị viên chọn sửa Môn học
    ↓
Nhập mã, ví dụ pkd101
    ↓
Frontend chuyển thành PKD101
    ↓
Backend trim và chuyển hoa một lần nữa
    ↓
Kiểm tra dạng 3 chữ + 3 số
    ↓
Kiểm tra mã duy nhất, loại trừ chính bản ghi đang sửa
    ↓
Cập nhật database
```

### 15.3. Luồng vô hiệu hóa Khoa

```text
Quản trị viên chọn vô hiệu hóa Khoa
    ↓
Khoa được soft delete
    ↓
Không soft delete Chuyên ngành/Môn học con
    ↓
Các truy vấn hiệu lực loại Chuyên ngành/Môn học khỏi danh sách active
    ↓
Các luồng public không cho truy cập nội dung thuộc Khoa đó
```

### 15.4. Luồng kích hoạt lại Khoa

```text
Quản trị viên chọn kích hoạt Khoa
    ↓
Khoa được restore
    ↓
Chuyên ngành/Môn học con chưa bị vô hiệu hóa riêng tự động khả dụng lại
    ↓
Không cần cập nhật hàng loạt bản ghi con
```

### 15.5. Luồng vô hiệu hóa Môn học có liên kết

```text
Quản trị viên chọn vô hiệu hóa Môn học
    ↓
Hệ thống kiểm tra tài liệu, review, yêu cầu tài liệu
    ↓
Nếu có liên kết: trả lỗi, không đổi trạng thái
    ↓
Nếu không có liên kết: soft delete Môn học
```

## 16. Các quyết định thiết kế đã chốt

### 16.1. Không thêm bảng hoặc trường mới

Không tạo bảng trạng thái hiệu lực riêng và không thêm cột `effective_status`.

Trạng thái hiệu lực được suy ra từ:

- `ngay_xoa_tam` của bản ghi.
- `ngay_xoa_tam` của Khoa cha.

### 16.2. Không dùng Resource để validation

[CourseReviewResource.php](../app/Http/Resources/CourseReviewResource.php) chỉ chịu trách nhiệm định dạng dữ liệu trả về cho review.

Resource không được sử dụng để:

- Kiểm tra input.
- Chuẩn hóa mã danh mục.
- Thay thế Form Request.

### 16.3. Không âm thầm bỏ qua dữ liệu sai

Nếu ID Chuyên ngành được gửi lên nhưng không tồn tại, đã vô hiệu hóa hoặc thuộc Khoa khác:

- Request bị từ chối.
- Trả lỗi validation.
- Không tạo hoặc cập nhật Môn học dở dang.

## 17. Kết quả kiểm thử đã thực hiện

Feature test danh mục bao phủ các trường hợp:

1. Admin tạo Môn học và gán Chuyên ngành.
2. Từ chối mã học phần trùng.
3. Chuẩn hóa mã học phần chữ thường thành chữ hoa.
4. Từ chối mã học phần sai số lượng chữ/số hoặc sai tổng độ dài.
5. Từ chối Môn học có quá 8 tín chỉ.
6. Chuẩn hóa mã Khoa.
7. Chuẩn hóa và kiểm tra mã Chuyên ngành dạng `DXX`.
8. Từ chối Chuyên ngành thuộc Khoa khác khi gán vào Môn học.
9. Không cho vô hiệu hóa Môn học có review.
10. Không cho Moderator quản lý danh mục.
11. Kiểm tra Khoa bị vô hiệu hóa làm bản ghi con mất hiệu lực thực tế nhưng không bị soft delete.
12. Không cho kích hoạt bản ghi con khi Khoa cha đang vô hiệu hóa.

Kết quả lần kiểm tra gần nhất:

```text
11 tests passed
49 assertions passed
Frontend build thành công
git diff --check thành công
```

## 18. Hướng dẫn kiểm tra thủ công

### 18.1. Kiểm tra mã Chuyên ngành

1. Mở trang quản trị danh mục.
2. Chọn thêm hoặc sửa Chuyên ngành.
3. Nhập `DQTKD`.
4. Gửi form.
5. Kiểm tra lỗi xuất hiện ngay dưới ô mã.
6. Nhập `dmk`.
7. Kiểm tra hệ thống chuẩn hóa thành `DMK`.

### 18.2. Kiểm tra mã học phần

1. Mở form Môn học.
2. Nhập `pkd101`.
3. Kiểm tra ô nhập chuyển thành `PKD101`.
4. Thử nhập `PK101`, `PKDD01` hoặc `PKD1001`.
5. Kiểm tra lỗi định dạng hiển thị rõ ràng.

### 18.3. Kiểm tra số tín chỉ

1. Nhập giá trị từ 1 đến 8.
2. Kiểm tra form chấp nhận dữ liệu.
3. Nhập `9`.
4. Kiểm tra trình duyệt giới hạn giá trị tối đa là 8.
5. Nếu gửi request trực tiếp lên backend với giá trị 9, kiểm tra backend vẫn từ chối.

### 18.4. Kiểm tra hiệu lực kế thừa

1. Tạo một Khoa đang hoạt động.
2. Tạo Chuyên ngành và Môn học thuộc Khoa đó.
3. Vô hiệu hóa Khoa.
4. Kiểm tra Chuyên ngành và Môn học không còn nằm trong danh sách active.
5. Kiểm tra `ngay_xoa_tam` của bản ghi con vẫn không đổi.
6. Kích hoạt lại Khoa.
7. Kiểm tra các bản ghi con chưa bị vô hiệu hóa độc lập khả dụng lại.

## 19. Các nguyên tắc cần giữ khi mở rộng chức năng

Khi tiếp tục phát triển danh mục học thuật:

- Luôn validate ở backend bằng Form Request.
- Không chỉ dựa vào validation HTML/React.
- Không xóa vật lý bản ghi danh mục đang có liên kết.
- Không cập nhật `ngay_xoa_tam` hàng loạt cho bản ghi con khi Khoa thay đổi trạng thái.
- Luôn tính trạng thái hiệu lực của Chuyên ngành/Môn học kèm trạng thái Khoa cha.
- Không cho gán Chuyên ngành khác Khoa vào Môn học.
- Không âm thầm bỏ qua ID hoặc dữ liệu không hợp lệ.
- Giữ thông báo lỗi cụ thể, dễ hiểu và hiển thị đúng trường.
- Khi thay đổi quy tắc mã hoặc số tín chỉ, phải cập nhật đồng thời:
  - Form Request tạo.
  - Form Request cập nhật.
  - Giao diện React.
  - Feature test.
  - Tài liệu này.

