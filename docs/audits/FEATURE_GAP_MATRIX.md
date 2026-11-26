# MA TRẬN ĐỐI CHIẾU KHOẢNG TRỐNG TÍNH NĂNG (FEATURE GAP MATRIX)
## AGU Academic Hub — Phân Hệ Sinh Viên (`SINH_VIEN`) & Cựu Sinh Viên (`CUU_SINH_VIEN`)
**Thời điểm lập:** 2026-11-06  
**Phiên bản:** 2.0 (Chuẩn hóa toàn diện theo phản biện kỹ thuật và xác minh Database Schema)  
**Mục tiêu:** Đối soát toàn diện giữa Giao diện người dùng (Frontend UI), Nghiệp vụ máy chủ (Backend), Liên thông tuyến đường (Route Integration), Tính đúng đắn nghiệp vụ (Business Completeness) và Kiểm chứng tại thời điểm chạy (Runtime Verification).

---

## 1. NGUYÊN TẮC VÀ TIÊU CHÍ PHÂN LOẠI CHUẨN HÓA

Để loại bỏ hoàn toàn hiện tượng "đánh dấu ảo" (chỉ kiểm tra route trả HTTP 200 hoặc component React tồn tại đã coi là xong), ma trận áp dụng 5 cấp độ kiểm chứng nghiêm ngặt:

1. **`UI Completeness` (Tính hoàn thiện giao diện):** Component tồn tại, đúng thiết kế Design System, hiển thị đủ các trường thông tin cần thiết, không có lỗi render rác/đè empty state.
2. **`Backend Completeness` (Tính hoàn thiện máy chủ):** Controller, Query, Action, Repository, Form Request và Migration CSDL đáp ứng đầy đủ yêu cầu truy vấn/lưu trữ dữ liệu.
3. **`Integration Completeness` (Tính liên thông người dùng):** Luồng người dùng từ trang này sang trang khác liền mạch, truyền đúng tham số (query params, route params), không bị nuốt prop hoặc rơi vào trang sai mục đích.
4. **`Business Completeness` (Tính toàn vẹn nghiệp vụ):** Kiểm tra validation đầy đủ 2 đầu (Client & Server), bảo toàn tính toàn vẹn dữ liệu, kiểm tra quyền và vai trò (Sinh viên vs Cựu sinh viên), dọn dẹp file rác khi có lỗi.
5. **`Runtime Verification` (Kiểm chứng thực tế):** Đã chạy thực tế trên môi trường ứng dụng và xác nhận bằng bằng chứng mã nguồn cụ thể.

### Định nghĩa các trạng thái phân loại:
- **`FULLY_IMPLEMENTED`:** Đạt 100% cả 5 tiêu chí trên. Luồng độc lập hoặc luồng liên thông đã hoàn thành và kiểm chứng không có lỗi.
- **`PARTIALLY_IMPLEMENTED`:** Đã có UI và Backend nhưng còn thiếu mắt xích nghiệp vụ, thiếu tham số tích hợp, hoặc chưa liên thông trọn vẹn luồng người dùng.
- **`BROKEN`:** Có giao diện và endpoint nhưng chạy sai logic hiển thị (như empty state vô điều kiện), truyền nhầm view hoặc trỏ sai chức năng gây phá vỡ trải nghiệm người dùng.
- **`UI_ONLY`:** Chỉ có giao diện tóm tắt hoặc component tĩnh, chưa có trang chi tiết hoặc thiếu kết nối backend tương ứng.
- **`BACKEND_ONLY`:** Backend (API, Query, Action, DB) đã sẵn sàng nhưng Frontend chưa có màn hình/giao diện nào kết nối tới.
- **`NOT_IMPLEMENTED`:** Chưa có cả giao diện lẫn xử lý nghiệp vụ liên quan.
- **`NEEDS_VERIFICATION`:** Chưa đủ bằng chứng mã nguồn hoặc runtime để đưa ra kết luận.

---

## 2. MA TRẬN CHI TIẾT 33 TÍNH NĂNG (33 FEATURES GAP MATRIX)

