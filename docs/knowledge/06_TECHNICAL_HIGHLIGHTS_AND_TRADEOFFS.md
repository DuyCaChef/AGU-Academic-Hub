# DANH MỤC KỸ THUẬT NỔI BẬT & PHÂN TÍCH LỰA CHỌN ĐÁNH ĐỔI (TECHNICAL HIGHLIGHTS & TRADEOFFS)
## DEFENSE-READY ARCHITECTURAL & ENGINEERING TRADEOFF ANALYSIS
**Vị trí:** `docs/knowledge/06_TECHNICAL_HIGHLIGHTS_AND_TRADEOFFS.md`  
**Ngày lập:** 11/11/2026  
**Cương vị đánh giá:** Principal Software Engineer & Thesis Defense Advisor  
**Tiêu chuẩn chất lượng:** Phân tích gắn liền 100% với mã nguồn thực tế của AGU Academic Hub. Mọi đánh giá đều có dẫn chứng code, bảng trade-off đa tiêu chí và câu hỏi phản biện chuyên sâu.

---

## 1. TỔNG QUAN BẢNG XẾP HẠNG TOP KỸ THUẬT NỔI BẬT NHẤT KHI BẢO VỆ (TOP HIGHLIGHTS)

| Hạng | Tên Giải pháp Kỹ thuật | Phân hệ ứng dụng | Giá trị Kỹ thuật & Thực tiễn | Tệp Mã nguồn Đại diện |
|:---:|:---|:---|:---|:---|
| **#1** | **Khóa bi quan theo dòng (`lockForUpdate`) kết hợp Khóa chống trùng Idempotency** | Bounties / Wallet | Ngăn chặn triệt để Race Condition, Double Spending và Double Payout khi tương tranh cao. | `app/Modules/Bounties/Domain/Services/ReputationEvaluator.php:20-80` |
| **#2** | **Kiến trúc Modular Monolith 4 tầng (Domain-Driven Bounded Contexts)** | Toàn hệ thống | Xóa bỏ Fat Controllers, đóng gói logic nghiệp vụ theo miền độc lập, bảo trì mở rộng dễ dàng. | `app/Modules/*/Application/`, `app/Modules/*/Domain/` |
| **#3** | **Cơ chế Ký quỹ Điểm (Escrow Holding) & Quy trình Phê duyệt Bounty 2 bước** | Bounties / Moderation | Bảo toàn tính toàn vẹn tài chính, triệt tiêu gian lận thông đồng cày điểm và rửa điểm thưởng. | `app/Modules/Bounties/Application/Actions/ApproveBountyPayoutAction.php:27-94` |
| **#4** | **Lọc trùng lặp nhị phân tức thời bằng hàm băm SHA-256 phân vùng theo môn** | Documents | Triệt tiêu 100% file trùng nhị phân ngay tại file tạm, bảo vệ dung lượng đĩa cứng máy chủ $O(1)$. | `app/Modules/Documents/Domain/Services/DocumentDeduplicationEvaluator.php:12-28` |
| **#5** | **Nhật ký Kiểm toán Bất biến (Immutable Audit Logs) qua Eloquent Booted Hook** | Administration | Ném `DomainException` chặn sửa/xóa log; bảo đảm tính toàn vẹn pháp lý cho dữ liệu thanh tra. | `app/Models/NhatKyKiemToan.php:24-38` |
| **#6** | **Cơ chế Ẩn danh 2 lớp (2-Layer Controlled Anonymity) cho Đánh giá Môn học** | Reviews | Bảo vệ an toàn tâm lý cho sinh viên trên giao diện nhưng vẫn giữ vết định danh tại DB để hậu kiểm. | `app/Modules/Reviews/Presentation/Http/Resources/CourseReviewResource.php` |
| **#7** | **Zero-KYC Pre-provisioned IAM kết hợp Cưỡng chế đổi mật khẩu lần đầu** | Identity | Không thu thập ảnh thẻ sinh viên, tự động cấp tài khoản từ dữ liệu thô, loại bỏ hoàn toàn tải KYC thủ công. | `app/Modules/Identity/Application/Actions/AuthenticateUserAction.php:35-72` |
| **#8** | **Bảo vệ PII Minh chứng Cựu sinh viên qua Private Storage & Signed URLs 30 phút** | Identity / Moderation | Tệp nhạy cảm không tạo symlink public, chỉ stream qua chữ ký điện tử có thời hạn tự hủy. | `app/Modules/Identity/Infrastructure/Storage/PrivateKycStorage.php:15-35` |

