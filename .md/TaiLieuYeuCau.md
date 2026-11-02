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

Hệ thống được thiết kế theo chuẩn kiến trúc **Modular Monolith 4 tầng** (Domain-Driven Design), phân chia thành **7 Bounded Contexts** nghiệp vụ độc lập:

  * **Backend Framework:** Laravel 11.x (PHP 8.2+), tổ chức 4 tầng (`Presentation`, `Application`, `Domain`, `Infrastructure`).
  * **Frontend & UI Stack:** Inertia.js v2, React 18+, Tailwind CSS v4, Ziggy Route Helper, Lucide React.
  * **Cơ sở dữ liệu:** MySQL 8.0 (InnoDB Engine, Fulltext Index) cho môi trường Production; SQLite In-Memory tương thích 100% cho môi trường Automated Testing thông qua cơ chế tự động nhận diện Driver.
  * **Xử lý Bất đồng bộ (Queues/Jobs):** Database/Redis Queue phục vụ bóc tách văn bản, tính toán mã băm SHA-256, so khớp SimHash > 85%, gửi email xác thực.
  * **Lưu trữ File (Storage):** Tách biệt ổ đĩa công khai (`storage/app/public/documents`) và phân vùng riêng tư nhạy cảm (`storage/app/private/kyc`) với quyền truy cập thông qua Temporary Signed URL 30 phút hoặc Stream Controller an toàn.

### Ma trận Phân quyền Truy cập (RBAC Matrix)

| Nhóm chức năng                                | Khách (Guest) | Sinh viên (Student) | Cựu SV (Alumni) | Mod | Admin thường | Super Admin |
| :-------------------------------------------- | :-----------: | :-----------------: | :-------------: | :-: | :----------: | :---------: |
| Xem Landing page & Đăng nhập                  | ✔             | ✔                   | ✔               | ✖   | ✖            | ✖           |
| Tìm kiếm, Preview & Tải tài liệu              | ✖             | ✔                   | ✔               | ✔   | ✔            | ✔           |
| Upload tài liệu mới / Tạo Version             | ✖             | ✔                   | ✔               | ✔   | ✔            | ✔           |
| Xóa tài liệu của chính mình (Khi chưa ai tải) | ✖             | ✔                   | ✔               | ✔   | ✔            | ✔           |
| Xóa mềm tài liệu bất kỳ (Kèm lý do)           | ✖             | ✖                   | ✖               | ✔   | ✔            | ✔           |
| Khôi phục tài liệu đã xóa (Restore)           | ✖             | ✖                   | ✖               | ✖   | ✔            | ✔           |
| Dọn dẹp vĩnh viễn thùng rác (Purge)           | ✖             | ✖                   | ✖               | ✖   | ✖            | ✔           |
| Tạo yêu cầu tài liệu (Wishlist Request)       | ✖             | ✔                   | ✔               | ✔   | ✔            | ✔           |
| Đăng review môn học (Ẩn danh/Công khai)       | ✖             | ✔                   | ✔               | ✖   | ✖            | ✖           |
| Duyệt hàng đợi KYC & Xử lý Báo cáo            | ✖             | ✖                   | ✖               | ✔   | ✔            | ✔           |
| Quản lý Khoa / Ngành / Môn học                | ✖             | ✖                   | ✖               | ✖   | ✔            | ✔           |
| Phân quyền & Quản lý Admin/Mod                | ✖             | ✖                   | ✖               | ✖   | ✖            | ✔           |

---

### Phân Rã Yêu Cầu Chức Năng (Functional Requirements) Theo 7 Bounded Contexts

