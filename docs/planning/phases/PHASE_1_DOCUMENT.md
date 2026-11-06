# PHASE 1: DOCUMENT EXPERIENCE (VÒNG ĐỜI TÀI LIỆU HỌC TẬP)
## AGU Academic Hub — Implementation Phase 1 Specification

---

| Thông Tin Phase | Chi Tiết |
| :--- | :--- |
| **Giai Đoạn** | Phase 1: Document Experience |
| **Mục Tiêu** | Tách bạch hoàn toàn màn hình Đăng tải (`Create`) và Chi tiết (`Show`), tích hợp luồng xem trước PDF, tải file an toàn, upvote AJAX và báo cáo vi phạm |
| **Số Lượng Task** | 5 Tasks (**TSK-101**, **TSK-102**, **TSK-103**, **TSK-104**, **TSK-105**) |
| **Tổng Thời Gian Ước Lượng** | **17.0 Giờ** |
| **Trọng Tâm** | Tương tác tài liệu học thuật, Pipeline băm SHA-256, Viewer PDF, Phân trang |

---

### TASK TSK-101: Xây Dựng Trang Chi Tiết Tài Liệu (`Documents/Show.jsx`)

1. **Task ID:** `TSK-101`
2. **Module:** M03 (Document)
3. **Mục tiêu:** Tạo mới view `resources/js/Pages/Documents/Show.jsx` để hiển thị toàn diện thông tin một tài liệu học tập, bao gồm metadata, thông tin môn học, tác giả đăng tải, thống kê lượt xem/tải/thích và các nút hành động.
4. **Hiện trạng:**
   - Tuyến `GET /tai-lieu/{id}` (`documents.show`) gọi `DocumentController@show`.
   - Controller trả về: `Inertia::render('Documents/Index', ['document' => $document])`.
   - File `Documents/Index.jsx` hoàn toàn không có mã nguồn hiển thị tài liệu đơn lẻ, dẫn đến việc render một màn hình danh sách trống rỗng!
5. **File cần đọc:**
   - `app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php`
   - `app/Modules/Documents/Application/Queries/GetDocumentDetailQuery.php`
   - `resources/js/Pages/Documents/Index.jsx`
   - `resources/js/Components/domain/DocumentCard.jsx`
6. **File cần sửa hoặc tạo:**
   - Sửa: `app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php` (trỏ `show()` sang view `Documents/Show`)
   - Tạo mới: `resources/js/Pages/Documents/Show.jsx`
7. **Backend dependency:** `GetDocumentDetailQuery` (đã có sẵn, query đầy đủ `monHoc`, `nguoiDang`).
8. **Data Contract:**
   - *Props nhận từ Backend (`Documents/Show`):*
     ```json
     {
       "document": {
         "ma_tai_lieu": 105,
         "tieu_de": "Đề thi giữa kỳ môn Cấu trúc dữ liệu 2024",
         "mo_ta": "Đề thi tự luận kèm đáp án gợi ý",
         "phan_loai": "DE_THI",
         "phien_ban": "1.0",
         "dinh_dang_tep": "pdf",
         "dung_luong_kb": 2048,
         "luot_tai": 15,
         "luot_thich": 8,
         "created_at": "2024-10-15T08:30:00Z",
         "mon_hoc": {
           "ma_mon_hoc": 12,
           "ma_hoc_phan": "COS201",
           "ten_mon_hoc": "Cấu trúc dữ liệu và giải thuật"
         },
         "nguoi_dang": {
           "ma_nguoi_dung": 34,
           "ho_ten": "Trần Thị B",
           "mssv": "B2100567"
         }
       }
     }
     ```
9. **Các bước triển khai:**
   - *Bước 1:* Trong `DocumentController.php` method `show()`, sửa lệnh render:
     ```php
     return Inertia::render('Documents/Show', [
         'document' => $document,
     ]);
     ```
   - *Bước 2:* Tạo file `resources/js/Pages/Documents/Show.jsx` bọc trong `StudentLayout`.
   - *Bước 3:* Thiết kế khối Breadcrumbs điều hướng: Trang chủ $\to$ Môn học $\to$ Chi tiết tài liệu.
   - *Bước 4:* Thiết kế cột trái (Nội dung chính): Tiêu đề, Badge phân loại, mô tả chi tiết, khung nhúng xem trước tài liệu (kết nối với TSK-103).
   - *Bước 5:* Thiết kế cột phải (Sidebar thông tin): Thông tin môn học (Mã HP, Số tín chỉ), thông tin người đăng (Họ tên, ngày đăng), số lượt tải, điểm tín nhiệm (upvotes), nút Tải file về máy, nút Upvote, nút Báo cáo vi phạm.