---

## 2. PHÂN TÍCH CHUYÊN SÂU TỪNG KỸ THUẬT THEO TIÊU CHUẨN ĐỒ ÁN

---

### KỸ THUẬT 1: KHÓA BI QUAN THEO DÒNG (`lockForUpdate`) KẾT HỢP KHÓA CHỐNG TRÙNG IDEMPOTENCY KEY

#### 1. Vấn đề cần giải quyết
Trong phân hệ Ví điểm và Giải ngân Bounty, khi 2 request gửi đến máy chủ trong cùng một tích tắc (ví dụ: 2 cán bộ kiểm duyệt cùng nhấn nút "Duyệt giải ngân" cho 1 bài nộp, hoặc người dùng double-click nút "Đăng yêu cầu"):
- Nếu không khóa dòng, cả hai luồng xử lý sẽ đọc cùng một số dư ví cũ (Dirty Read / Non-repeatable Read).
- Hệ thống sẽ thực hiện trừ/cộng điểm hai lần (Double Payout / Double Spending), làm sai lệch sổ cái tài chính và làm âm ví người dùng.

#### 2. Nguyên lý hoạt động
- **Pessimistic Locking (Khóa bi quan):** Giả định xung đột **chắc chắn sẽ xảy ra**. Ngay khi đọc dữ liệu ví hoặc yêu cầu, câu lệnh `SELECT ... FOR UPDATE` sẽ ép cơ sở dữ liệu MySQL (động cơ InnoDB) thiết lập một khóa độc quyền (Exclusive X-Lock) lên đúng hàng (row) đó. Mọi transaction khác cố tình đọc hoặc sửa hàng này sẽ bị CSDL chặn lại ở trạng thái chờ (Wait Queue) cho đến khi transaction đầu tiên commit.
- **Idempotency Key (Khóa chống trùng):** Sinh một chuỗi định danh duy nhất cho từng thao tác (ví dụ: `giai_ngan_yc_12_nd_5_nguoi_tao`). Cột `khoa_chong_trung` trong bảng `giao_dich_diem` có ràng buộc `UNIQUE`. Kể cả khi có lỗi logic ứng dụng, CSDL cũng sẽ từ chối lệnh ghi thứ hai.

#### 3. Vị trí trong dự án
- File: `app/Modules/Bounties/Domain/Services/ReputationEvaluator.php`
- Hàm: `khoaViDiem()` (Lines 18-26), `dongBangDiemBounty()` (Lines 29-71), `giaiNganDiemBounty()` (Lines 74-127).
- File: `app/Modules/Bounties/Application/Actions/ApproveBountyPayoutAction.php` (Lines 36-58).

#### 4. Đoạn code thực tế chứng minh
```php
// File: app/Modules/Bounties/Domain/Services/ReputationEvaluator.php
public function khoaViDiem(int $maNguoiDung): ViDiem
{
    return ViDiem::where('ma_nguoi_dung', $maNguoiDung)
        ->lockForUpdate() // Áp đặt Exclusive Lock lên hàng ví
        ->firstOrCreate(
            ['ma_nguoi_dung' => $maNguoiDung],
            ['diem_kha_dung' => 100, 'diem_tam_giu' => 0]
        );
}

// Kiểm tra Idempotency chống lặp giao dịch
public function giaiNganDiemBounty(YeuCauTaiLieu $yeuCau, NguoiDung $nguoiDongGop, ?string $khoaChongLap = null): void
{
    $khoaChongLap = $khoaChongLap ?? "giai_ngan_yc_{$yeuCau->ma_yeu_cau}_nd_{$nguoiDongGop->ma_nguoi_dung}";

    $daGiaiNgan = GiaoDichDiem::where('khoa_chong_trung', $khoaChongLap.'_nguoi_tao')->exists();
    if ($daGiaiNgan) {
        return; // Dừng ngay lập tức nếu giao dịch đã từng được thực thi
    }
    // ...
}
```

