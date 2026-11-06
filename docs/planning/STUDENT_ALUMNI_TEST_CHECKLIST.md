# CHECKLIST KIỂM THỬ VÀ NGHIỆM THU PHÂN HỆ SINH VIÊN VÀ CỰU SINH VIÊN
## AGU Academic Hub — Quality Assurance & Acceptance Test Checklist

---

| Thông Tin Kiểm Thử | Chi Tiết |
| :--- | :--- |
| **Dự Án** | AGU Academic Hub — Phân Hệ Sinh Viên & Cựu Sinh Viên |
| **Môi Trường Kiểm Thử** | Local Development & Staging Server (Node 20+ / PHP 8.2+ / MySQL 8.0) |
| **Mục Tiêu Nghiệm Thu** | Đảm bảo chất lượng toàn diện Frontend, không hồi quy lỗi, đáp ứng 100% Data Contract |
| **Tài Liệu Kèm Theo** | Master Plan: [`STUDENT_ALUMNI_IMPLEMENTATION_PLAN.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/planning/STUDENT_ALUMNI_IMPLEMENTATION_PLAN.md) |

---

## 1. NGUYÊN TẮC KIỂM THỬ BẮT BUỘC

> [!IMPORTANT]
> 1. **Không coi việc Backend Test (`php artisan test`) PASS 100% là bằng chứng Frontend đã hoạt động.** Backend chỉ kiểm thử JSON response và HTTP status; rất nhiều lỗi Frontend (như thiếu component, lỗi build Vite, thiếu prop, crash React lifecycle) chỉ bộc lộ khi chạy trên trình duyệt thực tế.
> 2. Mọi tính năng chỉ được đánh dấu **`PASSED`** khi vượt qua đồng thời cả 2 tác nhân: **Sinh viên hiện hành (`SINH_VIEN`)** và **Cựu sinh viên (`CUU_SINH_VIEN`)**.
> 3. Mọi thao tác kiểm thử phải được ghi nhận trên ít nhất 2 độ phân giải: **Desktop (1920x1080 / 1440x900)** và **Mobile (375x812 - iPhone X / 412x915 - Samsung S20)**.

---

## 2. BẢNG CHECKLIST KIỂM THỬ TOÀN DIỆN (COMPREHENSIVE TEST CHECKLIST)

### Nhóm 1: Kiểm Tra Build, Bundle & Môi Trường Runtime (Build & Runtime Stability)

| Mã Test | Hạng Mục Kiểm Thử | Các Bước Thực Hiện | Kết Quả Mong Đợi | Trạng Thái | Người Kiểm | Ghi Chú Nghiệm Thu |
| :---: | :--- | :--- | :--- | :---: | :---: | :--- |
| **TC-BLD-01** | Production Build Assets | Chạy lệnh `npm run build` trên terminal. | Build thành công 100% (Exit code 0), không có cảnh báo Rollup missing import hoặc unresolved dependency. | `PENDING` | - | Chặn đứng bởi lỗi `canvas-confetti` (sẽ pass sau TSK-001) |
| **TC-BLD-02** | Development HMR Server | Chạy lệnh `npm run dev`, mở trình duyệt truy cập app. | Vite Dev Server khởi động dưới 1.5s, Hot Module Replacement (HMR) nhận diện thay đổi file JSX tức thì. | `PENDING` | - | - |
| **TC-BLD-03** | Console Cleanliness | Mở DevTools Console trên mọi trang chính của Sinh viên. | Không có lỗi đỏ (Errors), không có cảnh báo nghiêm trọng về React Key (`Each child in a list should have a unique key`). | `PENDING` | - | - |
| **TC-BLD-04** | Inertia Page Resolution | Lần lượt click điều hướng qua tất cả 15+ route giao diện của hệ thống. | Không phát sinh lỗi `Error: Page not found: ./Pages/...`. Mọi view đều mount thành công vào DOM. | `PENDING` | - | Đặc biệt kiểm tra `/vi-diem`, `/tai-lieu/dang-tai`, `/tai-lieu/{id}` |

---

### Nhóm 2: Kiểm Tra Data Contract Giữa Backend Và Frontend

| Mã Test | Hạng Mục Kiểm Thử | Các Bước Thực Hiện | Kết Quả Mong Đợi | Trạng Thái | Người Kiểm | Ghi Chú Nghiệm Thu |
| :---: | :--- | :--- | :--- | :---: | :---: | :--- |
| **TC-CTR-01** | User Profile Identity | Đăng nhập tài khoản sinh viên, kiểm tra avatar và menu Header. | Hiển thị chính xác tên người dùng (`ho_ten`), MSSV, tên Khoa. Không hiển thị chữ `undefined` hoặc để trống tên. | `PASSED` | 2026-11-06 | Đã khắc phục qua TSK-003 |
| **TC-CTR-02** | Profile Form Uncontrolled | Truy cập trang `/ho-so`. Mở DevTools Console. | Form hiển thị sẵn họ tên và email hiện tại; không có cảnh báo React `uncontrolled input to controlled`. | `PASSED` | 2026-11-06 | Đã khắc phục qua TSK-003 |
| **TC-CTR-03** | Sidebar Faculty Tree Props | Kiểm tra cây danh mục môn học trên Sidebar bên trái. | Render đủ danh sách Khoa, mở/đóng xem môn học con trơn tru; không có warning prop type hoặc undefined array. | `PENDING` | - | Kiểm tra khắc phục TSK-304 |
| **TC-CTR-04** | Dashboard Props Completeness | Truy cập `/trang-chu` và `/khoa`. | Cả 2 tuyến đều nhận đủ `stats` và `recentDocuments`; các ô thống kê không bị rỗng hay hiển thị số âm. | `PENDING` | - | Kiểm tra khắc phục TSK-301 |
| **TC-CTR-05** | Pagination Contract | Kiểm tra các trang danh sách có phân trang (`Documents/Index`, `Wishlist/Index`, `Reviews/CourseReviews`). | Thuộc tính `links` được đọc đúng cấu trúc LengthAwarePaginator; nút Previous/Next và số trang bấm chuyển trang mượt mà. | `PASSED` | 2026-11-06 | TSK-104 đã gắn Pagination vào Documents/Index |

---

### Nhóm 3: Kiểm Tra Chức Năng CRUD & Luồng Tương Tác (Functional Workflows)

#### A. Phân Hệ M03: Tài Liệu Học Tập (Documents)
| Mã Test | Kịch Bản Kiểm Thử | Thao Tác Thực Hiện | Kết Quả Mong Đợi | Trạng Thái |
| :---: | :--- | :--- | :--- | :---: |
| **TC-DOC-01** | Mở form đăng tải tài liệu | Bấm nút "Đăng tải tài liệu" trên Header hoặc Sidebar (`/tai-lieu/dang-tai`). | Mở trang `Documents/Create.jsx` độc lập, có form chọn Khoa, Chuyên ngành, Môn học, tiêu đề, mô tả và vùng kéo thả file. | `PASSED` |
| **TC-DOC-02** | Lọc trùng SHA-256 tầng 1 | Chọn file đã từng được tải lên trong môn học đó. | Hệ thống kiểm tra băm file ngay trên client/server, hiển thị cảnh báo đỏ "Tài liệu này đã tồn tại trong môn học" và chặn gửi form. | `PASSED` |
| **TC-DOC-03** | Tải lên tài liệu mới thành công | Chọn file PDF hợp lệ (<50MB), điền đủ thông tin và bấm Submit. | Thanh tiến trình chạy 0-100%, tự động chuyển hướng sang trang `Documents/Show.jsx` của tài liệu vừa tạo kèm flash toast "Đăng tải thành công". | `PASSED` |
| **TC-DOC-04** | Xem chi tiết tài liệu | Click vào tiêu đề hoặc nút "Xem" từ bất kỳ thẻ tài liệu nào (`DocumentCard`). | Mở trang `Documents/Show.jsx` hiển thị đầy đủ tiêu đề, người đăng, ngày đăng, dung lượng, phân loại và số lượt xem/tải. | `PASSED` |
| **TC-DOC-05** | Xem trước PDF trực tuyến | Bấm nút "Xem trước PDF" trên trang chi tiết. | Mở khung xem trực tuyến nhúng PDF qua route `/tai-lieu/{id}/xem-truoc`, cho phép lật trang và phóng to/thu nhỏ tài liệu. | `PASSED` |
| **TC-DOC-06** | Tải file tài liệu về máy | Bấm nút "Tải tài liệu về máy" (`/tai-lieu/{id}/tai-ve`). | Trình duyệt tải file về ổ đĩa; biến đếm lượt tải trên giao diện tự động tăng thêm 1 đơn vị. | `PASSED` |
| **TC-DOC-07** | Upvote tài liệu AJAX | Bấm nút Like/Upvote trên thẻ tài liệu hoặc trang chi tiết. | Biến đếm upvote tăng thêm 1 ngay lập tức mà không phải reload toàn trang; icon đổi màu active. | `PASSED` |
| **TC-DOC-08** | Báo cáo tài liệu vi phạm | Bấm nút cờ báo cáo trên trang chi tiết tài liệu. | Mở modal báo cáo vi phạm, chọn lý do (Spam, bản quyền, sai môn) và gửi thành công tới Ban Kiểm duyệt. | `PASSED` |

#### B. Phân Hệ M04: Đánh Giá Môn Học (Reviews)
| Mã Test | Kịch Bản Kiểm Thử | Thao Tác Thực Hiện | Kết Quả Mong Đợi | Trạng Thái |
| :---: | :--- | :--- | :--- | :---: |
| **TC-REV-01** | Xem danh sách đánh giá | Truy cập `/mon-hoc/{code}/danh-gia`. | Hiển thị thông tin môn học, bảng tóm tắt điểm trung bình (độ khó, thực tiễn, khối lượng) và danh sách nhận xét. | `PENDING` |
| **TC-REV-02** | Lọc từ nhạy cảm thời gian thực | Mở modal viết đánh giá, gõ thử các từ ngữ tục tĩu/xúc phạm. | Viền ô nhập đổi màu đỏ, hiển thị cảnh báo từ ngữ vi phạm ngay tức thì và vô hiệu hóa nút gửi bài. | `PENDING` |
| **TC-REV-03** | Đăng bài đánh giá môn học | Nhập nhận xét lịch sự, chấm sao 1-5 và bấm Gửi đánh giá. | Bài viết xuất hiện ngay đầu danh sách; điểm đánh giá trung bình của môn học được tính toán lại. | `PENDING` |
| **TC-REV-04** | Bình chọn nhận xét hữu ích | Bấm nút "Hữu ích" (Thumbs up) trên bài đánh giá của sinh viên khác. | Số đếm hữu ích tăng thêm 1; nếu bấm lần nữa thì hủy vote (toggle). Người dùng không thể tự vote bài của chính mình. | `PENDING` |

#### C. Phân Hệ M05 & M06: Yêu Cầu Tìm Tài Liệu & Săn Thưởng Bounty
| Mã Test | Kịch Bản Kiểm Thử | Thao Tác Thực Hiện | Kết Quả Mong Đợi | Trạng Thái |
| :---: | :--- | :--- | :--- | :---: |
| **TC-BNT-01** | Xem thị trường săn thưởng | Bấm menu "Săn thưởng tài liệu" (`/yeu-cau-tai-lieu`). | Danh sách các yêu cầu tìm tài liệu hiển thị dạng lưới; lọc được theo trạng thái (Mở/Đóng) và môn học. | `PENDING` |
| **TC-BNT-02** | Đăng bài treo thưởng Bounty | Bấm "Tạo yêu cầu mới", chọn môn, nhập tiêu đề và cọc 50 điểm ví. | Tạo bài thành công; số dư điểm khả dụng trong ví bị trừ 50 điểm, điểm tạm giữ tăng 50 điểm. | `PENDING` |
| **TC-BNT-03** | Xem chi tiết yêu cầu | Click vào một yêu cầu tài liệu (`/yeu-cau-tai-lieu/{id}`). | Mở trang `Wishlist/Show.jsx` hiển thị mức điểm thưởng, mô tả và danh sách các bài nộp đáp ứng. Không bị lỗi build `canvas-confetti`. | `PENDING` |
| **TC-BNT-04** | Đóng góp tài liệu giải quyết | Bấm nút "Đóng góp tài liệu", chọn/nhập tài liệu phù hợp. | Bài nộp xuất hiện trong danh sách phản hồi của chủ yêu cầu ở trạng thái Chờ xác nhận. | `PENDING` |
| **TC-BNT-05** | Xác nhận giải ngân Bounty | Chủ bài đăng bấm "Xác nhận hợp lệ" cho một bài nộp. | Hiệu ứng pháo hoa Confetti kích hoạt; 50 điểm tạm giữ được chuyển vào ví người đóng góp, người đóng góp được cộng +10 điểm uy tín. | `PENDING` |
| **TC-BNT-06** | Hủy yêu cầu treo thưởng | Chủ bài đăng bấm "Hủy yêu cầu" khi chưa có ai đáp ứng. | Yêu cầu chuyển sang trạng thái ĐÃ HỦY; 50 điểm tạm giữ được hoàn trả đầy đủ vào điểm khả dụng. | `PENDING` |

#### D. Phân Hệ M07: Ví Điểm Cá Nhân (Wallet)
| Mã Test | Kịch Bản Kiểm Thử | Thao Tác Thực Hiện | Kết Quả Mong Đợi | Trạng Thái |
| :---: | :--- | :--- | :--- | :---: |
| **TC-WAL-01** | Truy cập giao diện Ví Điểm | Bấm vào số dư ví trên Header hoặc truy cập trực tiếp `/vi-diem`. | Mở trang `Wallet/Index.jsx` thành công (HTTP 200), không có lỗi crash component. | `PASSED` |
| **TC-WAL-02** | Hiển thị số dư chính xác | Đối chiếu số dư trên thẻ với CSDL bảng `vi_diem`. | Hiển thị chuẩn xác: Điểm khả dụng (`diem_kha_dung`), Điểm đang đóng băng (`diem_tam_giu`) và Tổng điểm tài khoản. | `PASSED` |
| **TC-WAL-03** | Bảng lịch sử giao dịch điểm | Xem bảng lịch sử biến động phía dưới. | Hiển thị danh sách các lần cộng/trừ điểm (`DONG_BANG`, `TRA_THUONG`, `HOAN_DIEM`), thời gian thực hiện, số điểm biến động màu xanh (+) hoặc đỏ (-). | `PENDING` |
| **TC-WAL-04** | Phân trang & Lọc biến động | Bấm chuyển trang và lọc giao dịch theo loại. | Bảng lịch sử cập nhật đúng danh sách mà không làm giật lag giao diện. | `PENDING` |

---

### Nhóm 4: Kiểm Tra Phân Quyền Vai Trò & Ranh Giới Người Dùng (Role Boundaries)

| Mã Test | Đối Tượng Kiểm Thử | Thao Tác & Mục Tiêu | Kết Quả Mong Đợi | Trạng Thái |
| :---: | :--- | :--- | :--- | :---: |
| **TC-SEC-01** | Khách vãng lai (Guest) | Truy cập `/vi-diem`, `/tai-lieu/dang-tai`, `/ho-so`. | Bị chặn và chuyển hướng ngay về trang đăng nhập `/dang-nhap` kèm thông báo yêu cầu xác thực. | `PENDING` |
| **TC-SEC-02** | Sinh viên chưa đổi MK | Đăng nhập tài khoản mới từ danh sách thô, cố tình gõ URL `/mon-hoc` hoặc `/vi-diem`. | Bị middleware `EnsurePasswordChanged` tóm lại và ép chuyển hướng về `/mat-khau-lan-dau`. | `PENDING` |
| **TC-SEC-03** | Cựu sinh viên (Alumni) | Đăng nhập tài khoản cựu sinh viên đã được duyệt KYC. | Sử dụng đầy đủ toàn bộ tính năng như sinh viên hiện hành (tra cứu, upload tài liệu, săn thưởng bounty, ví điểm). | `PENDING` |
| **TC-SEC-04** | Cựu sinh viên bị từ chối | Đăng nhập tài khoản cựu sinh viên có KYC trạng thái `TU_CHOI`. | Chặn đăng nhập, thông báo rõ ràng lý do từ chối trên form đăng nhập. | `PENDING` |
| **TC-SEC-05** | Sinh viên truy cập Admin | Sinh viên cố tình gõ các URL quản trị `/quan-tri/*`. | Bị middleware `CheckRole` chặn đứng với mã lỗi HTTP 403 Forbidden. | `PENDING` |

---

### Nhóm 5: Kiểm Tra Trạng Thái Trống & Xử Lý Lỗi (Empty & Error States)

| Mã Test | Trường Hợp Kiểm Thử | Điều Kiện Mô Phỏng | Kết Quả Mong Đợi | Trạng Thái |
| :---: | :--- | :--- | :--- | :---: |
| **TC-EMP-01** | Kho môn học không có tài liệu | Truy cập một môn học mới tạo chưa ai tải tài liệu. | Hiển thị giao diện Empty State thân thiện: Icon minh họa, thông báo "Chưa có tài liệu nào" và nút kêu gọi "Đăng tài liệu đầu tiên". | `PENDING` |
| **TC-EMP-02** | Ví điểm chưa có giao dịch | Tài khoản mới kích hoạt chưa từng tạo bounty. | Bảng lịch sử hiển thị thông báo "Chưa có biến động điểm nào" kèm gợi ý tham gia đóng góp tài liệu để nhận thưởng. | `PENDING` |
| **TC-EMP-03** | Không có thông báo | Bấm mở chuông thông báo khi chưa có thông báo mới. | Dropdown hiển thị trạng thái "Bạn chưa có thông báo mới nào" gọn gàng, không bị vỡ layout. | `PENDING` |
| **TC-ERR-01** | Upload file quá giới hạn | Chọn file có dung lượng >50MB tải lên form đăng tài liệu. | Client validate chặn ngay lập tức, báo lỗi đỏ "Dung lượng tệp vượt quá 50MB" trước khi upload. | `PENDING` |
| **TC-ERR-02** | Số dư ví không đủ cọc | Đặt cọc 200 điểm trong khi ví khả dụng chỉ có 100 điểm. | Form tạo yêu cầu hiển thị lỗi "Số dư điểm khả dụng không đủ để đặt cọc Bounty". | `PENDING` |

---

### Nhóm 6: Kiểm Tra Độ Tương Thích Thiết Bị & Responsive (UI / UX Responsiveness)

| Mã Test | Thiết Bị / Độ Phân Giải | Hạng Mục Kiểm Tra | Tiêu Chuẩn Đạt Nghiệm Thu | Trạng Thái |
| :---: | :--- | :--- | :--- | :---: |
| **TC-RES-01** | Mobile (<768px) Drawer | Nút Hamburger menu trên góc trái Header. | Bấm vào mở Drawer Sidebar trượt từ trái sang; click ra ngoài nền mờ thì đóng Sidebar. | `PENDING` |
| **TC-RES-02** | Mobile Sticky Header | Cuộn trang lên và xuống trên màn hình điện thoại. | Header tự động ẩn khi cuộn xuống và hiện lại khi cuộn ngược lên trên; không che khuất nội dung. | `PENDING` |
| **TC-RES-03** | Tablet (768px - 1024px) | Bố cục lưới thẻ tài liệu và thẻ yêu cầu Bounty. | Tự động co giãn thành 2 cột đều đặn; không tràn chữ (overflow) hay vỡ khung thẻ. | `PENDING` |
| **TC-RES-04** | Desktop (>1024px) | Hiển thị Sidebar cố định và khu vực Main Content. | Sidebar hiển thị cố định bên trái (260px), vùng nội dung chính căn giữa cân đối, tối đa 1600px. | `PENDING` |

---

## 3. BIÊN BẢN KÝ DUYỆT NGHIỆM THU (SIGN-OFF SHEET)

| Giai Đoạn | Ngày Nghiệm Thu | Tỷ Lệ Pass Test | Người Duyệt Kỹ Thuật (Tech Lead) | Người Duyệt Nghiệp Vụ (PO) | Kết Luận |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Sprint 1 (Phase 0 & 1)** | *Chưa thực hiện* | 0% | *Chưa ký* | *Chưa ký* | `PENDING` |
| **Sprint 2 (Phase 2 & 3)** | *Chưa thực hiện* | 0% | *Chưa ký* | *Chưa ký* | `PENDING` |
| **Sprint 3 (Phase 4 & E2E)** | *Chưa thực hiện* | 0% | *Chưa ký* | *Chưa ký* | `PENDING` |

---
*Checklist được biên soạn bởi Lead QA & Senior Software Engineer — AGU Academic Hub.*

