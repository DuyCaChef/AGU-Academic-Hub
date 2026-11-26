# BIÊN BẢN CÁC QUYẾT ĐỊNH CẦN THỐNG NHẤT LIÊN PHÂN HỆ
## AGU Academic Hub — Cross-Module Architecture Decisions Required
**Bên tham gia:**
- **Đại diện Phân hệ Sinh viên & Cựu sinh viên (Student/Alumni):** Người phụ trách hiện tại
- **Đại diện Phân hệ Quản trị & Kiểm duyệt (Admin/Moderator):** Thành viên đồng phát triển  
**Chủ trì thẩm định:** Senior Software Architect  
**Mục tiêu:** Thống nhất dứt điểm 05 quyết định then chốt trước khi tiến hành bước nghiệm thu và hoàn thiện mã nguồn, chấm dứt tình trạng tranh cãi hoặc triển khai lệch pha giữa hai nhóm.

---

## DANH MỤC 05 QUYẾT ĐỊNH BẮT BUỘC (DECISION REGISTER)

---

### QUYẾT ĐỊNH 1: QUYỀN SỞ HỮU VÀ VỊ TRÍ MÃ NGUỒN CỦA HÀNG ĐỢI DUYỆT GIẢI NGÂN BOUNTY
- **Mã quyết định:** `DEC-01`
- **Mức độ ưu tiên:** **P0 (Bắt buộc chốt trước khi nghiệm thu)**
- **Vấn đề cần quyết định:**
  - Hàng đợi duyệt giải ngân điểm thưởng Bounty (`/quan-tri/yeu-cau-tai-lieu/cho-giai-ngan`) và Controller `AdminBountyPayoutController.php` nên được bàn giao chính thức cho Module nào quản lý?
- **Bối cảnh:**
  - Tài liệu `ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md` xem đây là Nghiệp vụ số 15 của Admin/Moderator.
  - Tuy nhiên, hiện tại toàn bộ Controller, Query, Action và Routes liên quan lại được lập trình viên nhóm Student đặt bên trong thư mục `app/Modules/Bounties/`.
- **Các phương án xem xét:**
  - **Phương án 1 (Giữ nguyên vị trí & Đóng băng mã nguồn - Recommended):**
    - *Mô tả:* Giữ nguyên toàn bộ mã nguồn tại `app/Modules/Bounties/Presentation/Http/Controllers/AdminBountyPayoutController.php` và routes tại `app/Modules/Bounties/routes.php`. Lập trình viên hai nhóm cùng ký cam kết đóng băng (Code Freeze) các file này, không di chuyển namespace để tránh vỡ các Feature Tests và các import Inertia trên frontend (`Admin/Bounties/PayoutQueue.jsx`).
    - *Ưu điểm:* Rủi ro bằng 0; không tốn công refactor; bảo toàn 100% các test đang chạy đạt.
    - *Nhược điểm:* Cấu trúc thư mục chưa hoàn toàn "chuẩn mực giáo khoa" về phân tách module monorepo.
  - **Phương án 2 (Di dời toàn bộ sang `app/Modules/Moderation/`):**
    - *Mô tả:* Chuyển Controller sang `app/Modules/Moderation/Presentation/Http/Controllers/AdminBountyPayoutController.php`, chuyển routes sang `Moderation/routes.php`.
    - *Ưu điểm:* Đạt tính cô lập module lý tưởng (Admin code nằm trong Admin/Mod module).
    - *Nhược điểm:* Nguy cơ gãy route, gãy test, phải cập nhật lại namespace trong nhiều file, tốn thêm 3-4 giờ kiểm thử lại.
- **Phương án đề xuất của Architect:** **CHỌN PHƯƠNG ÁN 1** cho đợt nghiệm thu hiện tại. Ghi nhận nợ kỹ thuật refactor cho phiên bản v2.0 sau khi đã nghiệm thu thành công.
- **Bên cần xác nhận:** Đại diện hai nhóm cùng ký duyệt.

---

### QUYẾT ĐỊNH 2: GIẢI TỎA VÙNG CHẾT NGHIỆP VỤ (DEADLOCK ZONE) KHI TÁC GIẢ XÓA / GỠ TÀI LIỆU
- **Mã quyết định:** `DEC-02`
- **Mức độ ưu tiên:** **P0 (Bắt buộc khắc phục để tránh lỗi nghiệp vụ)**
- **Vấn đề cần quyết định:**
  - Quy định chính sách như thế nào đối với các tài liệu mà tác giả muốn rút bỏ nhưng rơi vào vùng: đã đăng quá 24 giờ với 0 lượt tải, hoặc có từ 1 đến 9 lượt tải ($1 \le \text{luot\_tai} \le 9$)?