10. **Acceptance Criteria (AC):**
    - [ ] Truy cập `GET /tai-lieu/{id}` render thành công trang `Documents/Show.jsx` với giao diện chuyên nghiệp.
    - [ ] Hiển thị chính xác toàn bộ metadata của tài liệu từ CSDL.
    - [ ] Thẻ "Sở hữu cộng đồng" xuất hiện nếu tài liệu có $\ge 10$ lượt tải.
11. **Test Cases:**
    - `TC-DOC-04`: Click vào thẻ tài liệu trên trang danh sách $\to$ chuyển hướng chính xác vào trang chi tiết tương ứng.
12. **Priority:** **`P1` (High)**
13. **Dependency Task:** `TSK-001` (để build an toàn).
14. **Estimated Effort:** **4.0 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Tài liệu bị xóa mềm hoặc đang ở trạng thái `TAM_AN_KIEM_TRA` có thể bị người dùng gõ URL truy cập trực tiếp.
    - *Giải pháp:* `GetDocumentDetailQuery` đã có rào chắn `abort(404)` nếu tài liệu không hợp lệ.

---

### TASK TSK-102: Xây Dựng Trang Đăng Tải Tài Liệu (`Documents/Create.jsx`)

1. **Task ID:** `TSK-102`
2. **Module:** M03 (Document)
3. **Mục tiêu:** Tạo mới view `resources/js/Pages/Documents/Create.jsx` cung cấp biểu mẫu tải lên tài liệu học tập hoàn chỉnh, tích hợp kiểm tra mã băm SHA-256 tầng 1 chống trùng lặp file ngay từ giao diện.
4. **Hiện trạng:**
   - Tuyến `GET /tai-lieu/dang-tai` (`documents.create`) gọi `DocumentController@create`.
   - Controller trả về: `Inertia::render('Documents/Index', ['faculties' => $faculties, 'isUploadMode' => true])`.
   - `Documents/Index.jsx` hoàn toàn không có form upload, khiến người dùng bấm vào "Đăng tải tài liệu" chỉ thấy màn hình danh sách trống.
5. **File cần đọc:**
   - `app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php`
   - `app/Modules/Documents/Application/Actions/UploadDocumentAction.php`
   - `app/Modules/Documents/Application/Queries/CheckDuplicateDocumentQuery.php`
   - `resources/js/Layouts/StudentLayout.jsx`
6. **File cần sửa hoặc tạo:**
   - Sửa: `app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php` (sửa method `create()` trỏ tới `Documents/Create`)
   - Tạo mới: `resources/js/Pages/Documents/Create.jsx`
7. **Backend dependency:**
   - `UploadDocumentAction` (`POST /tai-lieu/dang-tai`).
   - `CheckDuplicateDocumentQuery` (`POST /tai-lieu/kiem-tra-trung-lap`).
8. **Data Contract:**
   - *Props nhận từ Backend (`Documents/Create`):*
     ```json
     {
       "faculties": [
         {
           "ma_khoa": 1,
           "ten_khoa": "Công nghệ thông tin",
           "mon_hocs": [
             { "ma_mon_hoc": 10, "ma_hoc_phan": "COS101", "ten_mon_hoc": "Lập trình C" }
           ]
         }
       ]
     }
     ```
   - *Payload gửi lên server khi submit form:*
     `FormData: { ma_mon_hoc: int, tieu_de: string, mo_ta: string, phan_loai: string, file: BinaryFile }`