#### Bounded Context 1: Định danh, Xác thực & KYC (`app/Modules/Identity/`)
  * **FR-01.1 (Định danh Sinh viên đang học - Pre-provisioned):** Không cung cấp đăng ký tự do và không yêu cầu sinh viên nộp ảnh thẻ để duyệt thủ công. Dữ liệu nạp trước vào bảng `danh_sach_sinh_vien_raw` gồm MSSV, họ tên, email `@student.agu.edu.vn`, mật khẩu mặc định đã băm và khoa.
  * **FR-01.2 (Đăng nhập lần đầu & Đối soát thô):** Sinh viên đăng nhập bằng MSSV hoặc email trường cùng mật khẩu mặc định. Nếu thông tin khớp dữ liệu thô và chưa có tài khoản chính, hệ thống tự động khởi tạo tài khoản vai trò `SINH_VIEN` ở trạng thái `CHUA_XAC_THUC`, tạo phiên đăng nhập tạm thời và chuyển sang luồng đổi mật khẩu bắt buộc.
  * **FR-01.3 (Cưỡng chế đổi mật khẩu và kích hoạt):** Middleware `EnsurePasswordChanged` chặn các nghiệp vụ khác cho đến khi sinh viên đặt mật khẩu mới đạt chuẩn. Khi hoàn tất, hệ thống chuyển tài khoản sang `DANG_HOAT_DONG`, đánh dấu bản ghi raw `da_kich_hoat = true`, lưu `kich_hoat_luc` và khởi tạo ví điểm với 100 điểm.
  * **FR-01.4 (Đăng ký Cựu sinh viên - Alumni KYC):** Cựu sinh viên đăng ký qua form riêng `/cuu-sinh-vien/dang-ky`, cung cấp MSSV khóa cũ, năm tốt nghiệp và một minh chứng (bằng tốt nghiệp, giấy chứng nhận hoặc thẻ sinh viên cũ). File lưu tại `storage/app/private/kyc/` và hồ sơ chuyển sang `CHO_DUYET`.
  * **FR-01.5 (Vòng đời Tài khoản & Email Verification):** Quản lý trạng thái (`CHO_XAC_THUC` → `DANG_HOAT_DONG` → `TAM_KHOA`), xác thực email qua trường `xac_thuc_email_luc` (tương thích `email_verified_at`), cập nhật thông tin cá nhân và quy trình đổi mật khẩu an toàn.
  * **FR-01.6 (Hàng đợi Xét duyệt KYC):** Cho phép Moderator và Admin xem danh sách hồ sơ cựu sinh viên, xem trước ảnh minh chứng qua Temporary Signed URL 30 phút, duyệt hồ sơ kích hoạt tài khoản hoặc từ chối kèm lý do phản hồi.

#### Bounded Context 2: Danh mục Đào tạo (`app/Modules/AcademicCatalog/`)
  * **FR-02.0a (Cấu trúc Danh mục Đa cấp):** Quản lý quan hệ phân cấp Khoa → Chuyên ngành → Môn học (Mã học phần, số tín chỉ, mô tả).
  * **FR-02.0b (Bảo vệ Trạng thái Phân cấp - Cascading Status Protection):**
    - Khi chuyển trạng thái Khoa sang `TAM_KHOA`, toàn bộ Chuyên ngành và Môn học trực thuộc bị khóa liên đới.
    - Không cho phép kích hoạt Chuyên ngành hoặc Môn học nếu Khoa cha đang ở trạng thái `TAM_KHOA`.
  * **FR-02.0c (Tra cứu Danh mục Công khai):** Cung cấp API và giao diện Cây phân cấp (Tree View) cho sinh viên dễ dàng lọc và tìm kiếm môn học nhanh chóng theo Mã HP / Tên môn.

#### Bounded Context 3: Quản lý & Tải lên Tài liệu (`app/Modules/Documents/`)
  * **FR-02.1 (Phân loại Cấu trúc):** Bắt buộc liên kết tài liệu với: Khoa → Chuyên ngành → Môn học (Mã HP) → Phân loại (Đề thi, Bài giảng, Đồ án, Bài tập, Khác).
  * **FR-02.2 (Quy trình Lọc trùng 2 lớp - Deduplication Pipeline):**
      * *Lớp 1 (Mã băm nhị phân SHA-256):* Tính chuỗi SHA-256 của file tải lên. Nếu mã băm đã tồn tại trong cùng Môn học → Chặn lưu file vật lý lập tức, báo lỗi Conflict 409.
      * *Lớp 2 (So khớp tương đồng nội dung SimHash):* Đẩy file vào Background Queue, trích xuất text, tính toán tương đồng. Nếu độ trùng lặp >85% so với tài liệu hiện có → Đưa ra cảnh báo và gợi ý tạo phiên bản mới kế tiếp (v1.1, v2.0) thay vì tạo tài liệu độc lập.
  * **FR-02.3 (Xem trước Trực tuyến An toàn):** Trình xem trước file PDF trực tiếp trên trình duyệt qua luồng Stream Flysystem an toàn, chống lộ đường dẫn lưu trữ vật lý trên server.
  * **FR-02.4 (Phiên bản hóa - Versioning):** Cho phép người dùng đóng góp bản cập nhật liên kết `ma_tai_lieu_goc`, hiển thị nhóm phiên bản v1.0, v1.1, v2.0.
  * **FR-02.5 (Tải về & Upvote):** Ghi nhận lượt tải (tăng counter `luot_tai`) và cho phép sinh viên upvote tài liệu hữu ích.
  * **FR-02.6 (Quyền Tác giả Xóa):** Tác giả chỉ được tự xóa tài liệu trong vòng 24 giờ đầu kể từ khi đăng VÀ số lượt tải bằng 0 (`luot_tai == 0`).