- **Bối cảnh:**
  - Hiện tại, `canAuthorSoftDelete` chỉ cho tự xóa khi `luot_tai == 0` và $< 24$h. Trong khi `canRequestRemoval` chỉ cho nộp đơn xin gỡ khi $\ge 10$ lượt tải. Tác giả có tài liệu ở giữa hai khoảng này bị chặn cả 2 cửa.
- **Các phương án xem xét:**
  - **Phương án 1 (Hạ ngưỡng đơn xin gỡ xuống mức bù khuyết - Recommended):**
    - *Mô tả:* Giữ nguyên điều kiện tự xóa mềm tức thì: `luot_tai == 0 && created_at < 24h`. Nếu **KHÔNG** thỏa mãn điều kiện tự xóa này (bất kể vì đã quá 24h hoặc vì đã có lượt tải $\ge 1$), tác giả **ĐƯỢC PHÉP** gửi đơn xin gỡ qua `SubmitRemovalRequestAction`. Ban kiểm duyệt sẽ là người xem xét lý do và bấm duyệt hay từ chối.
    - *Ưu điểm:* Triệt tiêu 100% vùng chết nghiệp vụ; tác giả luôn có con đường hợp pháp để giải trình xin rút tài liệu; Ban kiểm duyệt vẫn kiểm soát được tính toàn vẹn của kho học liệu.
    - *Nhược điểm:* Hàng đợi xét duyệt gỡ tài liệu có thể nhận thêm một số đơn từ tài liệu ít tải.
  - **Phương án 2 (Nới rộng thời gian tự xóa mềm lên vô thời hạn nếu 0 lượt tải):**
    - *Mô tả:* Cho phép tác giả tự xóa mềm bất cứ lúc nào miễn là `luot_tai == 0`. Chỉ áp dụng xét duyệt khi `luot_tai >= 1`.
    - *Ưu điểm:* Rất linh hoạt cho tác giả có tài liệu 0 tải.
    - *Nhược điểm:* Tác giả có thể xóa tài liệu sau 6 tháng khiến sinh viên khác mất liên kết đã bookmark.
- **Phương án đề xuất của Architect:** **CHỌN PHƯƠNG ÁN 1**. Đây là phương án dung hòa nhất giữa quyền nhân thân của tác giả và tính kiểm duyệt của nhà trường.
- **Bên cần xác nhận:** Hai bên cùng thống nhất và cập nhật logic tại `DocumentAuthorRemovalEvaluator.php`.

---

### QUYẾT ĐỊNH 3: CHUẨN HÓA VÀ HỢP NHẤT HẠ TẦNG THÔNG BÁO NGHIỆP VỤ
- **Mã quyết định:** `DEC-03`
- **Mức độ ưu tiên:** **P1 (Quan trọng — Cần xử lý để đồng bộ giao diện chuông)**
- **Vấn đề cần quyết định:**
  - Xử lý thế nào đối với các thông báo của Moderation và Bounties đang bắn trực tiếp qua `$user->notify(new ...)` mà thiếu thuộc tính `'category' => 'PERSONAL'`?
- **Bối cảnh:**
  - Như đã phân tích tại GAP-HIGH-02, truy vấn lọc thông báo cá nhân tại `GetNotificationsQuery.php` đang lọc theo chuỗi `"category":"PERSONAL"`. Các thông báo không có trường này sẽ bị biến mất khi bấm lọc tab.
- **Các phương án xem xét:**
  - **Phương án 1 (Bổ sung thuộc tính category vào payload Notification hiện có - Recommended):**
    - *Mô tả:* Giữ nguyên các class Notification độc lập (`BountyPayoutClawbackRefundNotification`, `BountyPayoutApprovedNotification`,...), chỉ cần sửa hàm `toDatabase()` để thêm `'category' => 'PERSONAL'`.
    - *Ưu điểm:* Cực kỳ nhanh, chỉ mất khoảng 15 phút sửa code, không đụng chạm đến kiến trúc lớn, tương thích ngay với bộ lọc hiện tại.
    - *Nhược điểm:* Vẫn tồn tại sự phân mảnh nhỏ giữa Laravel Notification truyền thống và Shared OOP Service.
  - **Phương án 2 (Refactor toàn bộ sang gọi qua `NotificationService::send`):**
    - *Mô tả:* Xóa các class Notification độc lập, tạo thêm Enum types trong `NotificationType.php`, chuyển toàn bộ sang bắn Domain Event.
    - *Ưu điểm:* Đạt kiến trúc Clean Layered Architecture 100%.
    - *Nhược điểm:* Tốn công, rủi ro hồi quy cao trong giai đoạn gấp rút nghiệm thu.
- **Phương án đề xuất của Architect:** **CHỌN PHƯƠNG ÁN 1**. Đơn giản, an toàn, giải quyết dứt điểm lỗi lọc thông báo mà không gây rủi ro hồi quy.
- **Bên cần xác nhận:** Thành viên phụ trách Module Moderation và Bounties.

