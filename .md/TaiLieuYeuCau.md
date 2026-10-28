# TÀI LIỆU YÊU CẦU DỰ ÁN (PROJECT REQUIREMENTS SPECIFICATION)

**Hệ thống:** Nền tảng Tri thức & Cộng đồng Sinh viên Nội bộ (AGU Academic Hub)

-----

# PHẦN 1: TÀI LIỆU YÊU CẦU KINH DOANH (BRD)

### 1\. Bối cảnh & Vấn đề Cốt lõi (Problem Statement)

  * **Phân mảnh và thất thoát tri thức:** Sinh viên Đại học An Giang (AGU) chủ yếu trao đổi tài liệu qua các nhóm mạng xã hội mở. Tài liệu học tập (đề thi, slide, đồ án) bị trôi bài, mất liên kết tải, không có hệ thống phân loại theo mã học phần chuẩn của nhà trường.
  * **Bùng nổ dữ liệu rác & Trùng lặp nội dung:** Các nền tảng chia sẻ tự do thường gặp tình trạng "thừa tài liệu rác, thiếu tài liệu cần". Cùng một file PDF bị đổi tên, đổi trang bìa và upload lặp lại nhiều lần làm phình dung lượng lưu trữ nhưng không gia tăng giá trị học thuật.
  * **Rủi ro phá hoại và toàn vẹn dữ liệu:** Mô hình chia sẻ cộng đồng nếu không phân quyền chặt chẽ sẽ đối mặt với nguy cơ bị kẻ xấu xóa hàng loạt tài liệu giá trị hoặc spam nội dung độc hại.
  * **Môi trường thiếu an toàn cho phản hồi học thuật:** Sinh viên thiếu kênh đánh giá môn học (Course Reviews) khách quan, minh bạch và an toàn để định hướng đăng ký tín chỉ và phương pháp học tập.

### 2\. Mục tiêu Chiến lược & Chỉ số Đo lường (Business Goals & KPIs)

  * **\[Định danh Nội bộ 100%\]**: Loại bỏ tài khoản ảo, thương mại hóa và spam bên ngoài.
  * **\[Tối ưu hóa Kho Dữ liệu\]**: Chống trùng lặp nhị phân & tương đồng nội dung (\> 85%).
  * **\[Bảo tồn Tri thức Chung\]**: Cơ chế Community Ownership & Soft Delete chống phá hoại.
  * **\[Cân bằng Cung - Cầu\]**: Hệ thống Đặt hàng tài liệu (Bounty/Wishlist) có tích điểm.

**KPIs vận hành & Kỹ thuật:**

  * **Tỷ lệ định danh thành công:** ≥95% tài khoản hoạt động được xác thực qua Email trường hoặc đối soát thẻ sinh viên.
  * **Tỷ lệ tinh gọn dữ liệu (Deduplication Rate):** Giảm thiểu tối thiểu 80% dung lượng trùng lặp qua bộ lọc mã băm SHA-256 và so khớp tương đồng.
  * **Tỷ lệ toàn vẹn tri thức:** 0% tài liệu có giá trị (≥10 lượt tải) bị xóa vĩnh viễn ngoài ý muốn nhờ cơ chế Soft Delete và phê duyệt 2 lớp.
  * **Thời gian truy xuất tài liệu:** ≤30 giây để tìm chính xác tài liệu theo Mã học phần / Tên giảng viên.

### 3\. Đối tượng Sử dụng & Chân dung (User Personas)

  * **Sinh viên Đang học (Active Student):** Cần tìm kiếm đề thi, slide bài giảng chuẩn theo mã môn; tham gia hỏi đáp, đăng yêu cầu tìm tài liệu hiếm; xem và viết review môn học ẩn danh.
  * **Cựu sinh viên (Alumni):** Đóng góp đề cương, đồ án thực tế; chia sẻ kinh nghiệm thực tập/việc làm; hỗ trợ giải quyết các yêu cầu tài liệu từ khóa dưới.
  * **Điều phối viên / Ban học tập (Moderator):** Phê duyệt tài liệu chuyên môn; xử lý hàng đợi báo cáo vi phạm (Report Queue); kiểm duyệt bài viết thảo luận.
  * **Quản trị viên Hệ thống (Admin):** Quản lý cấu trúc danh mục Khoa/Môn; duyệt hồ sơ định danh ngoại lệ (KYC); khôi phục dữ liệu bị xóa nhầm; theo dõi dung lượng lưu trữ.

