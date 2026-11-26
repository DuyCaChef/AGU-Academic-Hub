# CẨM NANG BẢO VỆ ĐỒ ÁN & BỘ CÂU HỎI PHẢN BIỆN CHUYÊN SÂU (THESIS DEFENSE MASTER GUIDE)
## 8-LEVEL DEFENSE QUESTIONS, STRUCTURED MODEL ANSWERS & COUNTER-ATTACK STRATEGIES
**Vị trí:** `docs/knowledge/11_THESIS_DEFENSE_GUIDE.md`  
**Ngày lập:** 11/11/2026  
**Cương vị cố vấn:** Thesis Defense Advisor & Former Evaluation Panel Chair  
**Mục tiêu:** Huấn luyện chủ dự án trả lời tự tin, sắc bén và thuyết phục trước mọi câu hỏi hóc búa của Hội đồng Giảng viên đánh giá đồ án tốt nghiệp / đồ án môn học.

---

## 1. NGUYÊN TẮC VÀNG KHI ĐỨNG TRƯỚC HỘI ĐỒNG PHẢN BIỆN

1. **Không học thuộc lòng định nghĩa sách giáo khoa:** Giảng viên không muốn nghe bạn đọc lại định nghĩa "Khóa bi quan là gì" hay "Modular Monolith là gì". Giảng viên muốn biết: **Tại sao dự án của em cần nó? Không có nó thì hệ thống sẽ chết ở chỗ nào? Em đã code nó ở đâu?**
2. **Cấu trúc câu trả lời 5 bước (The 5-Step Defense Framework):**
   - **Bước 1 - Khẳng định cốt lõi:** Trả lời trực diện vào câu hỏi trong 1-2 câu đầu.
   - **Bước 2 - Cơ chế kỹ thuật:** Giải thích ngắn gọn bản chất vận hành bên dưới.
   - **Bước 3 - Dẫn chứng thực tế AGU:** Nêu rõ ngữ cảnh nghiệp vụ trong dự án.
   - **Bước 4 - Bằng chứng mã nguồn:** Chỉ ra file path, class hoặc hàm cụ thể đã lập trình.
   - **Bước 5 - Đánh đổi & Hạn chế:** Chủ động nêu điểm yếu và hướng khắc phục trước khi giảng viên kịp "bắt bẻ".
3. **Thứ bậc bằng chứng:** Luôn dùng từ ngữ gắn với hiện trạng:
   - *"Tính năng này nhóm em đã cài đặt tại file X và có bộ test tự động xác minh tại file Y..."* (SOURCE & TEST EVIDENCE).
   - *"Về thuật toán SimHash ngữ nghĩa, nhóm em đã hoàn thành thiết kế kiến trúc tại tài liệu Z và đặt mục tiêu tích hợp ở giai đoạn tiếp theo..."* (DESIGNED ONLY).

---

## 2. BỘ CÂU HỎI & CÂU TRẢ LỜI MẪU THEO 8 CẤP ĐỘ

---

### CẤP ĐỘ 1: TỔNG QUAN & BỐI CẢNH ĐỀ TÀI [MỨC ĐỘ: BASIC]

#### Câu hỏi 1.1: *"Tại sao sinh viên đã có Facebook, Zalo, Google Drive để chia sẻ tài liệu rồi mà em lại phải xây dựng AGU Academic Hub?"*
- **1. Trả lời ngắn:** *"Dạ thưa Thầy/Cô, các mạng xã hội mở phục vụ giao tiếp tức thời nhưng hoàn toàn thất bại trong việc **quản trị tri thức học thuật có cấu trúc**. AGU Academic Hub giải quyết 3 bài toán mà mạng xã hội không thể làm được: phân loại tài liệu chuẩn theo mã học phần, cơ chế ký quỹ điểm thưởng cân bằng cung-cầu, và bảo vệ tài liệu quý bằng quyền sở hữu cộng đồng chống bị xóa tùy tiện."*
- **2. Giải thích sâu:** Trên mạng xã hội, tài liệu bị trôi bài sau vài ngày, liên kết tải thường xuyên bị chết, và tràn ngập file rác trùng lặp. Đặc biệt, khi sinh viên đăng bài lên các nhóm công khai, họ không có kênh an toàn để đánh giá thật về độ khó của môn học do tâm lý e ngại lộ danh tính.
- **3. Ngữ cảnh AGU:** Sinh viên khóa mới của AGU gặp khó khăn khi tìm kiếm đề thi cũ của các môn như Lập trình nâng cao (`COS101`) hay Toán rời rạc. Với hệ thống này, chỉ cần gõ mã môn, toàn bộ đề thi, slide, bài tập mẫu và nhận xét của các khóa trước được hiển thị đầy đủ và có kiểm duyệt.
- **4. Code chứng minh:** Giao diện tích hợp hạt nhân tại `resources/js/Pages/Courses/Show.jsx` kết nối trực tiếp Kho tài liệu, Đánh giá môn học và Danh sách săn thưởng theo từng mã học phần.
- **5. Câu hỏi giảng viên có thể truy vấn tiếp:** *"Làm sao em lôi kéo được sinh viên chịu đóng góp tài liệu lên đây thay vì đăng lên Facebook?"*
  - *Cách đối đáp:* Nêu cơ chế **Gamification & Bounty**: Đóng góp tài liệu hay được cộng điểm uy tín và điểm thưởng ví; điểm đó dùng để yêu cầu các tài liệu hiếm khác từ cựu sinh viên.