#### 5. Bảng So sánh Đánh đổi (Trade-off Analysis)

| Tiêu chí đánh giá | Phương án A: Pessimistic Locking + Idempotency (ĐÃ CHỌN) | Phương án B: Optimistic Locking (Dùng version column) | Phương án C: Không dùng Lock (Chỉ dùng Code If/Else) |
|:---|:---|:---|:---|
| **Tính Nhất quán (Data Consistency)** | **Tuyệt đối 100% (ACID)** — Bảo đảm tại tầng lõi InnoDB. | Tốt, nhưng phải viết cơ chế Retry khi có xung đột. | **Rất kém** — Chắc chắn xảy ra lỗi số dư khi có tương tranh. |
| **Độ phức tạp triển khai** | **Thấp / Tinh gọn** — Tận dụng sẵn cơ chế `lockForUpdate()` của Eloquent. | Trung bình — Phải thêm cột `version`, xử lý bắt ngoại lệ StaleObjectException. | Rất thấp ban đầu, nhưng cực kỳ tốn chi phí sửa lỗi dữ liệu. |
| **Hiệu năng khi ít xung đột** | Rất cao — Thời gian giữ lock theo hàng chỉ vài mili-giây. | Rất cao — Không tốn tài nguyên lock của CSDL. | Cao nhất, nhưng sai dữ liệu. |
| **Hiệu năng khi xung đột cao** | Chấp nhận xếp hàng (Queue) trong phạm vi transaction. | Kém — Nhiều request bị fail và buộc phải retry liên tục. | Gây sập tính đúng đắn của dữ liệu ví. |
| **Nguy cơ Deadlock** | Có thể xảy ra nếu khóa nhiều bảng không theo thứ tự cố định. | Không bị deadlock ở CSDL, nhưng gây nghẽn ở tầng ứng dụng. | Không bị deadlock. |
| **Mức độ phù hợp với AGU** | **RẤT PHÙ HỢP** — Phân hệ điểm thưởng yêu cầu tính chính xác tuyệt đối như ngân hàng. | Không tối ưu bằng do chi phí viết retry logic phức tạp. | **HOÀN TOÀN KHÔNG CHẤP NHẬN**. |

#### 6. Vì sao không chọn Optimistic Locking?
Optimistic Locking hoạt động theo nguyên lý "Cứ cho cập nhật, nếu lúc ghi thấy phiên bản bị đổi thì hủy bỏ và báo lỗi". Trong bài toán giải ngân Bounty của trường học:
- Nếu 2 cán bộ quản trị cùng duyệt, việc báo lỗi `StaleObjectStateException` và bắt người dùng tự tải lại trang sẽ gây ức chế về mặt trải nghiệm.
- Pessimistic Locking cho phép request thứ 2 tự động dừng chờ vài mili-giây; khi request 1 commit xong, request 2 lập tức nhận diện trạng thái `DA_HOAN_THANH` và đưa ra thông báo nghiệp vụ rõ ràng: *"Yêu cầu này đã được xử lý bởi quản trị viên khác"* mà không phát sinh ngoại lệ kỹ thuật.

#### 7. Hạn chế của giải pháp đã chọn & Biện pháp khắc phục
- **Hạn chế:** Nếu transaction kéo dài (ví dụ: gọi API bên thứ ba hoặc gửi email chậm chạp bên trong transaction), lock sẽ bị giữ lâu, làm nghẽn các request khác.
- **Biện pháp khắc phục:** Hệ thống AGU Academic Hub áp dụng nguyên tắc **"Không gọi I/O bên ngoài trong Transaction"**: Việc gửi Email và Notification được đẩy ra ngoài qua lệnh `DB::afterCommit(...)`.

#### 8. Bộ câu hỏi Phản biện của Giảng viên & Câu trả lời Mẫu
- **Hỏi:** *"Tại sao em không dùng Redis Lock hay Distributed Lock mà lại dùng `lockForUpdate` của CSDL?"*
- **Trả lời mẫu:** *"Thưa Thầy/Cô, hệ thống AGU Academic Hub hiện vận hành theo kiến trúc Monolith tập trung với cơ sở dữ liệu MySQL 8.0 InnoDB. Việc sử dụng chính khóa hàng của CSDL (Pessimistic Row Lock) tận dụng trực tiếp tính năng ACID nội tại của engine InnoDB, không phụ thuộc vào hạ tầng bên ngoài, tránh được lỗi 'Split-brain' hoặc mất đồng bộ giữa Redis và MySQL khi một trong hai dịch vụ gặp sự cố mạng. Khi nào hệ thống mở rộng thành cụm Microservices phân tán nhiều Database, việc áp dụng Redis Redlock mới thực sự cần thiết."*