#### Bounded Context 4: Kiểm duyệt & Chống Phá hoại (`app/Modules/Moderation/`)
  * **FR-03.1 (Quyền sở hữu cộng đồng - Community Ownership):** Khi tài liệu đạt `luot_tai >= 10` hoặc đánh giá `diem_danh_gia >= 4.0`, tài liệu trở thành tài sản chung của cộng đồng. Tác giả bị vô hiệu hóa quyền xóa và chuyển sang cơ chế gửi Yêu cầu Gỡ bỏ (Removal Request) kèm lý do để Mod/Admin xét duyệt.
  * **FR-03.2 (Cơ chế Xóa mềm - Soft Deletes 60 Ngày):** Toàn bộ thao tác xóa từ người dùng, Mod hoặc Admin đều chỉ cập nhật `deleted_at`. File vật lý và bản ghi CSDL được bảo lưu trong thùng rác hệ thống ít nhất 60 ngày, cho phép khôi phục nguyên trạng.
  * **FR-03.3 (Hàng đợi Báo cáo Vi phạm - Report Queue):** Người dùng có quyền báo cáo tài liệu (Trùng lặp, Sai nội dung, Mã độc, Bản quyền). Khi nhận đủ ≥3 báo cáo, tài liệu tự động chuyển sang trạng thái `TAM_AN_KIEM_TRA` chờ Mod/Admin xử lý.
  * **FR-03.4 (Hàng đợi Phê duyệt Tài liệu):** Kiểm duyệt viên xem xét các tài liệu mới tải lên trước khi công khai ra toàn trường.

#### Bounded Context 5: Đánh giá Môn học (`app/Modules/Reviews/`)
  * **FR-05.1 (Tiêu chí Đánh giá 3 Chiều):** Chấm điểm 1 - 5 sao theo 3 tiêu chí độc lập: Độ khó của môn, Tính thực tiễn, và Khối lượng bài tập.
  * **FR-05.2 (Ẩn danh 2 lớp):** Lớp hiển thị (Presentation/Inertia) ẩn toàn bộ danh tính sinh viên khi `la_an_danh = true`. Lớp dữ liệu (Database) lưu `ma_nguoi_danh_gia` phục vụ hậu kiểm chống phá hoại học thuật.
  * **FR-05.3 (Bộ lọc Từ cấm Thông minh):** Tự động phát hiện và ngăn chặn từ ngữ thô tục, chuẩn hóa leetspeak, kết hợp danh sách từ cấm nội bộ và fallback CSDL.
  * **FR-05.4 (Bình chọn Hữu ích):** Sinh viên có thể bấm "Hữu ích" cho bài đánh giá; hệ thống ngăn chặn tác giả tự bình chọn bài viết của chính mình.

#### Bounded Context 6: Cân bằng Cung - Cầu & Wishlist (`app/Modules/Bounties/`)
  * **FR-04.1 (Yêu cầu Tài liệu - Wishlist):** Sinh viên đăng yêu cầu tìm tài liệu hiếm, đính kèm mức điểm thưởng trích từ ví cá nhân (`diem_thuong`).
  * **FR-04.2 (Ký quỹ Điểm an toàn - Escrow):** Sử dụng khóa bi quan `lockForUpdate` và ghi nhận sổ cái `giao_dich_diem_thuong` với trạng thái `TAM_GIU`, đảm bảo không bị trừ điểm hai lần (Idempotency Key).
  * **FR-04.3 (Nộp Tài liệu Giải quyết):** Sinh viên khác tải lên tài liệu giải quyết yêu cầu; hệ thống chặn tác giả tự nộp tài liệu giải quyết yêu cầu của chính mình.
  * **FR-04.4 (Tất toán & Hoàn trả):** Khi người yêu cầu xác nhận tài liệu đạt chuẩn, hệ thống giải phóng điểm ký quỹ và cộng điểm thưởng cho người đóng góp (`CONG_DIEM`); trường hợp đóng/hủy yêu cầu, điểm được hoàn trả nguyên vẹn về ví (`HOAN_TRA`).