---

### CẤP ĐỘ 2: NGHIỆP VỤ & QUY TRÌNH HỌC THUẬT [MỨC ĐỘ: INTERMEDIATE]

#### Câu hỏi 2.1: *"Tại sao khi sinh viên yêu cầu (SV A) đã bấm xác nhận tài liệu rồi mà hệ thống không cộng điểm ngay cho người đóng góp (SV B), mà lại phải chờ Admin/Mod duyệt giải ngân?"*
- **1. Trả lời ngắn:** *"Dạ thưa Thầy/Cô, đây là **quy trình phê duyệt 2 bước có chủ đích** của nhóm em nhằm ngăn chặn nguy cơ **gian lận thông đồng cày điểm thưởng (Collusion Attack)** và ngăn chặn việc giải ngân điểm cho các tài liệu rác hoặc tài liệu chứa mã độc."*
- **2. Giải thích sâu:** Nếu hệ thống giải ngân tự động ngay khi SV A xác nhận, một sinh viên có thể tạo 2 tài khoản (A và B): A đăng một yêu cầu treo thưởng 100 điểm, B nộp một file rác bất kỳ, rồi A lập tức bấm xác nhận để chuyển toàn bộ điểm sang cho B nhằm cày điểm uy tín hoặc trục lợi điểm thưởng.
- **3. Ngữ cảnh AGU:** Bước 1 cho phép SV A nghiệm thu nội dung có đáp ứng đúng nhu cầu học của mình hay không. Bước 2 đưa vào hàng đợi `admin.bounties.payout-queue` để cán bộ kiểm duyệt thẩm định tính hợp pháp, kiểm tra bản quyền và xác nhận không có hành vi gian lận trước khi tiền tệ trong ví được chính thức giải ngân.
- **4. Code chứng minh:** 
  - Bước 1 chỉ chuyển trạng thái sang `CHO_GIAI_NGAN`: `app/Modules/Bounties/Application/Actions/ConfirmBountyFulfillmentAction.php:48`.
  - Bước 2 thực chi điểm: `app/Modules/Bounties/Application/Actions/ApproveBountyPayoutAction.php:64`.
- **5. Trade-off:** Tạo ra độ trễ giải ngân cho người nộp (phải chờ cán bộ duyệt), nhưng đổi lại bảo đảm an toàn 100% cho nền kinh tế điểm thưởng nội bộ của nhà trường.

---

### CẤP ĐỘ 3: KIẾN TRÚC HỆ THỐNG [MỨC ĐỘ: ADVANCED]