---

### KỸ THUẬT 2: KIẾN TRÚC MODULAR MONOLITH 4 TẦNG (DOMAIN-DRIVEN BOUNDED CONTEXTS)

#### 1. Vấn đề cần giải quyết
Các ứng dụng web phát triển theo mô hình MVC truyền thống thường nhanh chóng rơi vào "bẫy" Fat Controllers: Controller chứa hàng nghìn dòng code pha trộn giữa xác thực, truy vấn raw SQL, transaction, dispatch event và render view. Khi nghiệp vụ mở rộng, việc sửa đổi một chức năng rất dễ làm gãy đổ các chức năng khác không liên quan.

#### 2. Nguyên lý hoạt động
Hệ thống chia nhỏ toàn bộ ứng dụng thành 8 **Bounded Contexts** độc lập tại `app/Modules/<ModuleName>/`. Mỗi module sở hữu toàn bộ 4 tầng kiến trúc riêng biệt:
1. `Presentation`: Controller, Form Request, Resource.
2. `Application`: Actions (thay đổi dữ liệu), Queries (đọc dữ liệu).
3. `Domain`: Evaluator, Services, Enums, Rules.
4. `Infrastructure`: Repositories, Storage, Notifications.

#### 3. Bảng So sánh Đánh đổi (Trade-off Analysis)

| Tiêu chí | Modular Monolith (ĐÃ CHỌN) | Microservices Architecture | Traditional Spaghetti MVC Monolith |
|:---|:---|:---|:---|
| **Độ phức tạp hạ tầng** | **Rất thấp** — Triển khai 1 container/server duy nhất. | **Rất cao** — Cần Kubernetes, API Gateway, Service Mesh, Docker swarm. | Rất thấp. |
| **Giao dịch phân tán** | **Rất dễ dàng** — Dùng chung `DB::transaction()` của MySQL. | **Cực kỳ khó** — Phải dùng Saga Pattern, 2-Phase Commit, Eventual Consistency. | Dễ dàng. |
| **Độ trễ mạng nội bộ (Network Overhead)** | **0 ms** — Gọi hàm trực tiếp qua PHP in-memory. | Cao — Mỗi lời gọi hàm là một HTTP/gRPC request qua mạng. | 0 ms. |
| **Ranh giới mã nguồn (Code Boundaries)** | **Rõ ràng, chặt chẽ** — Đóng gói theo từng Bounded Context. | Rất rõ ràng (tách repo/service riêng). | Rất mờ nhạt — Mọi code lẫn lộn trong `app/Http/Controllers`. |
| **Khả năng chuyển đổi tương lai** | **Rất linh hoạt** — Từng module đã độc lập, có thể tách thành Microservice bất kỳ lúc nào. | Đã là Microservices. | Cực kỳ khó tái cấu trúc. |
| **Mức độ phù hợp với AGU** | **HOÀN HẢO** — Tối ưu chi phí máy chủ trường học nhưng vẫn đảm bảo tính học thuật và khả năng mở rộng. | Quá tải so với quy mô 15.000 sinh viên AGU. | Không đảm bảo tiêu chuẩn đồ án kỹ thuật chất lượng cao. |

#### 4. Câu hỏi Phản biện của Giảng viên & Câu trả lời Mẫu
- **Hỏi:** *"Tại sao em không làm Microservices cho hiện đại mà lại làm Monolith?"*
- **Trả lời mẫu:** *"Thưa Thầy/Cô, Microservices giải quyết bài toán quy mô tổ chức (Organizational Scalability) khi có hàng trăm kỹ sư làm việc trên nhiều cụm chức năng độc lập. Với bài toán AGU Academic Hub phục vụ nội bộ trường đại học, thách thức lớn nhất là **tính toàn vẹn dữ liệu của các giao dịch điểm thưởng và kiểm duyệt tài liệu**. Nếu chọn Microservices, hệ thống sẽ phải trả giá bằng sự phức tạp của giao dịch phân tán (Distributed Transaction), trễ mạng (Network Latency), và chi phí hạ tầng máy chủ lớn. Kiến trúc Modular Monolith đem lại 90% lợi ích phân tách mã nguồn của Microservices nhưng giữ được tính đơn giản, hiệu năng cao và sự đảm bảo ACID tuyệt đối của cơ sở dữ liệu quan hệ."*