---

### QUYẾT ĐỊNH 4: CHIẾN LƯỢC RÚT NGẮN MA SÁT DUYỆT TÀI LIỆU NỘP BOUNTY (METHOD B)
- **Mã quyết định:** `DEC-04`
- **Mức độ ưu tiên:** **P1 (Quan trọng — Trải nghiệm người dùng)**
- **Vấn đề cần quyết định:**
  - Có nên duy trì quy trình 2 lớp riêng rẽ (Duyệt tệp xong mới cho Requester xác nhận rồi Admin duyệt giải ngân), hay có cơ chế hỗ trợ nào để tránh việc bài nộp bị ngâm quá lâu?
- **Bối cảnh:**
  - Sinh viên B tải tệp mới lên thì tệp ở trạng thái `CHO_DUYET`. Sinh viên A không thể bấm xác nhận để thưởng điểm nếu Kiểm duyệt viên chưa duyệt tệp sang `DA_DUYET`.
- **Các phương án xem xét:**
  - **Phương án 1 (Giữ nguyên 2 lớp kèm gắn nhãn ưu tiên và thông báo rõ ràng - Recommended):**
    - *Mô tả:* Giữ nguyên cơ chế 2 lớp để phòng chống tuyệt đối mã độc và file rác. Tuy nhiên:
      1. Trên giao diện Sinh viên A: Hiển thị badge vàng rõ ràng: *"Đang chờ Ban kiểm duyệt thẩm định tệp tin"*.
      2. Trên giao diện Admin Review Queue: Gắn nhãn màu tím `[Bounty Submission - Treo thưởng X điểm]` để Kiểm duyệt viên ưu tiên xử lý tệp này trước các tệp thông thường.
    - *Ưu điểm:* Bảo đảm 100% an toàn bảo mật và phòng chống gian lận cày điểm ảo.
    - *Nhược điểm:* Vẫn cần 2 lượt can thiệp của Moderator.
  - **Phương án 2 (Tự động giải ngân ngay khi Admin duyệt tài liệu nếu Requester đã bấm trước):**
    - *Mô tả:* Cho phép Sinh viên A bấm "Chấp nhận sơ bộ" trước, khi Admin duyệt tài liệu sang `DA_DUYET` thì tự động giải ngân luôn mà không cần vào hàng đợi thứ hai.
    - *Ưu điểm:* Giảm bớt 1 bước duyệt cho Admin.
    - *Nhược điểm:* Đảo lộn logic máy trạng thái hiện có; rủi ro Requester bấm nhầm trước khi file được kiểm chứng an toàn.
- **Phương án đề xuất của Architect:** **CHỌN PHƯƠNG ÁN 1**. An toàn là ưu tiên số một đối với tài nguyên học thuật của trường đại học.
- **Bên cần xác nhận:** Đại diện hai nhóm cùng xác nhận kịch bản hiển thị giao diện.

---

### QUYẾT ĐỊNH 5: CHÍNH SÁCH XỬ LÝ ĐỐI VỚI BÀI ĐÁNH GIÁ MÔN HỌC BỊ XÓA DO VI PHẠM
- **Mã quyết định:** `DEC-05`
- **Mức độ ưu tiên:** **P2 (Chính sách vận hành cộng đồng)**
- **Vấn đề cần quyết định:**
  - Khi một bài đánh giá môn học bị Kiểm duyệt viên xóa (`action = DELETE`) do vi phạm tiêu chuẩn cộng đồng, mức chế tài đối với tác giả đánh giá nên là bao nhiêu?
- **Hiện trạng:**
  - File `ResolveReviewReportAction.php` hiện đang cấu hình trừ **10 điểm uy tín (`diem_uy_tin -= 10`)** và tăng số lần vi phạm `so_lan_vi_pham += 1` thông qua `UserViolationPenaltyEvaluator`.
  - Trong khi đó, việc xóa tài liệu học thuật vi phạm bản quyền thì bị trừ **20 điểm uy tín**.
- **Các phương án xem xét:**
  - **Phương án 1 (Giữ nguyên mức phạt 10 điểm cho Review và 20 điểm cho Tài liệu - Recommended):**
    - *Lý do:* Vi phạm bản quyền tài liệu có mức độ nghiêm trọng và thiệt hại học thuật lớn hơn việc phát ngôn nhận xét tiêu cực/chưa chuẩn mực. Phân hóa mức phạt là hoàn toàn hợp lý.
  - **Phương án 2 (Đồng nhất mọi vi phạm đều phạt 20 điểm):**
    - *Lý do:* Đơn giản hóa quy tắc tính toán.
- **Phương án đề xuất của Architect:** **CHỌN PHƯƠNG ÁN 1**.
- **Bên cần xác nhận:** Hai bên cùng ghi nhận vào tài liệu vận hành chính sách cộng đồng.