#### Câu hỏi 3.1: *"Tại sao em chọn kiến trúc Modular Monolith thay vì Microservices hoặc Monolith MVC truyền thống?"*
- **1. Trả lời ngắn:** *"Dạ thưa Thầy/Cô, Modular Monolith đem lại lợi ích phân tách mã nguồn độc lập theo Bounded Context của Domain-Driven Design (DDD), nhưng vẫn giữ được **tính toàn vẹn giao dịch ACID tuyệt đối của CSDL quan hệ tập trung** mà không phải gánh chịu chi phí hạ tầng và độ phức tạp phân tán của Microservices."*
- **2. Giải thích sâu:** 
  - So với Spaghetti MVC: MVC truyền thống dồn toàn bộ code vào `app/Http/Controllers`, khi sửa đổi một controller rất dễ phá vỡ các chức năng khác. Modular Monolith chia thành 8 module độc lập (`Identity`, `Documents`, `Bounties`...), mỗi module có đủ 4 tầng riêng biệt.
  - So với Microservices: Microservices đòi hỏi mạng lưới máy chủ phức tạp, giao tiếp qua gRPC/HTTP làm tăng độ trễ (latency), và việc duy trì tính nhất quán khi giải ngân điểm hay thu hồi điểm sẽ buộc phải dùng Saga Pattern hoặc 2-Phase Commit cực kỳ phức tạp và dễ phát sinh lỗi.
- **3. Ngữ cảnh AGU:** Hệ thống phục vụ nội bộ trường học với khoảng 15.000 sinh viên. Triển khai 1 container/server duy nhất chạy Modular Monolith giúp nhà trường tiết kiệm tối đa ngân sách vận hành máy chủ, nhưng mã nguồn đã được đóng gói độc lập, sẵn sàng bóc tách thành Microservice trong tương lai nếu trường có nhu cầu mở rộng quy mô toàn ĐHQG.
- **4. Code chứng minh:** Cấu trúc module tại `app/Modules/` và `app/Modules/README.md`.

---

### CẤP ĐỘ 4: BACKEND & XỬ LÝ TƯƠNG TRANH [MỨC ĐỘ: EXPERT / DEFENSE CHALLENGE]

#### Câu hỏi 4.1: *"Trong hàm giải ngân Bounty, em dùng `lockForUpdate()`. Hãy giải thích bản chất nó hoạt động thế nào ở tầng Database? Nếu có 2 Admin cùng bấm duyệt một lúc thì điều gì sẽ xảy ra?"*
- **1. Trả lời ngắn:** *"Dạ thưa Thầy/Cô, `lockForUpdate()` kích hoạt cơ chế **Khóa độc quyền theo dòng (Exclusive Row-Level X-Lock)** của động cơ MySQL InnoDB bên trong một Transaction. Khi 2 Admin cùng bấm duyệt một lúc, Request 1 sở hữu khóa sẽ thực thi giải ngân; Request 2 bắt buộc phải xếp hàng chờ ở tầng CSDL, sau đó khi lấy được khóa sẽ bị State Guard chặn lại vì trạng thái yêu cầu đã đổi thành `DA_HOAN_THANH`, hoàn toàn không thể giải ngân lần hai."*
- **2. Giải thích sâu:**
  - Khi Request 1 chạy: `SELECT * FROM yeu_cau_tai_lieu WHERE ma_yeu_cau = 12 FOR UPDATE`. MySQL khóa đúng hàng số 12.
  - Request 2 chạy cùng câu lệnh đó: Bị MySQL đưa vào hàng đợi chờ (Lock Wait), không được đọc dữ liệu tiếp.
  - Request 1 kiểm tra `trang_thai === 'CHO_GIAI_NGAN'`, tiến hành trừ điểm tạm giữ của A, cộng điểm khả dụng cho B, ghi sổ cái `GiaoDichDiem`, đổi trạng thái Bounty thành `DA_HOAN_THANH`, và thực thi `COMMIT`.
  - Lúc này, khóa của hàng số 12 mới được giải phóng.
  - Request 2 lập tức được trao quyền đọc hàng số 12: Dữ liệu lúc này đã là `trang_thai === 'DA_HOAN_THANH'`.
  - Khối kiểm tra trạng thái (State Guard) tại dòng 37 của Action:
    ```php
    if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN) {
        return ['success' => false, 'message' => 'Yêu cầu này đã được xử lý bởi quản trị viên khác.'];
    }
    ```
    lập tức bắt được sự thay đổi, dừng luồng an toàn và thông báo cho Admin 2 biết yêu cầu đã được xử lý xong.
- **3. Lớp bảo vệ bổ sung:** Nếu có sự cố bất thường vượt qua được tầng code, cột `khoa_chong_trung` trong bảng `giao_dich_diem` có ràng buộc `UNIQUE INDEX` sẽ ném ngoại lệ `Integrity constraint violation: 1062 Duplicate entry` và tự động rollback giao dịch.
- **4. Code chứng minh:** 
  - File `app/Modules/Bounties/Application/Actions/ApproveBountyPayoutAction.php:36-58`.
  - Kịch bản kiểm thử: `tests/Feature/PostBountyCorrectionWorkflowTest.php:test_double_payout_concurrency_protection`.