9. **Các bước triển khai:**
   - *Bước 1:* Sửa `DocumentController@create`:
     ```php
     public function create(): InertiaResponse
     {
         $faculties = Khoa::with('monHocs')->get();
         return Inertia::render('Documents/Create', [
             'faculties' => $faculties,
         ]);
     }
     ```
   - *Bước 2:* Tạo file `resources/js/Pages/Documents/Create.jsx` sử dụng `StudentLayout`.
   - *Bước 3:* Xây dựng Form với Inertia `useForm`:
     - Select chọn Khoa $\to$ tự động lọc danh sách Môn học tương ứng.
     - Input tiêu đề tài liệu (bắt buộc, max 255 ký tự).
     - Select phân loại: `DE_THI` (Đề thi), `BAI_GIANG` (Bài giảng), `DO_AN` (Đồ án), `BAI_TAP` (Bài tập), `KHAC`.
     - Textarea mô tả tài liệu (tối đa 5000 ký tự).
     - Vùng kéo thả file (Dropzone): hỗ trợ `.pdf`, `.docx`, `.xlsx`, `.pptx`, `.zip`, dung lượng tối đa 50MB.
   - *Bước 4:* Tích hợp tính mã băm SHA-256 trên browser bằng Web Crypto API (`crypto.subtle.digest('SHA-256', buffer)`) khi người dùng chọn file $\to$ tự động gọi `POST /tai-lieu/kiem-tra-trung-lap` để cảnh báo sớm nếu file đã tồn tại trong môn học đó trước khi người dùng bấm Đăng tải.
   - *Bước 5:* Xử lý thanh tiến trình upload (`progress`) của Inertia và thông báo thành công.
10. **Acceptance Criteria (AC):**
    - [ ] Bấm nút "Đăng tải tài liệu" trên Header/Sidebar mở đúng trang `Documents/Create.jsx`.
    - [ ] Chọn Khoa lọc chính xác danh sách Môn học tương ứng.
    - [ ] Kiểm tra trùng lặp SHA-256 cảnh báo tức thì nếu file đã có trên hệ thống trong cùng môn học.
    - [ ] Upload file hợp lệ chuyển hướng thành công sang trang chi tiết của tài liệu mới.
11. **Test Cases:**
    - `TC-DOC-01`: Mở form upload, kiểm tra đầy đủ các input và select box.
    - `TC-DOC-02`: Thử chọn file trùng $\to$ hệ thống xuất hiện thông báo trùng lặp.
    - `TC-DOC-03`: Tải lên file thành công $\to$ chuyển hướng tới `Documents/Show`.
12. **Priority:** **`P1` (High)**
13. **Dependency Task:** Không có (Bắt đầu được ngay).
14. **Estimated Effort:** **4.5 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Tính mã băm file dung lượng lớn (>30MB) trên trình duyệt có thể gây đơ giao diện nhẹ.
    - *Giải pháp:* Chạy Web Crypto API trong Web Worker hoặc tính toán theo luồng không đồng bộ (`async/await`) kèm biểu tượng loading tinh tế.

---

### TASK TSK-103: Tích Hợp PDF In-Browser Preview & Safe Download Stream

1. **Task ID:** `TSK-103`
2. **Module:** M03 (Document)
3. **Mục tiêu:** Nhúng trình xem trước PDF trực tuyến mượt mà ngay trên trang chi tiết tài liệu và cung cấp nút bấm tải file an toàn có tự động kích hoạt bộ đếm lượt tải.
4. **Hiện trạng:**
   - Backend đã có sẵn 2 endpoints:
     - `GET /tai-lieu/{id}/xem-truoc` (`DocumentController@preview`) trả về Binary PDF Stream với header `Content-Disposition: inline`.
     - `GET /tai-lieu/{id}/tai-ve` (`DocumentController@download`) tự động tăng `luot_tai += 1` và trả về `BinaryFileResponse`.
   - Tuy nhiên ở tầng Frontend, người dùng không có nút bấm nào để gọi 2 endpoint này.
