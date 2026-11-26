# BÁO CÁO CHI TIẾT CÁC LỖI HỆ THỐNG (BUG REPORT)
**Dự án:** AGU Academic Hub - Nền tảng Chia sẻ và Quản lý Học liệu Học thuật  
**Tổ chức:** Trường Đại học An Giang (AGU)  
**Vị trí:** Senior QA Engineer / Security Specialist  
**Ngày phát hành:** 21/11/2026  
**Phiên bản:** v1.0.0  

---

## 1. BẢNG TỔNG HỢP DANH SÁCH LỖI (DEFECT SUMMARY)

| Mã lỗi | Tiêu đề lỗi | Mức độ (Severity) | Độ ưu tiên (Priority) | Phân hệ ảnh hưởng | Trạng thái |
| :---: | :--- | :---: | :---: | :--- | :---: |
| **`BUG-SEC-01`** | Lỗ hổng Arbitrary File Upload: Cho phép tải lên tệp tin thực thi nguy hiểm (.php, .exe, .sh, .bat) | **CRITICAL** | **P1 (Khẩn cấp)** | Document Management / Security | `CONFIRMED` |
| **`BUG-DOC-03`** | Lỗ hổng IDOR: Cho phép người dùng bất kỳ tải phiên bản mới đè lên tài liệu của người khác | **HIGH** | **P1 (Khẩn cấp)** | Document Lifecycle / Versioning | `CONFIRMED` |
| **`BUG-DB-01`** | Bảng `bao_cao_danh_gia` sử dụng Storage Engine MyISAM làm tê liệt Foreign Key và tạo bản ghi mồ côi | **HIGH** | **P2 (Cao)** | Database / Data Integrity | `CONFIRMED` |
| **`BUG-DOC-04`** | Thiếu validation Enum `phan_loai` trong `StoreDocumentRequest` gây sập hệ thống (HTTP 500) | **HIGH** | **P2 (Cao)** | Document Upload / Validation | `CONFIRMED` |
| **`BUG-DOC-02`** | Cơ chế lọc trùng Lớp 2 (Similarity Detection >85%) chưa được tích hợp vào tiến trình Upload | **HIGH** | **P2 (Cao)** | Document Lifecycle / AI Similarity | `CONFIRMED` |
| **`BUG-SEC-02`** | Cho phép người dùng tải lên tệp tin rỗng 0 bytes không có nội dung | **MEDIUM** | **P3 (Trung bình)** | Document Upload / Validation | `CONFIRMED` |
| **`BUG-DOC-01`** | Không cộng điểm đóng góp và không kích hoạt thông báo khi upload tài liệu trực tiếp | **MEDIUM** | **P3 (Trung bình)** | Document Lifecycle / Gamification | `CONFIRMED` |

---

## 2. CHI TIẾT TỪNG LỖI HỆ THỐNG

---

### BUG-SEC-01: Lỗ hổng Arbitrary File Upload cho phép tải lên tệp tin thực thi nguy hiểm
- **Mã lỗi:** `BUG-SEC-01`
- **Mức độ nghiêm trọng:** `CRITICAL` (CVSS v3.1 Score: **9.8 - Critical**)
- **Độ ưu tiên xử lý:** `P1` (Cần vá ngay lập tức trước khi triển khai thực tế)
- **Phân hệ:** Quản lý tài liệu / An ninh tệp tin (Document Security)
- **Vai trò chịu ảnh hưởng:** Sinh viên (`hai_dth235638@student.agu.edu.vn`), Cựu sinh viên (`dat@gmail.com`)
- **Tệp mã nguồn liên quan:** `app/Http/Requests/Documents/StoreDocumentRequest.php` (Dòng 21)

#### Tóm tắt lỗi:
FormRequest xác thực tệp tải lên (`StoreDocumentRequest`) chỉ kiểm tra tệp có tồn tại và dung lượng tối đa 50MB, nhưng **hoàn toàn không giới hạn phần mở rộng (extensions) hoặc kiểu MIME (MIME-types)**. Kẻ tấn công hoặc người dùng có thể tải lên các file mã thực thi như `.php`, `.exe`, `.sh`, `.bat`, `.py` trực tiếp vào hệ thống lưu trữ của máy chủ web.