### 4\. Giá trị Đem lại & Cơ chế Vận hành Cốt lõi (Core Mechanics)

  * **Quyền sở hữu cộng đồng (Community Ownership):** Tài liệu khi đã đạt mốc tương tác cao trở thành tài sản chung của sinh viên toàn trường. Tác giả không thể tự ý xóa bỏ làm gián đoạn việc học của người khác.
  * **Cơ chế Điểm đóng góp (Academic Reputation & Credits):** Hạn chế tải về ồ ạt bằng cách yêu cầu tích lũy điểm thưởng qua việc tải lên tài liệu mới chất lượng, giải quyết yêu cầu trong Wishlist, hoặc nhận Upvote từ cộng đồng.
  * **Đánh giá môn học Ẩn danh có kiểm soát (Controlled Anonymity):** Bảo vệ danh tính sinh viên trên giao diện nhưng lưu vết định danh tại Database phục vụ kiểm tra vi phạm quy chế.

-----

# PHẦN 2: TÀI LIỆU ĐẶC TẢ YÊU CẦU PHẦN MỀM (SRS)

### 1\. Kiến trúc Kỹ thuật & Công nghệ (System Architecture)

  * **Backend:** Laravel 11.x (PHP 8.2+).
  * **Cơ sở dữ liệu:** MySQL 8.0 (InnoDB Engine, Fulltext Index).
  * **Xử lý Bất đồng bộ (Queues/Jobs):** Database/Redis Queue phục vụ bóc tách văn bản, tính toán mã băm SHA-256, gửi email xác thực.
  * **Lưu trữ File (Storage):** Local Private Storage / S3-compatible Object Storage (Tách biệt thư mục công khai và thư mục nhạy cảm).
  * **Giao diện & Tương tác:** Laravel Blade + TailwindCSS + [Alpine.js](http://Alpine.js).

### Ma trận Phân quyền Truy cập (RBAC Matrix)

| Nhóm chức năng                                | Khách (Guest) | Sinh viên (Student) | Cựu SV (Alumni) | Mod | Admin |
| :-------------------------------------------- | :-----------: | :-----------------: | :-------------: | :-: | :---: |
| Xem Landing page & Đăng nhập                  | ✔             | ✔                   | ✔               | ✖   | ✖     |
| Tìm kiếm, Preview & Tải tài liệu              | ✖             | ✔                   | ✔               | ✔   | ✔     |
| Upload tài liệu mới / Tạo Version             | ✖             | ✔                   | ✔               | ✔   | ✔     |
| Xóa tài liệu của chính mình (Khi chưa ai tải) | ✖             | ✔                   | ✔               | ✔   | ✔     |
| Xóa mềm tài liệu bất kỳ (Kèm lý do)           | ✖             | ✖                   | ✖               | ✔   | ✔     |
| Khôi phục tài liệu đã xóa (Restore)           | ✖             | ✖                   | ✖               | ✖   | ✔     |
| Tạo yêu cầu tài liệu (Wishlist Request)       | ✖             | ✔                   | ✔               | ✔   | ✔     |
| Đăng review môn học (Ẩn danh/Công khai)       | ✖             | ✔                   | ✔               | ✖   | ✖     |
| Duyệt hàng đợi KYC & Xử lý Báo cáo            | ✖             | ✖                   | ✖               | ✔   | ✔     |
| Cấu hình Khoa / Môn học / Quản trị hệ thống   | ✖             | ✖                   | ✖               | ✖   | ✔     |

### Module 1: Định danh & Phân quyền Tài khoản (Identity & Access Management)

  * **FR-01.1 (Định danh Sinh viên đang học - Pre-provisioned):** Không cung cấp đăng ký tự do và không yêu cầu sinh viên nộp ảnh thẻ để duyệt thủ công. Admin/Nhà trường nạp trước dữ liệu vào bảng `danh_sach_sinh_vien_raw` gồm MSSV, họ tên, email `@student.agu.edu.vn`, mật khẩu mặc định đã băm và khoa.
  * **FR-01.2 (Đăng nhập lần đầu Sinh viên đang học):** Sinh viên đăng nhập bằng MSSV hoặc email trường cùng mật khẩu mặc định. Nếu thông tin khớp dữ liệu thô và chưa có tài khoản chính, hệ thống tự động khởi tạo tài khoản vai trò `SINH_VIEN` ở trạng thái `CHUA_XAC_THUC`, tạo phiên đăng nhập tạm thời và chuyển sang luồng đổi mật khẩu bắt buộc.
  * **FR-01.3 (Cưỡng chế đổi mật khẩu và kích hoạt):** Middleware `EnsurePasswordChanged` chặn các nghiệp vụ khác cho đến khi sinh viên đặt mật khẩu mới đạt chuẩn. Khi hoàn tất, hệ thống lưu mật khẩu Bcrypt, chuyển tài khoản sang `DANG_HOAT_DONG`, đánh dấu bản ghi raw `da_kich_hoat = true`, lưu `kich_hoat_luc` và khởi tạo ví điểm với 100 điểm.
  * **FR-01.4 (Đăng ký Cựu sinh viên - Alumni KYC):** Cựu sinh viên đăng ký qua form riêng `/alumni/register` (hoặc route tương đương `/cuu-sinh-vien/dang-ky`), cung cấp MSSV khóa cũ, năm tốt nghiệp và một minh chứng: bằng tốt nghiệp, giấy chứng nhận tốt nghiệp tạm thời hoặc thẻ sinh viên khóa cũ. Hồ sơ chuyển sang `CHO_DUYET`.
  * **FR-01.5 (Trạng thái Người dùng):** Sinh viên đang học đi theo `CHUA_XAC_THUC` → `DANG_HOAT_DONG` sau khi đổi mật khẩu; cựu sinh viên đi theo `CHUA_XAC_THUC` → `DANG_HOAT_DONG` sau khi Admin/Mod duyệt; cả hai đều có thể chuyển sang `TAM_KHOA` khi vi phạm.

### Module 2: Quản lý & Tải lên Tài liệu (Document Lifecycle & Deduplication)

  * **FR-02.1 (Phân loại Cấu trúc):** Bắt buộc liên kết tài liệu với: Khoa → Ngành → Môn học (Mã HP) → Phân loại (Đề thi, Bài giảng, Đồ án, Trắc nghiệm).
  * **FR-02.2 (Quy trình Lọc trùng 2 lớp - Deduplication Pipeline):**
      * Lớp 1 (Mã băm nhị phân): Tính chuỗi SHA-256 của file tải lên. Nếu file\_hash đã tồn tại trong cùng Mã học phần → Chặn lưu file vật lý, thông báo tài liệu đã có trên hệ thống.
      * Lớp 2 (So khớp tương đồng nội dung): Đẩy file vào Background Queue, trích xuất text (bỏ trang bìa), tính toán độ tương đồng qua SimHash/Cosine Similarity. Nếu độ trùng lặp \>85% so với tài liệu hiện có → Đưa ra cảnh báo và yêu cầu xác nhận tạo phiên bản mới (parent\_id) thay vì tạo tài liệu độc lập.
  * **FR-02.3 (Xem trước Trực tuyến / Document Preview):** Tích hợp trình xem trước file PDF/Office trực tiếp trên trình duyệt mà không bắt buộc tải về máy.
  * **FR-02.4 (Phiên bản hóa - Versioning):** Cho phép người dùng đóng góp bản cập nhật (sửa lỗi, bổ sung lời giải) cho một tài liệu đã có, gom nhóm hiển thị dưới dạng v1.0, v1.1, v2.0.

### Module 3: Bảo vệ Dữ liệu & Chống Phá hoại (Data Protection & Anti-Sabotage)

  * **FR-03.1 (Ràng buộc Quyền xóa - Laravel Policy):** Sinh viên chỉ được xóa tài liệu do chính mình tải lên trong vòng 24 giờ đầu VÀ số lượt tải bằng 0 (downloads\_count = 0). Khi tài liệu có ≥10 lượt tải hoặc đánh giá ≥4 sao → Hệ thống khóa quyền xóa của tác giả (Áp dụng cơ chế Community Ownership). Tác giả chỉ có thể gửi yêu cầu gỡ bỏ (Request Removal) kèm lý do để Mod/Admin xét duyệt.
  * **FR-03.2 (Cơ chế Xóa mềm - Soft Deletes):** Toàn bộ thao tác xóa từ người dùng, Mod hoặc Admin đều chỉ cập nhật trường deleted\_at. File vật lý và bản ghi Database được giữ nguyên trong thùng rác hệ thống ít nhất 60 ngày.
  * **FR-03.3 (Hàng đợi Báo cáo Vi phạm - Report Queue):** Người dùng có quyền báo cáo tài liệu (Nội dung sai, trùng lặp, chứa mã độc, vi phạm bản quyền). Khi nhận đủ ≥3 báo cáo từ các tài khoản uy tín, tài liệu tự động chuyển sang trạng thái HIDDEN\_FOR\_REVIEW chờ Mod/Admin xử lý.

### Module 4: Cân bằng Cung - Cầu (Document Wishlist & Bounty System)

  * **FR-04.1 (Yêu cầu Tài liệu):** Sinh viên tạo phiếu yêu cầu: Tên môn, giảng viên phụ trách, loại tài liệu cần tìm, mức điểm thưởng trích từ ví cá nhân (bounty\_points).
  * **FR-04.2 (Thông báo Điều hướng):** Hệ thống tự động gửi thông báo đến các Cựu sinh viên hoặc sinh viên đã hoàn thành môn học đó với điểm số cao.
  * **FR-04.3 (Trả thưởng Tự động):** Khi tài liệu tải lên để giải quyết yêu cầu được người yêu cầu hoặc Mod xác nhận hợp lệ → Hệ thống tự động chuyển điểm thưởng cho người đóng góp.

### Module 5: Đánh giá Môn học (Course & Lecturer Review Engine)

  * **FR-05.1 (Tiêu chí Đánh giá):** Thang điểm 1 - 5 sao cho: Độ khó của môn, Tính thực tiễn, Khối lượng bài tập.
  * **FR-05.2 (Ẩn danh 2 lớp):** Ẩn hoàn toàn thông tin định danh của người đánh giá ở tầng giao diện và API public. Lưu trữ user\_id nội bộ tại Database có mã hóa phục vụ hậu kiểm.
  * **FR-05.3 (Bộ lọc Từ khóa Tự động):** Chặn các từ ngữ lăng mạ, bôi nhọ danh dự cá nhân trước khi bài đánh giá được lưu vào hệ thống.

### 4\. Yêu cầu Phi chức năng (Non-Functional Requirements)

  * **NFR-01 (Bảo mật Dữ liệu Riêng tư - PII):** Chỉ minh chứng của cựu sinh viên mới được lưu trong phân vùng riêng `storage/app/private/kyc`; tuyệt đối không lưu ảnh thẻ sinh viên đang học vì luồng này được định danh tự động từ dữ liệu nạp trước. File KYC không được tạo Symlink ra thư mục public và chỉ được truy cập qua Signed URL hoặc Route nội bộ có Middleware kiểm tra quyền Admin/Mod.
  * **NFR-02 (Hiệu năng & Tối ưu Tải):**
      * Tác vụ bóc tách text và tính toán độ tương đồng file phải thực thi qua Laravel Queue, không làm nghẽn tiến trình HTTP Request của người dùng.
      * Thời gian phản hồi trang danh mục và tìm kiếm học phần ≤500ms.
  * **NFR-03 (Bảo toàn & Dung lượng):** Áp dụng nén file và tối ưu ảnh đại diện trước khi lưu trữ để tiết kiệm dung lượng máy chủ.
  * **NFR-04 (Chống Tấn công):** Rate Limiting 10 lượt upload tài liệu/ngày cho tài khoản thường; kiểm tra định dạng file (MIME-Type Sniffing) ở tầng Backend để chặn hoàn toàn file thực thi .exe, .sh, .php, .bat.