5. **File cần đọc:**
   - `app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php`
   - `app/Modules/Documents/Application/Actions/PreviewDocumentAction.php`
   - `app/Modules/Documents/Application/Actions/DownloadDocumentAction.php`
   - `resources/js/Pages/Documents/Show.jsx` (được tạo ở TSK-101)
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Documents/Show.jsx`
   - Tạo mới: `resources/js/Components/Documents/DocumentPreviewModal.jsx` (hoặc nhúng iframe trực tiếp)
7. **Backend dependency:** `DocumentController@preview` và `@download` (hoàn thiện 100%).
8. **Data Contract:**
   - Endpoint preview: `route('documents.preview', document.ma_tai_lieu)`.
   - Endpoint download: `route('documents.download', document.ma_tai_lieu)`.
9. **Các bước triển khai:**
   - *Bước 1:* Trên trang `Documents/Show.jsx`:
     - Nếu định dạng tệp là `pdf`: Nhúng một khung xem trước iframe trực tiếp trỏ tới `route('documents.preview', document.ma_tai_lieu)` kèm nút "Mở rộng toàn màn hình".
     - Nếu định dạng tệp là Word/Excel/Khác: Hiển thị icon tệp lớn kèm thông báo "Tệp này không hỗ trợ xem trước trực tuyến, vui lòng tải về máy".
   - *Bước 2:* Thêm nút "Tải tài liệu về máy" (Icon Download, màu xanh nổi bật):
     - Gắn liên kết trực tiếp `href={route('documents.download', document.ma_tai_lieu)}`.
     - Thêm sự kiện click để cập nhật lạc quan (Optimistic UI) số đếm `luot_tai` tăng thêm 1 đơn vị trên giao diện.
10. **Acceptance Criteria (AC):**
    - [ ] File PDF hiển thị trực tuyến rõ nét trong khung xem trước, người dùng đọc được nội dung mà không cần tải về.
    - [ ] Bấm nút Tải về kích hoạt download file thật từ server, lượt tải tự động tăng lên trên CSDL và giao diện.
11. **Test Cases:**
    - `TC-DOC-05`: Bấm xem trước PDF trên trang chi tiết $\to$ hiển thị nội dung tài liệu.
    - `TC-DOC-06`: Bấm tải về $\to$ file được tải về máy và `luot_tai` tăng.
12. **Priority:** **`P1` (High)**
13. **Dependency Task:** `TSK-101`.
14. **Estimated Effort:** **3.0 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Trình duyệt Safari hoặc thiết bị di động có thể tự động tải file thay vì render iframe PDF.
    - *Giải pháp:* Cung cấp nút fallback rõ ràng: "Xem PDF trên tab mới" mở trực tiếp link preview với `target="_blank"`.

---

### TASK TSK-104: Tích Hợp Phân Trang & Upvote AJAX Trên Thẻ Tài Liệu

1. **Task ID:** `TSK-104`
2. **Module:** M03 (Document)
3. **Mục tiêu:** Bổ sung thanh phân trang (Pagination) vào cuối trang danh sách `Documents/Index.jsx` và kích hoạt nút bấm Upvote tương tác thời gian thực trên component `DocumentCard.jsx`.
4. **Hiện trạng:**
   - Trang `Documents/Index.jsx` nhận danh sách `documents` từ backend (phân trang 12 mục) nhưng không render component phân trang, khiến người dùng không thể xem các trang tiếp theo.
   - Thẻ `DocumentCard.jsx` chỉ hiển thị số lượt thích tĩnh (`document.luot_thich || 0`), không có nút bấm hay handler để gọi API upvote.
5. **File cần đọc:**
   - `resources/js/Pages/Documents/Index.jsx`
   - `resources/js/Components/domain/DocumentCard.jsx`
   - `resources/js/Components/Common/Pagination.jsx`
   - `app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php:124`
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Documents/Index.jsx`
   - Sửa: `resources/js/Components/domain/DocumentCard.jsx`
7. **Backend dependency:** `DocumentController@upvote` (`POST /tai-lieu/{id}/binh-chon`).
8. **Data Contract:**
   - *Upvote API Response:* `{ success: true, upvotes: 9 }`.
   - *Pagination prop:* `documents.links` (mảng các link phân trang Laravel).
9. **Các bước triển khai:**
   - *Bước 1:* Trong `resources/js/Pages/Documents/Index.jsx`:
     - Import `Pagination` từ `@/Components/Common/Pagination`.
     - Chèn `<Pagination links={documents.links} className="mt-6" />` ngay bên dưới lưới tài liệu.
   - *Bước 2:* Trong `resources/js/Components/domain/DocumentCard.jsx`:
     - Chuyển đổi khối hiển thị Upvote thành thẻ `<button>` tương tác.
     - Viết hàm `handleToggleUpvote`: gửi request `router.post(route('documents.upvote', document.ma_tai_lieu))` hoặc `fetch` ngầm để cập nhật state số lượt thích cục bộ.
     - Hiển thị hiệu ứng đổi màu nút thích khi người dùng tương tác.
10. **Acceptance Criteria (AC):**
    - [ ] Cuối trang danh sách tài liệu hiển thị đầy đủ các nút chuyển trang (Trang 1, 2, 3... Kế tiếp). Bấm chuyển trang mượt mà.
    - [ ] Bấm nút Upvote trên bất kỳ thẻ tài liệu nào, số lượt upvote tăng ngay lập tức mà không reload toàn trang.