| Mã | Tên Tính Năng (Feature) | Thành Phần UI (Frontend) | Xử Lý Backend | Tuyến Đường (Route) | Tính Hoàn Thiện Tích Hợp (Integration) | Runtime | Trạng Thái Phân Loại | Khối Lượng Công Việc Còn Thiếu & Giải Pháp |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :---: | :--- |
| **F-01** | Danh sách Khoa đào tạo | `Pages/Faculties/Index.jsx` | `FacultyController@index`, `GetPublicFacultiesQuery` | `GET /khoa` (`faculties.index`) | **Bị đứt đoạn:** Nút "Xem môn học" không truyền `ma_khoa` | Có | `PARTIALLY_IMPLEMENTED` | [Đã xác minh] Cần sửa link "Xem môn học" thành `route('courses.index', { ma_khoa: faculty.ma_khoa })`. Bổ sung modal hoặc accordion hiển thị danh sách Chuyên ngành trực thuộc. |
| **F-02** | Xem Chuyên ngành theo Khoa | *Chưa có component* | `FacultyController@layDanhSachMonHocTheoChuyenNganh` | `GET /khoa/chuyen-nganh/{id}/mon-hoc` | **Chưa tích hợp:** FE chưa gọi API | Chưa | `BACKEND_ONLY` | [Đã xác minh] Backend có API nhưng người dùng không có giao diện xem các ngành trực thuộc khoa. Cần tích hợp hiển thị chuyên ngành vào trang Khoa (`Faculties/Index.jsx`). |
| **F-03** | Tra cứu danh mục Môn học | `Pages/Documents/Index.jsx` *(nhận nhầm)* | `CourseController@index`, `getPaginatedCourses` | `GET /mon-hoc` (`courses.index`) | **Gãy hoàn toàn:** View bỏ qua prop `courses`, chỉ render tài liệu | Có | `BROKEN` | [Đã xác minh] `CourseController@index` truyền `courses` nhưng `Documents/Index.jsx` nuốt mất. Cần tạo trang chuyên biệt `Pages/Courses/Index.jsx` để hiển thị danh mục môn học. |
| **F-04** | Tìm kiếm môn theo Mã/Tên | Ô search trên `Documents/Index.jsx` | `DocumentRepository@paginatePublicDocuments` | `GET /mon-hoc?search=...` | **Sai trách nhiệm nghiệp vụ:** Gọi nhầm search tài liệu | Có | `BROKEN` | [Đã xác minh] Search môn học phải gọi `AcademicCatalogRepository::getPublicPaginatedCourses` (đã có logic tìm `ma_hoc_phan`, `ten_mon_hoc`). Cần trả về đúng trang môn học. |
| **F-05** | Chi tiết môn học | `Pages/Documents/Index.jsx` *(chỉ có banner tóm tắt)* | `CourseController@show`, `findActiveCourseByCode` | `GET /mon-hoc/{course_code}` (`courses.show`) | **Bị gộp tạm:** Thiếu trang chi tiết môn học chuyên biệt | Có | `UI_ONLY` | [Đã xác minh] Cần tạo mới `Pages/Courses/Show.jsx` hiển thị đầy đủ: Số tín chỉ, Khoa chủ quản, Chuyên ngành áp dụng, Mô tả môn học và các tab chức năng. |
| **F-06** | Tabs Môn học: Kho tài liệu | Phần list trên `Documents/Index.jsx` | `CourseController@show`, `DocumentRepository` | `GET /mon-hoc/{code}` | **Chưa có cấu trúc Tab:** Chỉ là trang danh sách tài liệu độc lập | Có | `PARTIALLY_IMPLEMENTED` | [Đã xác minh] Dữ liệu tài liệu theo môn đã lọc đúng, nhưng thiếu vỏ bọc Tab điều hướng trong trang chi tiết môn học để chuyển tiếp với Đánh giá và Bounty. |
| **F-07** | Tabs Môn học: Đánh giá | `Pages/Reviews/CourseReviews.jsx` | `CourseReviewController@index`, `GetCourseReviewsQuery` | `GET /mon-hoc/{code}/danh-gia` | **Độc lập, thiếu liên kết:** Chưa gắn tab từ chi tiết môn | Có | `PARTIALLY_IMPLEMENTED` | [Đã xác minh] Module Review hoạt động rất tốt (rating, bình chọn, thống kê), nhưng người dùng xem môn học không có tab bấm sang review. Cần tích hợp Tab 2 trên `Courses/Show.jsx`. |
| **F-08** | Tabs Môn học: Wishlist môn | *Chưa có tab theo môn* | `WishlistBountyController@index` | `GET /yeu-cau-tai-lieu?ma_mon_hoc=...` | **Chưa tích hợp vào môn học:** Người dùng phải ra chợ Bounty tìm tay | Chưa | `NOT_IMPLEMENTED` | [Đã xác minh] Backend `GetWishlistBountyListQuery` đã hỗ trợ filter `ma_mon_hoc`. Cần tạo Tab 3 trên `Courses/Show.jsx` để hiển thị các yêu cầu tài liệu của riêng môn học đó. |
| **F-09** | Dashboard: Thẻ Tìm môn học | Thẻ 1 `quickLinks` (`Dashboard.jsx`) | `CourseController@index` | `GET /mon-hoc` (`courses.index`) | **Bị ảnh hưởng bởi F-03:** Click mở trang danh sách tài liệu | Có | `BROKEN` | [Đã xác minh] Cần sửa đích đến liên thông với trang danh mục môn học mới `Courses/Index.jsx` (giải quyết triệt để sau khi hoàn thành `TSK-REV-001`). |
| **F-10** | Dashboard: Thẻ Săn thưởng | Thẻ 2 `quickLinks` (`Dashboard.jsx`) | `WishlistBountyController@index` | `GET /yeu-cau-tai-lieu` (`document-requests.index`) | **Hoàn chỉnh:** Điều hướng mượt mà đến chợ Bounty | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Đã hoạt động chính xác cả UI, Backend và điều hướng runtime. |
| **F-11** | Dashboard: Thẻ Duyệt khoa | Thẻ 3 `quickLinks` (`Dashboard.jsx`) | `FacultyController@index` | `GET /khoa` (`faculties.index`) | **Bị ảnh hưởng bởi F-01:** Mở trang khoa nhưng tương tác tiếp bị hụt | Có | `PARTIALLY_IMPLEMENTED` | [Đã xác minh] Mở đúng trang Khoa, nhưng do trang Khoa chưa truyền param sang môn học nên luồng nghiệp vụ bị cụt. Sẽ hoàn thiện sau `TSK-REV-004`. |
| **F-12** | Dashboard: Thẻ Đăng tài liệu | Thẻ 4 `quickLinks` (`Dashboard.jsx`) | `DocumentController@create` | `GET /tai-lieu/dang-tai` (`documents.create`) | **Hoàn chỉnh:** Mở đúng form tải tài liệu | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Điều hướng chuẩn xác. Form mở sẵn sàng tiếp nhận file. |
| **F-13** | Dashboard: Tài liệu mới | Khối `recentDocuments` (`Dashboard.jsx`) | `DashboardController`, `TaiLieu::latest()` | `GET /trang-chu` (`dashboard`) | **Lỗi giao diện nghiêm trọng:** Empty state luôn hiện đè, thẻ không click được | Có | `BROKEN` | [Đã xác minh] `Dashboard.jsx:419-430` render hộp "Chưa có tài liệu" vô điều kiện; các thẻ tài liệu là `<article>` tĩnh không có `<Link>` sang `documents.show`. |
| **F-14** | Dashboard: Danh tính Alumni | Thẻ profile banner (`Dashboard.jsx`) | `auth.user` | `GET /trang-chu` (`dashboard`) | **Chưa đồng bộ danh tính:** Hiển thị sai danh xưng với Cựu sinh viên | Có | `PARTIALLY_IMPLEMENTED` | [Đã xác minh] `Dashboard.jsx:205` ghi cứng "Hồ sơ sinh viên" cho cả user `CUU_SINH_VIEN`. Cần bổ sung logic nhận diện vai trò và fallback khi không có MSSV. |
| **F-15** | Đăng tải: Phân cấp Khoa/Ngành | Form chọn đơn vị (`Documents/Create.jsx`) | `DocumentController@create` | `GET /tai-lieu/dang-tai` (`documents.create`) | **Thiếu cấp trung gian:** Chỉ có chọn Khoa $\to$ Môn học | Có | `PARTIALLY_IMPLEMENTED` | [Đã xác minh] CSDL quan hệ `MonHoc` $\leftrightarrow$ `ChuyenNganh` qua pivot `mon_hoc_chuyen_nganh`. Cần bổ sung chọn Chuyên ngành (tùy chọn) để lọc môn học dễ dàng hơn. |
| **F-16** | Đăng tải: Kéo thả & Chọn file | Dropzone (`Documents/Create.jsx`) | HTML5 Drag & Drop, File API | Không áp dụng (Client UI) | **Hoàn chỉnh:** Kéo thả mượt mà, nhận diện đúng icon | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Hoạt động ổn định trên nhiều trình duyệt, hỗ trợ kéo thả file và click duyệt file từ máy. |
| **F-17** | Đăng tải: Validate Client | `handleFileChange` (`Documents/Create.jsx`) | Không áp dụng (Client-side) | Không áp dụng | **Thiếu chặn sớm:** Hash file lớn gây đơ tab | Có | `PARTIALLY_IMPLEMENTED` | [Đã xác minh] Gọi `computeSha256` bằng `file.arrayBuffer()` mà không kiểm tra kích thước. Cần thêm kiểm tra `file.size <= 50MB` trước khi tính hash. |
| **F-18** | Đăng tải: Băm file SHA-256 | Web Crypto API `subtle.digest` | Không áp dụng (Client-side) | Không áp dụng | **Hoàn chỉnh:** Sinh mã băm 64 ký tự chuẩn xác | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Băm SHA-256 chuẩn mã hóa SHA-2, hiển thị trạng thái hashing và truyền kết quả sang pre-check. |
| **F-19** | Đăng tải: Lọc trùng Lớp 1 | Modal cảnh báo trên `Documents/Create.jsx` | `DocumentController@checkDuplicate`, `CheckDuplicateQuery` | `POST /tai-lieu/kiem-tra-trung-lap` | **Hoàn chỉnh:** Trả HTTP 409 nếu trùng, disable nút nộp | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] AJAX pre-check phản hồi nhanh, chặn người dùng cố nộp file trùng mã băm trong cùng môn học. |
| **F-20** | Đăng tải: Gửi Form Multipart | Inertia `post` form (`Documents/Create.jsx`) | `DocumentController@store`, `UploadDocumentAction` | `POST /tai-lieu/dang-tai` (`documents.store`) | **Hoàn chỉnh:** Tải file, ghi disk, tạo bản ghi DB | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Tải lên thành công, lưu file vào `storage/app/documents`, ghi bản ghi `tai_lieu` và chuyển về `documents.show`. |
| **F-21** | Đăng tải: Validate Server | Form Request / Validator | `DocumentController@store` | `POST /tai-lieu/dang-tai` | **Thiếu bảo vệ MIME & dọn file:** Chỉ validate `max:51200` | Có | `PARTIALLY_IMPLEMENTED` | [Đã xác minh] `DocumentController@store` thiếu `mimes` rule. `UploadDocumentAction` lưu disk trước khi ghi DB, nếu DB fail sẽ để lại file rác trên disk. |
| **F-22** | Xem chi tiết tài liệu | `Pages/Documents/Show.jsx` | `DocumentController@show`, `GetDocumentDetailQuery` | `GET /tai-lieu/{id}` (`documents.show`) | **Hoàn chỉnh:** Metadata, tác giả, ngày đăng, SHA-256 | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Giao diện đầy đủ, hiển thị breadcrumb, phân loại, lượt tải, bản xem trước và quyền tác giả. |
| **F-23** | Xem trước PDF trực tuyến | Khung iframe (`Documents/Show.jsx`) | `DocumentController@preview`, `DocumentFileStorage` | `GET /tai-lieu/{id}/xem-truoc` (`documents.preview`) | **Hoàn chỉnh:** Inline stream với `Content-Disposition: inline` | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Xem trước mượt mà trong trang, phóng to/thu nhỏ, mở tab mới an toàn bằng PDF viewer tích hợp. |
| **F-24** | Tải tệp tài liệu về máy | Nút Download (`Documents/Show.jsx`) | `DocumentController@download`, `DownloadDocumentAction` | `GET /tai-lieu/{id}/tai-ve` (`documents.download`) | **Hoàn chỉnh:** Tải file an toàn, tự động tăng `luot_tai` | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Stream tải file về máy chuẩn header đính kèm, tăng biến đếm `luot_tai` trong database ngay sau khi tải. |
| **F-25** | Bình chọn / Like tài liệu | Nút Upvote AJAX (`DocumentCard`, `Show.jsx`) | `DocumentController@upvote`, `UpvoteDocumentAction` | `POST /tai-lieu/{id}/binh-chon` (`documents.upvote`) | **Hoàn chỉnh:** Toggle trạng thái thích, cập nhật số lượt | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Phản hồi AJAX tức thì, cập nhật số đếm và đổi màu nút like theo trạng thái người dùng. |
| **F-26** | Báo cáo tài liệu vi phạm | `Components/Documents/DocumentReportModal.jsx` | `ViolationReportController@storeDocumentReport` | `POST /bao-cao/tai-lieu` (`reports.document.store`) | **Hoàn chỉnh:** Modal lý do vi phạm, ẩn nút với chính tác giả | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Gửi báo cáo thành công tới hàng đợi Ban Kiểm duyệt, có cơ chế chống spam báo cáo trùng. |
| **F-27** | Xem danh mục Săn thưởng | `Pages/Wishlist/Index.jsx` | `WishlistBountyController@index`, `GetWishlistBountyListQuery` | `GET /yeu-cau-tai-lieu` (`document-requests.index`) | **Hoàn chỉnh:** Lưới yêu cầu, lọc trạng thái, phân trang | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Hiển thị danh sách bài treo thưởng, lọc trạng thái `MO/DONG`, tìm kiếm theo từ khóa. |
| **F-28** | Đăng bài treo thưởng Bounty | `Components/Wishlist/CreateRequestModal.jsx` | `WishlistBountyController@store`, `CreateBountyRequestAction` | `POST /yeu-cau-tai-lieu` (`document-requests.store`) | **Hoàn chỉnh:** Form modal, trừ ví khả dụng, tạm giữ điểm | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Đóng băng điểm thưởng trong ví, ghi bản ghi giao dịch giữ điểm, hiển thị ngay trên bảng tin. |
| **F-29** | Đóng góp tài liệu giải quyết | `Components/Wishlist/FulfillRequestModal.jsx` | `WishlistBountyController@fulfill`, `documents.my-documents` | `POST /yeu-cau-tai-lieu/{id}/dong-gop` | **Hoàn chỉnh:** Picker tài liệu cá nhân, nộp bài thành công | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Cho phép chọn tài liệu cá nhân đã được duyệt để phản hồi cho bài treo thưởng. |
| **F-30** | Nghiệm thu giải ngân Bounty | Nút nghiệm thu (`Wishlist/Show.jsx`) | `WishlistBountyController@confirm`, `ConfirmBountyFulfillmentAction` | `POST /yeu-cau-tai-lieu/{id}/xac-nhan` | **Hoàn chỉnh:** Giải ngân điểm sang người giải, pháo hoa Confetti | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Chuyển điểm thưởng từ quỹ tạm giữ sang ví người đóng góp, cộng điểm uy tín và kích hoạt hiệu ứng Confetti. |
| **F-31** | Xem Ví điểm & Giao dịch | `Pages/Wallet/Index.jsx` | `WalletController@index`, `GetWalletDataQuery` | `GET /vi-diem` (`wallet.index`) | **Hoàn chỉnh:** 4 chỉ số số dư, bảng lịch sử phân trang | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Hiển thị chính xác số dư khả dụng, điểm tạm giữ, điểm uy tín và biến động cộng/trừ điểm minh bạch. |
| **F-32** | Hồ sơ cá nhân học thuật | `Pages/Profile/Edit.jsx` | `ProfileController@edit` | `GET /ho-so` (`profile.edit`) | **Hoàn chỉnh:** Thẻ thông tin học thuật readonly, cập nhật profile | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Tích hợp layout sinh viên, hiển thị MSSV/Khoa, điểm uy tín, số dư ví và form đổi mật khẩu. |
| **F-33** | Thông báo hệ thống | `Components/NotificationDropdown.jsx` | `NotificationController@index`, `markAsRead` | `GET /thong-bao`, `POST /thong-bao/{id}/doc` | **Hoàn chỉnh:** Chuông thông báo trên Header, cập nhật real-time | Có | `FULLY_IMPLEMENTED` | [Đã xác minh] Dropdown thông báo hiển thị danh sách tin mới, đếm số tin chưa đọc và đánh dấu đã đọc mượt mà. |