---

### CẤP ĐỘ 5: DATABASE & TOÀN VẸN DỮ LIỆU [MỨC ĐỘ: ADVANCED]

#### Câu hỏi 5.1: *"Làm thế nào em bảo đảm số dư ví điểm trong hệ thống không bao giờ bị âm khi xảy ra lỗi hoặc khi thu hồi điểm (Clawback)?"*
- **1. Trả lời ngắn:** *"Dạ thưa Thầy/Cô, hệ thống bảo vệ số dư ví qua **3 tầng chốt chặn độc lập**: Tầng 1 là Domain Validation trong code PHP, Tầng 2 là Khóa dòng bi quan trong Transaction, và Tầng 3 là **Ràng buộc kiểm tra toàn vẹn CSDL (Database Check Constraint)** `CHECK (diem_kha_dung >= 0)` tại tầng lõi MySQL."*
- **2. Giải thích sâu:** Khi thu hồi điểm thưởng (Clawback) do tài liệu bị phát hiện vi phạm bản quyền:
  - Nếu số dư ví của kẻ vi phạm còn đủ điểm: Hệ thống trừ điểm khả dụng bình thường.
  - Nếu kẻ vi phạm đã rút hoặc chi tiêu hết điểm (`diem_kha_dung < diemThuong`): Thay vì trừ âm ví, hệ thống chỉ khấu trừ tối đa số điểm hiện có về 0, đồng thời trừ mạnh vào **Điểm uy tín học thuật (`diem_uy_tin`)** và tự động chuyển tài khoản sang trạng thái `TAM_KHOA` để xử lý kỷ luật.
  - Bất kỳ câu lệnh SQL nào cố tình ghi số âm vào cột `diem_kha_dung` đều bị động cơ MySQL InnoDB từ chối ngay lập tức bằng lỗi vi phạm ràng buộc `chk_vi_diem_kha_dung`.
- **3. Code chứng minh:**
  - Migration: `database/migrations/2026_10_14_000008_create_vi_diem_va_yeu_cau_tai_lieus_table.php`.
  - Evaluator: `app/Modules/Moderation/Domain/Services/BountyClawbackHandler.php:40-75`.

---

### CẤP ĐỘ 6: BẢO MẬT & PHÂN QUYỀN [MỨC ĐỘ: ADVANCED]

#### Câu hỏi 6.1: *"Làm thế nào em bảo vệ ảnh bằng tốt nghiệp của Cựu sinh viên nộp KYC không bị người lạ tải về hoặc rò rỉ trên Internet?"*
- **1. Trả lời ngắn:** *"Dạ thưa Thầy/Cô, nhóm em áp dụng mô hình **Phân vùng Lưu trữ Nhạy cảm (Private Storage) kết hợp URL có Chữ ký số Thời hạn (Temporary Signed URL 30 phút)**. Toàn bộ file KYC nằm ngoài thư mục web public, không có liên kết trực tiếp, và chỉ cán bộ kiểm duyệt có chữ ký hợp lệ mới xem được file."*
- **2. Giải thích sâu:**
  - File được lưu tại `storage/app/private/kyc/` và đổi tên thành UUID ngẫu nhiên.
  - Không tạo symlink ra `public/storage/`. Nếu ai đó gõ trực tiếp URL file trên trình duyệt sẽ nhận lỗi `404 Not Found`.
  - Khi Moderator mở hàng đợi duyệt KYC, hệ thống sinh một liên kết tạm thời qua `URL::temporarySignedRoute('kyc.preview', now()->addMinutes(30), ...)`.
  - Chữ ký điện tử HMAC-SHA256 được mã hóa bằng `APP_KEY` bí mật của máy chủ. Sau 30 phút, link tự động vô hiệu hóa.
  - Route xem ảnh có Middleware kiểm tra cả 2 lớp: `signed` và `role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN`.
- **3. Code chứng minh:** `app/Modules/Identity/Infrastructure/Storage/PrivateKycStorage.php:20-35`.

---

### CẤP ĐỘ 7: ĐÁNH ĐỔI & MỞ RỘNG QUY MÔ [MỨC ĐỘ: EXPERT]