#### Bounded Context 7: Quản trị Hệ thống & Lưu trữ (`app/Modules/Administration/`)
  * **FR-06.1 (Giám sát Dung lượng Lưu trữ):** Thống kê tổng dung lượng ổ đĩa, phân bổ theo loại file (PDF, DOCX, ZIP...), phát hiện file có dung lượng lớn bất thường, tương thích truy vấn thống kê trên cả MySQL và SQLite.
  * **FR-06.2 (Dọn dẹp Thùng rác Vĩnh viễn):** Chỉ Super Admin mới có quyền dọn dẹp các file trong thùng rác đã quá hạn 60 ngày. Bắt buộc thực thi trong DB Transaction, xóa file vật lý an toàn và ghi nhận bản ghi kiểm toán tại bảng `lich_su_thung_rac`.
  * **FR-06.3 (Phân cấp Quản trị Tài khoản):**
    - Hệ thống phân biệt hai cấp trong vai trò `QUAN_TRI_VIEN`: Admin thường (`THUONG`) và Super Admin (`TOI_CAO`).
    - Admin thường chỉ quản lý tài khoản Sinh viên/Cựu sinh viên; không được can thiệp vào tài khoản Moderator hoặc Admin khác.
    - Chỉ Super Admin mới có quyền phân quyền Quản trị viên, nâng hạ cấp `cap_quan_tri`, hoặc khóa tài khoản quản trị.
    - Bảo vệ tài khoản `admin@agu.edu.vn` và không cho phép hạ cấp/xóa Super Admin cuối cùng của hệ thống.
  * **FR-06.4 (Bảng Điều khiển Quản trị Tổng thể):** Tổng hợp số liệu người dùng, tài liệu, báo cáo chờ xử lý và tình trạng tài nguyên hệ thống theo thời gian thực.

---

### 4\. Yêu cầu Phi chức năng (Non-Functional Requirements)

  * **NFR-01 (Bảo mật Dữ liệu Riêng tư - PII):** Chỉ minh chứng của cựu sinh viên mới được lưu trong phân vùng riêng `storage/app/private/kyc`; tuyệt đối không lưu ảnh thẻ sinh viên đang học vì luồng này được định danh tự động từ dữ liệu nạp trước. File KYC không được tạo Symlink ra thư mục public và chỉ được truy cập qua Temporary Signed URL 30 phút hoặc Route nội bộ có Middleware kiểm tra quyền Admin/Mod.
  * **NFR-02 (Hiệu năng & Tương thích Đa Driver CSDL):**
      * Tác vụ bóc tách text và tính toán độ tương đồng file phải thực thi qua Laravel Queue, không làm nghẽn tiến trình HTTP Request của người dùng.
      * Tầng Infrastructure Repositories phải bọc kiểm tra `DB::getDriverName()` nhằm hỗ trợ đồng thời cả cú pháp `DATE_FORMAT` (MySQL Production) và `strftime` (SQLite In-Memory Test).
      * Áp dụng Caching thông minh (`Cache::remember`) cho các truy vấn thống kê nặng với thời gian TTL hợp lý (30 - 60 phút).
      * Thời gian phản hồi trang danh mục và tìm kiếm học phần ≤500ms.
  * **NFR-03 (Bảo toàn & Lưu vết Kiểm toán):** Áp dụng Soft Delete 60 ngày cho mọi tài liệu. Khi thực hiện xóa vĩnh viễn, bắt buộc ghi nhận vào bảng `lich_su_thung_rac` để phục vụ công tác đối soát.
  * **NFR-04 (Chống Tấn công & Toàn vẹn Dữ liệu):** Rate Limiting 10 lượt upload tài liệu/ngày cho tài khoản thường; kiểm tra định dạng file (MIME-Type Sniffing) ở tầng Backend để chặn hoàn toàn file thực thi .exe, .sh, .php, .bat; áp dụng cơ chế khóa dòng `lockForUpdate` chống Race Condition khi giao dịch điểm ví.