11. **Test Cases:**
    - `TC-DOC-07`: Bấm Upvote tài liệu $\to$ số đếm tăng, F5 lại trang số đếm vẫn giữ nguyên.
    - `TC-CTR-05`: Chuyển sang trang 2 của danh sách môn học $\to$ danh sách tài liệu mới được nạp chính xác.
12. **Priority:** **`P1` (High)**
13. **Dependency Task:** `TSK-101`.
14. **Estimated Effort:** **2.5 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Người dùng bấm liên tục nhiều lần vào nút Upvote (Spam click).
    - *Giải pháp:* Thêm cờ `isVoting` vô hiệu hóa nút tạm thời trong 500ms khi request đang gửi đi.

---

### TASK TSK-105: Xây Dựng Modal Báo Cáo Vi Phạm Tài Liệu

1. **Task ID:** `TSK-105`
2. **Module:** M03 / M10 (Document / Moderation)
3. **Mục tiêu:** Tạo modal `DocumentReportModal.jsx` kết nối với API backend để sinh viên và cựu sinh viên có thể báo cáo tài liệu vi phạm bản quyền, nội dung rác hoặc sai danh mục môn học.
4. **Hiện trạng:**
   - Backend đã có sẵn action `SubmitDocumentReportAction` và route `POST /bao-cao/tai-lieu` (`reports.document.store`).
   - Tầng Frontend hoàn toàn chưa có bất kỳ giao diện nào để người dùng gửi báo cáo vi phạm.
5. **File cần đọc:**
   - `app/Modules/Moderation/Presentation/Http/Controllers/DocumentReportController.php`
   - `app/Modules/Moderation/Application/Actions/SubmitDocumentReportAction.php`
   - `resources/js/Pages/Documents/Show.jsx`
6. **File cần sửa hoặc tạo:**
   - Tạo mới: `resources/js/Components/Documents/DocumentReportModal.jsx`
   - Sửa: `resources/js/Pages/Documents/Show.jsx` (nhúng nút mở modal báo cáo)
7. **Backend dependency:** `DocumentReportController@store`.
8. **Data Contract:**
   - *Payload gửi lên:*
     ```json
     {
       "ma_tai_lieu": 105,
       "ly_do": "VI_PHAM_BAN_QUYEN", // hoặc NOI_DUNG_DOC_HAI, SAI_MON_HOC, KHAC
       "mo_ta_chi_tiet": "Tài liệu này chứa nội dung riêng tư của giảng viên..."
     }
     ```
9. **Các bước triển khai:**
   - *Bước 1:* Tạo `resources/js/Components/Documents/DocumentReportModal.jsx`.
   - *Bước 2:* Dựng giao diện form báo cáo gồm:
     - Radio chọn lý do vi phạm định sẵn.
     - Textarea giải thích chi tiết lý do báo cáo.
     - Cảnh báo học thuật: "Báo cáo sai sự thật có thể làm giảm điểm uy tín của bạn".
   - *Bước 3:* Kết nối gửi form bằng `useForm` của Inertia tới `route('reports.document.store')`.
   - *Bước 4:* Đóng modal và hiển thị Toast thông báo thành công: "Báo cáo của bạn đã được gửi tới Ban Kiểm duyệt".
10. **Acceptance Criteria (AC):**
    - [ ] Bấm nút cờ báo cáo trên trang chi tiết tài liệu mở ra modal báo cáo.
    - [ ] Gửi báo cáo thành công, bản ghi được lưu vào bảng `bao_cao_vi_pham` của CSDL.
    - [ ] Nếu tài liệu nhận đủ 5 báo cáo hợp lệ, tài liệu tự động chuyển trạng thái `TAM_AN_KIEM_TRA`.
11. **Test Cases:**
    - `TC-DOC-08`: Gửi báo cáo vi phạm thành công $\to$ modal đóng, hiển thị flash message.
12. **Priority:** **`P2` (Medium)**
13. **Dependency Task:** `TSK-101`.
14. **Estimated Effort:** **3.0 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Người dùng tự báo cáo tài liệu của chính mình.
    - *Giải pháp:* Ẩn nút báo cáo nếu `document.ma_nguoi_dang === auth.user.ma_nguoi_dung`.