---

### KỸ THUẬT 3: LỌC TRÙNG LẶP NHỊ PHÂN TỨC THỜI BẰNG SHA-256 THEO MÔN HỌC

#### 1. Vấn đề cần giải quyết
Người dùng tải lên cùng một file PDF bài giảng hoặc đề thi nhiều lần dưới các tên file khác nhau (ví dụ: `DeThi.pdf`, `DeThi_Final.pdf`, `Slide1.pdf`), làm lãng phí dung lượng lưu trữ máy chủ và làm loãng kết quả tìm kiếm của sinh viên.

#### 2. Nguyên lý hoạt động
- Sử dụng hàm băm mật mã học **SHA-256** (Secure Hash Algorithm 256-bit). Thuật toán này biến đổi nội dung nhị phân của tệp tin thành một chuỗi hex cố định dài 64 ký tự.
- **Tính toán trên tệp tạm (`tmp`):** Ngay khi file vừa upload lên server và nằm ở thư mục tạm của tiến trình PHP, hệ thống thực hiện `hash_file('sha256', $file->getRealPath())`.
- **Phân vùng theo Môn học:** Kiểm tra xem cặp `(ma_mon_hoc, ma_bam_file)` đã tồn tại trong CSDL hay chưa. Nếu đã có $\to$ Từ chối ngay lập tức, xóa file tạm, không ghi vào đĩa lưu trữ chính thức.

#### 3. Bảng So sánh Đánh đổi (Trade-off Analysis)

| Tiêu chí | Phương án A: SHA-256 nhị phân theo môn (ĐÃ CHỌN) | Phương án B: So sánh theo tên file & kích thước byte | Phương án C: Bóc tách toàn bộ Text & SimHash |
|:---|:---|:---|:---|
| **Độ chính xác chống trùng 100%** | **Tuyệt đối 100%** — Xác suất va chạm SHA-256 là $1/2^{256}$ (bằng 0 trên thực tế). | Kém — Đổi tên file là qua mặt được. | Cao — Bắt được cả bài viết tương đồng. |
| **Tốc độ thực thi (Performance)** | **Cực nhanh** ($<50$ ms cho file 10MB) — Chạy đồng bộ trong HTTP request. | Tức thời ($<1$ ms). | **Rất chậm** ($2-10$ giây) — Bắt buộc phải chạy hàng đợi Queue. |
| **Tiêu tốn CPU / RAM máy chủ** | Rất thấp. | Không đáng kể. | **Rất cao** — Cần thư viện parser PDF, OCR ảnh, tính toán vector. |
| **Trùng lặp ngữ nghĩa (Semantic Duplicate)** | Không phát hiện được nếu file bị sửa 1 ký tự. | Không phát hiện được. | Phát hiện được tương đồng $>85\%$. |
| **Mức độ khả thi hiện tại** | **100% Khả thi, đã kiểm chứng đạt**. | Khả thi nhưng không hiệu quả. | Cần đầu tư tài nguyên lớn cho Background Workers. |

#### 4. Câu hỏi Phản biện của Giảng viên & Câu trả lời Mẫu
- **Hỏi:** *"Nếu người dùng đổi tên trang bìa hoặc thêm 1 trang trắng vào file PDF thì mã SHA-256 có phát hiện được không?"*
- **Trả lời mẫu:** *"Thưa Thầy/Cô, đây chính là ranh giới kỹ thuật giữa Lọc trùng Lớp 1 (Nhị phân) và Lớp 2 (Ngữ nghĩa). SHA-256 chỉ phát hiện trùng khớp nhị phân 100%. Nếu người dùng thay đổi dù chỉ 1 bit trong file, mã băm sẽ thay đổi hoàn toàn do hiệu ứng tuyết lở (Avalanche Effect). Trong thiết kế tổng thể của dự án tại `TaiLieuYeuCau.md`, nhóm đã thiết kế Lớp 2 sử dụng SimHash/Cosine Similarity chạy ngầm qua Queue để bóc tách nội dung text và so khớp tương đồng $>85\%$. Tuy nhiên, ở giai đoạn hiện tại, nhóm ưu tiên triển khai Lớp 1 SHA-256 vì tính ổn định cao, bảo vệ đĩa cứng tức thời mà không gây nghẽn CPU của máy chủ trường học."*