#### Các bước tái hiện (Steps to Reproduce):
1. Đăng nhập vào hệ thống bằng tài khoản Sinh viên hoặc Cựu sinh viên đã duyệt.
2. Điều hướng tới trang tải lên tài liệu: `GET /tai-lieu/dang-tai`.
3. Điền đầy đủ thông tin học thuật (Khoa, Môn học, Tiêu đề).
4. Tại ô chọn tệp, chọn tệp tin chứa mã thực thi: `malicious_script.php` (hoặc `.exe`, `.sh`, `.bat`).
5. Bấm nút **"Đăng tải tài liệu"** (Gửi `POST /tai-lieu/dang-tai`).
6. Kiểm tra phản hồi HTTP và kiểm tra thư mục vật lý `storage/app/public/documents/` cùng CSDL bảng `tai_lieu`.

#### Dữ liệu kiểm thử:
- Tệp kiểm thử: `tests/Fixtures/qa_files/malicious_script.php` (MIME: `text/x-php`).
- Payload:
  ```http
  POST /tai-lieu/dang-tai HTTP/1.1
  Host: 127.0.0.1:8000
  Content-Type: multipart/form-data; boundary=----WebKitFormBoundary
  
  ------WebKitFormBoundary
  Content-Disposition: form-data; name="ma_mon_hoc"
  4
  ------WebKitFormBoundary
  Content-Disposition: form-data; name="tieu_de"
  Test Exploit Arbitrary Upload
  ------WebKitFormBoundary
  Content-Disposition: form-data; name="file"; filename="malicious_script.php"
  Content-Type: text/x-php

  <?php echo "EXPLOITED"; ?>
  ------WebKitFormBoundary--
  ```

#### Kết quả thực tế (Actual Result):
- Hệ thống trả về `HTTP 302 Found` (Chuyển hướng thành công tới danh sách tài liệu).
- Tệp `malicious_script.php` được lưu trữ thành công trên ổ đĩa vật lý của máy chủ tại `storage/app/public/documents/...`.
- CSDL tạo bản ghi mới với cột `duoi_file` là `"php"` và `mime_type` là `"text/x-php"`.

#### Kết quả mong đợi (Expected Result):
- Hệ thống phải từ chối ngay ở tầng FormRequest validation với mã `HTTP 422 Unprocessable Entity`.
- Trả về thông báo lỗi thân thiện: *"Chỉ chấp nhận các tệp định dạng PDF, DOCX, PPTX, XLSX, TXT."*

#### Phân tích nguyên nhân gốc rễ (Root Cause):
Tại `app/Http/Requests/Documents/StoreDocumentRequest.php`:
```php
public function rules(): array
{
    return [
        'ma_mon_hoc' => ['required', 'integer', 'exists:mon_hoc,id'],
        'tieu_de' => ['required', 'string', 'max:255'],
        'mo_ta' => ['nullable', 'string', 'max:2000'],
        'phan_loai' => ['nullable', 'string', 'max:50'],
        'file' => ['required', 'file', 'max:51200'], // <-- THIẾU RULE mimes HOẶC mimetypes!
    ];
}
```

#### Đánh giá tác động (Impact):
- **Remote Code Execution (RCE):** Nếu thư mục `storage` được cấu hình sai hoặc có lỗ hổng thực thi trực tiếp, kẻ tấn công có thể thực thi shell code kiểm soát toàn bộ máy chủ Web và chiếm đoạt CSDL.
- **Phát tán mã độc hại:** Tệp tin độc hại có thể được các sinh viên khác tải về, dẫn đến lây nhiễm virus vào thiết bị của người dùng trường.

#### Đề xuất khắc phục (Remediation):
Bổ sung rule `mimes` vào trường `file` tại `StoreDocumentRequest.php`:
```php
'file' => [
    'required',
    'file',
    'mimes:pdf,docx,pptx,xlsx,txt',
    'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain',
    'max:51200'
],
```

---

### BUG-DOC-03: Lỗ hổng IDOR cho phép tạo phiên bản mới đè lên tài liệu của người dùng khác
- **Mã lỗi:** `BUG-DOC-03`
- **Mức độ nghiêm trọng:** `HIGH` (CVSS v3.1 Score: **7.5 - High**)
- **Độ ưu tiên xử lý:** `P1` (Cần vá ngay lập tức)
- **Phân hệ:** Quản lý vòng đời tài liệu / Phân quyền phiên bản (Versioning Authorization)
- **Vai trò chịu ảnh hưởng:** Sinh viên, Cựu sinh viên
- **Tệp mã nguồn liên quan:** `app/Http/Requests/Documents/CreateDocumentVersionRequest.php` và `app/Http/Controllers/Web/DocumentController.php`