---

## 3. BẢNG TỔNG HỢP & GIẢI TRÌNH SỰ KHÁC BIỆT THỐNG KÊ

```
┌─────────────────────────────────┬─────────────┬─────────────┐
│ Trạng Thái Phân Loại            │ Số Lượng    │ Tỷ Lệ %     │
├─────────────────────────────────┼─────────────┼─────────────┤
│ FULLY_IMPLEMENTED               │ 18 / 33     │ 54.5 %      │
│ PARTIALLY_IMPLEMENTED           │ 08 / 33     │ 24.2 %      │
│ BROKEN                          │ 04 / 33     │ 12.1 %      │
│ UI_ONLY                         │ 01 / 33     │ 03.0 %      │
│ BACKEND_ONLY                    │ 01 / 33     │ 03.0 %      │
│ NOT_IMPLEMENTED                 │ 01 / 33     │ 03.0 %      │
│ NEEDS_VERIFICATION              │ 00 / 33     │ 00.0 %      │
├─────────────────────────────────┼─────────────┼─────────────┤
│ TỔNG CỘNG                       │ 33 Features │ 100.0 %     │
└─────────────────────────────────┴─────────────┴─────────────┘
```

### Giải trình đồng bộ giữa Báo Cáo Kiểm Toán (`REALITY_AUDIT_REPORT.md`) và Ma Trận:
1. **Lý do báo cáo trước có con số tổng 22:**
   - Trong `REALITY_AUDIT_REPORT.md:17-24`, báo cáo ban đầu đã **gộp nhóm các tính năng nhỏ của cùng một nghiệp vụ** (ví dụ: gộp F-27 đến F-30 thành một nhóm Bounty, gộp F-22 đến F-25 thành nhóm Document Viewer).
   - Trong Ma trận 33 tính năng chuẩn hóa này, toàn bộ **33 tính năng nguyên tử (atomic features)** đã được tách biệt rõ ràng, phân định độc lập từng mắt xích UI, Backend và Integration.
2. **Loại bỏ trạng thái ảo của F-06:**
   - Ban đầu F-06 ("Tabs Môn học: Kho tài liệu") bị đánh dấu nhầm là `FULLY_IMPLEMENTED` do tuyến đường trả về danh sách tài liệu. Tuy nhiên qua kiểm chứng thực tế, `Courses/Show.jsx` hoàn toàn chưa tồn tại, danh sách này chỉ hiển thị đơn lập trên `Documents/Index.jsx` và không có vỏ bọc Tab chuyển tiếp. Do đó, F-06 được điều chỉnh chính xác về **`PARTIALLY_IMPLEMENTED`**.
3. **Phân định rõ ràng giữa Course Search và Document Search:**
   - **Course Search (F-03, F-04):** Tìm kiếm môn học theo Mã học phần và Tên môn thuộc trách nhiệm của `AcademicCatalogRepository::getPublicPaginatedCourses` và trang `Courses/Index.jsx`.
   - **Document Search (F-22, F-06):** Tìm kiếm tài liệu theo Tiêu đề, Mô tả thuộc trách nhiệm của `DocumentRepository::paginatePublicDocuments` và trang `Documents/Index.jsx`. Hai nghiệp vụ này độc lập và không được dùng cái này thay thế cho cái kia.