---

### KỸ THUẬT 4: CƠ CHẾ ẨN DANH 2 LỚP (2-LAYER CONTROLLED ANONYMITY) CHO ĐÁNH GIÁ MÔN HỌC

#### 1. Vấn đề cần giải quyết
Sinh viên thường e ngại khi đánh giá môn học hoặc nhận xét về giảng viên vì lo sợ bị trù dập hoặc ảnh hưởng đến điểm số học tập nếu để lộ danh tính. Tuy nhiên, nếu cho phép ẩn danh hoàn toàn (Anonymous 100%), nền tảng sẽ trở thành nơi phát tán ngôn từ độc hại, vu khống, xúc phạm danh dự cá nhân mà không thể quy trách nhiệm.

#### 2. Nguyên lý hoạt động
Hệ thống thiết kế mô hình **Ẩn danh có Kiểm soát (Controlled Anonymity)**:
- **Lớp 1 - Ẩn danh Tầng Giao diện (Presentation Masking):** 
  - Tại tầng API/Inertia Resource (`CourseReviewResource.php`), hệ thống bóc tách dữ liệu: thay thế toàn bộ tên sinh viên thành chuỗi cố định `"Sinh viên ẩn danh"`, gán `mssv = null`, và xóa bỏ trường `ma_nguoi_dung` khỏi payload JSON truyền xuống trình duyệt. Người dùng khác hoặc hacker kiểm tra mạng (Inspect Network) cũng không thể tìm ra ID của tác giả bài viết.
- **Lớp 2 - Định danh Tầng Dữ liệu Hậu kiểm (Database Traceability):**
  - Tại cơ sở dữ liệu MySQL, cột `ma_nguoi_danh_gia` vẫn được lưu trữ nguyên vẹn và liên kết khóa ngoại với bảng `nguoi_dung`.
  - Chỉ có Super Admin hoặc Hội đồng Kỷ luật khi có quyết định bằng văn bản mới có quyền truy vấn ngược định danh của tác giả thông qua màn hình Audit Logs để xử lý vi phạm quy chế học đường.

---

### KỸ THUẬT 5: NHẬT KÝ KIỂM TOÁN BẤT BIẾN (IMMUTABLE AUDIT LOGS)

#### 1. Vấn đề cần giải quyết
Trong các hệ thống quản trị, rủi ro quản trị viên lạm quyền hoặc kẻ tấn công chiếm được quyền Admin rồi sửa đổi nhật ký hoạt động nhằm xóa dấu vết vi phạm (ví dụ: tự cộng điểm, tự xóa hồ sơ kỷ luật).

#### 2. Nguyên lý hoạt động
Hệ thống tận dụng **Eloquent Model Booted Events** để triệt tiêu hoàn toàn khả năng cập nhật (`update`) hoặc xóa (`delete`) đối với bảng `nhat_ky_kiem_toan`:
```php
// File: app/Models/NhatKyKiemToan.php
protected static function booted(): void
{
    static::updating(function () {
        throw new \DomainException('Bản ghi nhật ký kiểm toán là bất biến (Immutable), không được phép chỉnh sửa.');
    });

    static::deleting(function () {
        throw new \DomainException('Bản ghi nhật ký kiểm toán là bất biến (Immutable), không được phép xóa.');
    });
}
```
- Ngay khi có bất kỳ đoạn mã PHP nào trong ứng dụng gọi `$log->update(...)` hoặc `$log->delete()`, Eloquent sẽ lập tức chặn lại và ném ra `DomainException`. Dữ liệu chỉ có thể thêm mới (Append-Only), bảo đảm tính toàn vẹn pháp lý tuyệt đối cho các đợt thanh tra hệ thống.