#### Tóm tắt lỗi:
Chức năng tạo phiên bản mới (`POST /tai-lieu/{id}/tao-phien-ban`) bị hổng kiểm soát truy cập mức đối tượng (Insecure Direct Object Reference - IDOR). Người dùng B hoàn toàn có thể chỉ định `ID` tài liệu của người dùng A để tạo phiên bản kế tiếp (ví dụ v1.1) mà không bị hệ thống chặn.

#### Các bước tái hiện:
1. Đăng nhập bằng tài khoản Sinh viên (`hai_dth235638@student.agu.edu.vn`) - Giả sử sở hữu tài liệu ID = 12.
2. Đăng xuất và đăng nhập bằng tài khoản Cựu sinh viên (`dat@gmail.com`) - Hoàn toàn không sở hữu tài liệu ID = 12.
3. Gửi HTTP Request tạo phiên bản mới:
   ```http
   POST /tai-lieu/12/tao-phien-ban HTTP/1.1
   Host: 127.0.0.1:8000
   Content-Type: multipart/form-data
   
   --boundary
   Content-Disposition: form-data; name="file"; filename="version_exploit.pdf"
   Content-Type: application/pdf
   
   [PDF Data]
   --boundary
   Content-Disposition: form-data; name="ghi_chu_thay_doi"
   Phiên bản do người ngoài tự ý cập nhật
   --boundary--
   ```
4. Kiểm tra phản hồi HTTP và CSDL bảng `tai_lieu`.

#### Kết quả thực tế:
- Request thành công với `HTTP 302 Found`.
- CSDL tạo bản ghi tài liệu mới có `tai_lieu_goc_id = 12`, `phien_ban = 1.1`, nhưng `ma_nguoi_dang` lại là Cựu sinh viên (User ID = 38). Tài liệu gốc của Sinh viên bị người lạ cập nhật phiên bản.

#### Kết quả mong đợi:
- Hệ thống phải trả về `HTTP 403 Forbidden` do người dùng hiện tại không phải là tác giả (`ma_nguoi_dang`) của tài liệu gốc và không có quyền Quản trị viên.

#### Phân tích nguyên nhân gốc rễ:
Tại `app/Http/Requests/Documents/CreateDocumentVersionRequest.php`:
```php
public function authorize(): bool
{
    return true; // <-- LUÔN TRẢ VỀ TRUE MÀ KHÔNG KIỂM TRA QUYỀN SỞ HỮU!
}
```

#### Đề xuất khắc phục:
Cập nhật phương thức `authorize()` trong `CreateDocumentVersionRequest.php`:
```php
public function authorize(): bool
{
    $documentId = $this->route('id');
    $document = \App\Models\TaiLieu::find($documentId);
    
    if (!$document) {
        return false;
    }

    $currentUser = $this->user();
    
    // Chỉ chủ sở hữu tài liệu gốc hoặc Quản trị viên mới được tạo phiên bản mới
    return $currentUser->getKey() === $document->ma_nguoi_dang || $currentUser->laQuanTriVien();
}
```

---

### BUG-DB-01: Bảng `bao_cao_danh_gia` sử dụng MyISAM engine, phá vỡ Foreign Key và tạo bản ghi mồ côi
- **Mã lỗi:** `BUG-DB-01`
- **Mức độ nghiêm trọng:** `HIGH`
- **Độ ưu tiên xử lý:** `P2`
- **Phân hệ:** Cơ sở dữ liệu / Tính toàn vẹn quan hệ (Referential Integrity)
- **Tệp mã nguồn liên quan:** `database/migrations/2026_10_14_000012_create_bao_cao_danh_gia_table.php`

#### Tóm tắt lỗi:
Trong CSDL `agu_academic_hub`, bảng `bao_cao_danh_gia` được tạo với `ENGINE=MyISAM`. MyISAM không hỗ trợ ràng buộc Foreign Key và không hỗ trợ Transactions (ACID). Khi bài đánh giá gốc bị xóa, các bản ghi báo cáo vi phạm trỏ tới nó không bị cascade hoặc cảnh báo, tạo ra **9 bản ghi mồ côi (Orphan records)** trỏ tới `ma_danh_gia` không tồn tại.