#### Câu hỏi 7.1: *"Nếu ngày mai nhà trường có 100.000 sinh viên cùng truy cập tải đề thi trước giờ thi, kiến trúc hiện tại sẽ nghẽn ở đâu đầu tiên và em giải quyết thế nào?"*
- **1. Trả lời ngắn:** *"Dạ thưa Thầy/Cô, điểm nghẽn đầu tiên sẽ là **Disk I/O và băng thông mạng khi stream file PDF từ máy chủ ứng dụng**, tiếp theo là nghẽn CPU CSDL khi hàng chục nghìn lượt cùng truy vấn bảng danh mục môn học."*
- **2. Giải pháp giải quyết theo lộ trình thực tế:**
  1. **Giải phóng Disk I/O:** Chuyển phân vùng lưu trữ tệp học thuật từ Local Disk sang dịch vụ lưu trữ đám mây tương thích S3 (như **MinIO** nội bộ trường hoặc **AWS S3 / Cloudflare R2**), kết hợp CDN (Cloudflare) để cache các file tĩnh. Trình duyệt người dùng sẽ tải file trực tiếp từ CDN/S3 qua Pre-signed URL, máy chủ Laravel hoàn toàn không phải tốn RAM/CPU để stream file.
  2. **Giải phóng CSDL:** Áp dụng **Redis Cache** cho các tầng Query: Caching danh mục Khoa, Ngành và thông tin Môn học trong 24 giờ qua lệnh `Cache::remember()`. Tách cụm CSDL MySQL thành mô hình **Master-Slave (Read-Write Splitting)**: 1 node Master chuyên ghi giao dịch điểm và nộp bài, các node Slave phục vụ hàng chục nghìn truy vấn đọc danh mục.
- **3. Code chứng minh hiện tại:** Hệ thống đã chuẩn bị sẵn tầng Storage Driver độc lập (`DocumentFileStorage.php`), chỉ cần đổi cấu hình `FILESYSTEM_DISK=s3` trong file `.env` mà không cần viết lại mã nguồn ứng dụng.

---

### CẤP ĐỘ 8: PHẢN BIỆN SÂU & ĐÁNH GIÁ TRUNG THỰC [MỨC ĐỘ: DEFENSE CHALLENGE]

#### Câu hỏi 8.1: *"Điểm yếu lớn nhất hoặc điều em cảm thấy tiếc nuối nhất trong kiến trúc hiện tại của dự án là gì?"*
- **1. Trả lời ngắn & Trung thực:** *"Dạ thưa Thầy/Cô, điểm tiếc nuối lớn nhất của nhóm em là **chưa hoàn thiện Lớp 2 của Bộ lọc Trùng lặp Nội dung (SimHash / Cosine Similarity)**, và **vấn đề Vùng chết xóa tài liệu của tác giả (Deadlock Zone)** giữa mốc 1 đến 9 lượt tải."*
- **2. Giải thích chi tiết:**
  - Về lọc trùng: Hệ thống hiện tại mới chỉ lọc trùng nhị phân 100% bằng SHA-256 (`DocumentDeduplicationEvaluator.php`). Nếu sinh viên đổi trang bìa file PDF thì mã băm sẽ khác và hệ thống chưa phát hiện được mức độ tương đồng ngữ nghĩa. Nhóm đã thiết kế thuật toán SimHash trong tài liệu `TaiLieuYeuCau.md` nhưng cần thêm Background Queue Workers chuyên trách để bóc tách text.
  - Về vùng chết xóa tài liệu: Quy tắc hiện tại chỉ cho tác giả tự xóa khi $0$ lượt tải và trong $24\text{h}$; tài liệu $\ge 10$ tải mới được nộp đơn xin gỡ. Điều này vô tình làm kẹt các tài liệu có từ 1 đến 9 lượt tải. Nhóm em đã ghi nhận rõ ràng khiếm khuyết này trong báo cáo `02_PROJECT_CONFLICTS_AND_GAPS.md` và đã có phương án nới lỏng điều kiện nộp đơn xin gỡ để ban kiểm duyệt hỗ trợ sinh viên trong bản cập nhật tới.
- **3. Đánh giá của Hội đồng:** Sự trung thực, khả năng tự nhận diện điểm yếu kỹ thuật và đưa ra giải pháp rõ ràng chính là phẩm chất được đánh giá cao nhất của một kỹ sư phần mềm chuyên nghiệp.