#### Các bước tái hiện:
1. Tạo một đánh giá môn học: `ma_danh_gia = 6`.
2. Gửi báo cáo vi phạm cho đánh giá này (bảng `bao_cao_danh_gia` tạo bản ghi liên kết tới `ma_danh_gia = 6`).
3. Xóa vĩnh viễn bài đánh giá ID = 6 khỏi bảng `danh_gia_mon_hoc`.
4. Thực hiện truy vấn kiểm tra dữ liệu mồ côi:
   ```sql
   SELECT b.id, b.ma_danh_gia 
   FROM bao_cao_danh_gia b 
   LEFT JOIN danh_gia_mon_hoc d ON b.ma_danh_gia = d.id 
   WHERE d.id IS NULL;
   ```

#### Kết quả thực tế:
- Truy vấn trả về 9 bản ghi mồ côi. Các kiểm duyệt viên khi mở hàng đợi duyệt báo cáo vi phạm sẽ gặp lỗi `Trying to get property of non-object` khi truy cập `$report->danhGia->noi_dung`.

#### Kết quả mong đợi:
- CSDL phải sử dụng chuẩn `InnoDB`, có Foreign Key Constraint kèm `ON DELETE CASCADE` để khi bài viết bị xóa thì các báo cáo tự động được dọn dẹp hoặc đánh dấu xử lý.

#### Đề xuất khắc phục:
1. Chuyển đổi Storage Engine của bảng sang `InnoDB`:
   ```sql
   ALTER TABLE bao_cao_danh_gia ENGINE = InnoDB;
   ```
2. Dọn dẹp các bản ghi mồ côi hiện tại:
   ```sql
   DELETE FROM bao_cao_danh_gia WHERE ma_danh_gia NOT IN (SELECT id FROM danh_gia_mon_hoc);
   ```
3. Bổ sung khóa ngoại trong Migration:
   ```php
   $table->foreign('ma_danh_gia')->references('id')->on('danh_gia_mon_hoc')->onDelete('cascade');
   ```

---

### BUG-DOC-04: Thiếu validation Enum `phan_loai` trong `StoreDocumentRequest` gây sập hệ thống (HTTP 500)
- **Mã lỗi:** `BUG-DOC-04`
- **Mức độ nghiêm trọng:** `HIGH`
- **Độ ưu tiên xử lý:** `P2`
- **Phân hệ:** Quản lý tài liệu / Xác thực đầu vào (Input Validation)
- **Tệp mã nguồn liên quan:** `app/Http/Requests/Documents/StoreDocumentRequest.php`

#### Tóm tắt lỗi:
Trường `phan_loai` trong bảng CSDL `tai_lieu` là một cột `ENUM('DE_THI', 'BAI_GIANG', 'DO_AN', 'BAI_TAP', 'KHAC')`. Tuy nhiên, trong `StoreDocumentRequest`, trường này chỉ được khai báo là `'string', 'max:50'`. Khi người dùng gửi một giá trị ngoài enum (ví dụ `phan_loai="DE_CUONG"` hoặc bất kỳ chuỗi ngẫu nhiên nào), MySQL strict mode ném ra lỗi `SQLSTATE[01000]: Warning: 1265 Data truncated for column 'phan_loai'`, khiến server trả về mã lỗi `HTTP 500 Internal Server Error`.

#### Các bước tái hiện:
1. Gửi request `POST /tai-lieu/dang-tai` kèm tệp tin hợp lệ.
2. Đặt giá trị trường `phan_loai="DE_CUONG"`.
3. Quan sát phản hồi từ máy chủ.

#### Kết quả thực tế:
- Server phản hồi `HTTP 500 Internal Server Error` với thông báo ngoại lệ CSDL chưa được xử lý.

#### Kết quả mong đợi:
- Hệ thống trả về `HTTP 422 Unprocessable Entity` với thông báo lỗi rõ ràng: *"Phân loại tài liệu đã chọn không hợp lệ."*

#### Đề xuất khắc phục:
Cập nhật rule trong `StoreDocumentRequest.php`:
```php
use Illuminate\Validation\Rule;

'phan_loai' => [
    'nullable',
    'string',
    Rule::in(['DE_THI', 'BAI_GIANG', 'DO_AN', 'BAI_TAP', 'KHAC'])
],
```

---

### BUG-DOC-02: Cơ chế lọc trùng Lớp 2 (Similarity Detection >85%) chưa được tích hợp vào tiến trình Upload
- **Mã lỗi:** `BUG-DOC-02`
- **Mức độ nghiêm trọng:** `HIGH`
- **Độ ưu tiên xử lý:** `P2`
- **Phân hệ:** Vòng đời tài liệu / Xử lý thông minh (AI & Content Deduplication)
- **Tệp mã nguồn liên quan:** `app/Services/Documents/Actions/UploadDocumentAction.php` và `app/Services/Documents/DocumentDeduplicationEvaluator.php`

#### Tóm tắt lỗi:
Tài liệu thiết kế hệ thống quy định cơ chế lọc trùng 2 lớp:
- Lớp 1: Băm SHA-256 (phát hiện tệp tin giống hệt nhau).
- Lớp 2: Trích xuất nội dung văn bản, bỏ qua trang bìa, tính toán độ tương đồng (SimHash / Cosine Similarity $\ge$ 85%) và đề xuất tạo phiên bản mới nếu phát hiện trùng lặp cao.
Thực tế kiểm tra cho thấy: Backend chỉ gọi kiểm tra Lớp 1 (`findExactDuplicateBySha256`). Toàn bộ logic Lớp 2 hoàn toàn không được kích hoạt trong luồng đăng tải, không có Background Queue Job để xử lý bóc tách text, và cột `chu_ky_noi_dung` trong CSDL luôn có giá trị `NULL`.

#### Kết quả thực tế:
- Khi tải lên hai tệp tài liệu có nội dung giống nhau 88% nhưng khác biệt một vài từ (SHA-256 khác nhau), hệ thống vẫn coi đây là 2 tài liệu hoàn toàn độc lập (v1.0), không hiển thị bất kỳ cảnh báo nào và không gợi ý hợp nhất phiên bản.

#### Đề xuất khắc phục:
1. Tạo một Job `ExtractDocumentContentAndCheckSimilarity` đưa vào Queue sau khi tài liệu được lưu tạm.
2. Hoặc bổ sung API kiểm tra độ tương đồng trước khi cho phép người dùng hoàn tất bước xuất bản tài liệu.

---

### BUG-SEC-02: Cho phép người dùng tải lên tệp tin rỗng 0 bytes không có nội dung
- **Mã lỗi:** `BUG-SEC-02`
- **Mức độ nghiêm trọng:** `MEDIUM`
- **Độ ưu tiên xử lý:** `P3`
- **Phân hệ:** Quản lý tài liệu / Xác thực đầu vào
- **Tệp mã nguồn liên quan:** `app/Http/Requests/Documents/StoreDocumentRequest.php`

#### Tóm tắt lỗi:
Hệ thống cho phép người dùng tải lên các tệp tin có dung lượng 0 bytes (`fixture_empty_0byte.txt`). Các tệp này được lưu trữ thành công và hiển thị trên giao diện, nhưng khi người dùng khác tải về hoặc xem trước (preview) thì hoàn toàn không có nội dung hoặc gây lỗi hiển thị trình đọc PDF.

#### Đề xuất khắc phục:
Thêm rule `min:1` vào trường `file` tại `StoreDocumentRequest.php`:
```php
'file' => ['required', 'file', 'min:1', 'max:51200'],
```

---

### BUG-DOC-01: Không cộng điểm đóng góp và không gửi thông báo khi upload tài liệu trực tiếp
- **Mã lỗi:** `BUG-DOC-01`
- **Mức độ nghiêm trọng:** `MEDIUM`
- **Độ ưu tiên xử lý:** `P3`
- **Phân hệ:** Vòng đời tài liệu / Gamification
- **Tệp mã nguồn liên quan:** `app/Services/Documents/Actions/UploadDocumentAction.php`

#### Tóm tắt lỗi:
Khi người dùng tải lên tài liệu học tập thành công thông qua `UploadDocumentAction`, hành động này chỉ tạo bản ghi `TaiLieu` mà không kích hoạt gọi dịch vụ tính điểm uy tín (`ReputationService`), dẫn đến việc người dùng không nhận được điểm thưởng cống hiến tri thức theo thiết kế Gamification. Đồng thời, không có thông báo xác nhận tải lên thành công được lưu vào bảng `thong_bao`.

#### Đề xuất khắc phục:
Tích hợp `ReputationService` và sự kiện `DocumentUploadedEvent` bên trong Transaction của `UploadDocumentAction`:
```php
app(\App\Services\Reputation\ReputationService::class)->congDiemDongGopTaiLieu($nguoiDung, 5);
$nguoiDung->notify(new \App\Notifications\TaiLieuTaiLenThanhCongNotification($taiLieu));
```
