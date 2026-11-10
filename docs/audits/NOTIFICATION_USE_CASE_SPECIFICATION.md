# ĐẶC TẢ CÁC CA SỬ DỤNG HỆ THỐNG THÔNG BÁO (NOTIFICATION USE CASE SPECIFICATIONS)
## DỰ ÁN: AGU ACADEMIC HUB — HỆ THỐNG THÔNG BÁO TẬP TRUNG TOÀN HỆ THỐNG

---

## 1. DANH MỤC TỔNG HỢP CÁC CA SỬ DỤNG (USE CASE INVENTORY)

| Phân nhóm | Mã UC | Tên Use Case | Trạng thái |
| :--- | :--- | :--- | :--- |
| **Lõi thông báo (Core Operations)** | UC-NOT-01 | Xem danh sách thông báo cá nhân / công việc | `IMPLEMENTED` |
| | UC-NOT-02 | Xem số lượng thông báo chưa đọc (Unread Count Badge) | `IMPLEMENTED` |
| | UC-NOT-03 | Đánh dấu một thông báo đã đọc | `IMPLEMENTED` |
| | UC-NOT-04 | Đánh dấu tất cả thông báo đã đọc | `IMPLEMENTED` |
| | UC-NOT-05 | Xóa một thông báo | `IMPLEMENTED` |
| | UC-NOT-06 | Điều hướng an toàn khi mở thông báo | `IMPLEMENTED` |
| **Sinh viên / Cựu SV (Student & Alumni)** | UC-ST-01 | Nhận thông báo khi có tài liệu mới giải quyết Bounty của mình | `IMPLEMENTED` |
| | UC-ST-02 | Nhận thông báo khi đóng góp Bounty được người tạo chấp nhận | `IMPLEMENTED` |
| | UC-ST-03 | Nhận thông báo khi yêu cầu Bounty bị hủy & hoàn tiền cọc | `IMPLEMENTED` |
| | UC-ST-04 | Nhận thông báo khi tài liệu tải lên được phê duyệt | `IMPLEMENTED` |
| | UC-ST-05 | Nhận thông báo khi tài liệu tải lên bị từ chối phê duyệt | `IMPLEMENTED` |
| | UC-ST-06 | Nhận thông báo khi tài liệu duyệt qua Bounty kích hoạt trả thưởng | `IMPLEMENTED` |
| | UC-ST-07 | Nhận thông báo khi bài đánh giá môn học của mình bị báo cáo | `IMPLEMENTED` |
| | UC-ST-08 | Nhận thông báo khi hồ sơ KYC được duyệt | `IMPLEMENTED` |
| | UC-ST-09 | Nhận thông báo khi hồ sơ KYC bị từ chối | `IMPLEMENTED` |
| | UC-ST-10 | Nhận thông báo khi số dư ví điểm biến động | `CONTRACT_DEFINED` |
| **Kiểm duyệt viên (Moderator Work Items)** | UC-MOD-01 | Nhận thông báo mục công việc: Tài liệu mới chờ duyệt | `IMPLEMENTED` |
| | UC-MOD-02 | Nhận thông báo mục công việc: Bài đánh giá môn học bị báo cáo | `IMPLEMENTED` |
| | UC-MOD-03 | Nhận thông báo mục công việc: Hồ sơ KYC mới chờ duyệt | `IMPLEMENTED` |
| | UC-MOD-04 | Nhận thông báo mục công việc: Yêu cầu Bounty xảy ra tranh chấp | `CONTRACT_DEFINED` |
| | UC-MOD-05 | Nhận thông báo mục công việc: Tài liệu bị người dùng báo cáo vi phạm | `CONTRACT_DEFINED` |
| | UC-MOD-06 | Nhận thông báo nhắc nhở công việc kiểm duyệt tồn đọng quá hạn | `CONTRACT_DEFINED` |
| | UC-MOD-07 | Nhận thông báo tổng kết công việc kiểm duyệt định kỳ | `CONTRACT_DEFINED` |
| **Quản trị viên (Admin Operations)** | UC-ADM-01 | Nhận thông báo cảnh báo bảo mật tài khoản | `IMPLEMENTED` |
| | UC-ADM-02 | Nhận thông báo lỗi hệ thống nghiêm trọng | `CONTRACT_DEFINED` |
| | UC-ADM-03 | Nhận thông báo đề xuất bổ nhiệm kiểm duyệt viên mới | `CONTRACT_DEFINED` |
| | UC-ADM-04 | Nhận thông báo bảo trì hệ thống định kỳ | `CONTRACT_DEFINED` |
| | UC-ADM-05 | Nhận thông báo thay đổi cấu hình hệ thống | `CONTRACT_DEFINED` |

---

## 2. ĐẶC TẢ CHI TIẾT CÁC CA SỬ DỤNG LÕI (CORE NOTIFICATION USE CASES)

### UC-NOT-01: Xem danh sách thông báo
- **Tác nhân:** Mọi người dùng đã đăng nhập (`SINH_VIEN`, `CUU_SINH_VIEN`, `KIEM_DUYET_VIEN`, `ADMIN`).
- **Điều kiện tiên quyết:** Người dùng đã xác thực thành công.
- **Kích hoạt:** Người dùng truy cập đường dẫn `/thong-bao` hoặc mở dropdown thông báo từ thanh điều hướng trên Header.
- **Luồng chính:**
  1. Client gửi request `GET /thong-bao` (có thể kèm query params: `filter=all|unread|read`, `category=PERSONAL|WORK_ITEM`, `page=1`).
  2. Controller gọi `GetNotificationsQuery` để lấy danh sách từ cơ sở dữ liệu dựa trên `notifiable_id = Auth::id()`.
  3. Kết quả được sắp xếp theo thời gian mới nhất (`created_at DESC`), phân trang 15 bản ghi/trang.
  4. Hệ thống render giao diện `Notifications/Index` (hoặc trả JSON Resource cho dropdown).
- **Trạng thái:** `IMPLEMENTED`.

### UC-NOT-02: Xem số lượng thông báo chưa đọc (Unread Count Badge)
- **Tác nhân:** Mọi người dùng đã đăng nhập.
- **Điều kiện tiên quyết:** Người dùng đã xác thực.
- **Kích hoạt:** Khi trang web tải hoặc sau mỗi khoảng thời gian định kỳ (polling/refresh).
- **Luồng chính:**
  1. Frontend gọi `GET /api/notifications/unread-count`.
  2. Controller gọi `GetUnreadNotificationCountQuery`.
  3. Query thực hiện đếm bản ghi `whereNull('read_at')` thuộc về người dùng hiện tại.
  4. Trả về JSON: `{"unread_count": 5}`.
  5. Badge hiển thị số lượng đỏ trên biểu tượng quả chuông.
- **Trạng thái:** `IMPLEMENTED`.

### UC-NOT-03: Đánh dấu một thông báo đã đọc
- **Tác nhân:** Người sở hữu thông báo.
- **Điều kiện tiên quyết:** Thông báo tồn tại và thuộc về người dùng hiện tại.
- **Kích hoạt:** Người dùng bấm vào thông báo hoặc nút "Đánh dấu đã đọc" (icon check).
- **Luồng chính:**
  1. Client gửi `POST /thong-bao/{id}/doc` (hoặc `PATCH /api/notifications/{id}/read`).
  2. Action `MarkNotificationAsReadAction` truy vấn bản ghi theo UUID `id` và kiểm tra `notifiable_id == Auth::id()`.
  3. Nếu không đúng người sở hữu, hệ thống từ chối bằng HTTP 403 / 404.
  4. Nếu hợp lệ và `read_at` đang là `NULL`, cập nhật `read_at = now()`.
  5. Trả về JSON phản hồi thành công và unread count mới.
- **Trạng thái:** `IMPLEMENTED`.

### UC-NOT-04: Đánh dấu tất cả thông báo đã đọc
- **Tác nhân:** Người sở hữu thông báo.
- **Điều kiện tiên quyết:** Có ít nhất 1 thông báo chưa đọc.
- **Kích hoạt:** Người dùng bấm nút "Đánh dấu tất cả đã đọc" trên trang trung tâm hoặc dropdown.
- **Luồng chính:**
  1. Client gửi `POST /thong-bao/doc-tat-ca` (hoặc `POST /api/notifications/mark-all-read`).
  2. Action `MarkAllNotificationsAsReadAction` thực thi 1 câu lệnh SQL UPDATE duy nhất:
     `UPDATE notifications SET read_at = NOW() WHERE notifiable_id = :userId AND read_at IS NULL`.
  3. Trả về thông điệp thành công và unread count = 0.
- **Trạng thái:** `IMPLEMENTED`.

### UC-NOT-05: Xóa một thông báo
- **Tác nhân:** Người sở hữu thông báo.
- **Điều kiện tiên quyết:** Thông báo tồn tại và thuộc về người dùng hiện tại.
- **Kích hoạt:** Người dùng bấm vào biểu tượng thùng rác bên cạnh thông báo.
- **Luồng chính:**
  1. Client gửi `DELETE /api/notifications/{id}`.
  2. Action `DeleteNotificationAction` kiểm tra quyền sở hữu tương tự UC-NOT-03.
  3. Tiến hành xóa vĩnh viễn (Hard delete) bản ghi khỏi bảng `notifications`.
  4. Trả về phản hồi thành công; giao diện tự động xóa mục này khỏi danh sách.
- **Trạng thái:** `IMPLEMENTED`.

### UC-NOT-06: Điều hướng an toàn khi mở thông báo
- **Tác nhân:** Người dùng nhấp vào link của thông báo.
- **Điều kiện tiên quyết:** Thông báo có chứa trường `target_url`.
- **Kích hoạt:** Người dùng click vào tiêu đề hoặc thẻ thông báo.
- **Luồng chính:**
  1. Tự động kích hoạt UC-NOT-03 để đánh dấu thông báo đã đọc.
  2. Kiểm tra `target_url`: chỉ cho phép các đường dẫn nội bộ bắt đầu bằng dấu `/` (ví dụ `/courses/1/documents/10`).
  3. Nếu đường dẫn dẫn ra bên ngoài hoặc chứa script độc hại, bộ lọc `NotificationPayload::sanitizeUrl` đã chuẩn hóa thành `/thong-bao`.
  4. Inertia Router thực hiện chuyển trang an toàn đến đích.
- **Trạng thái:** `IMPLEMENTED`.

---

## 3. ĐẶC TẢ CHI TIẾT CÁC CA SỬ DỤNG CHO SINH VIÊN & CỰU SINH VIÊN (STUDENT & ALUMNI)

### UC-ST-01: Nhận thông báo khi có tài liệu mới giải quyết Bounty của mình
- **Mã sự kiện:** `DocumentContributedToBountyEvent`
- **Tác nhân kích hoạt:** Người đóng góp (Sinh viên hoặc Cựu sinh viên khác).
- **Đối tượng nhận:** Người tạo yêu cầu tài liệu (`nguoi_yeu_cau_id`).
- **Nội dung:**
  - **Tiêu đề:** `Có tài liệu mới giải quyết yêu cầu của bạn`
  - **Nội dung:** `Sinh viên {Tên} vừa đăng tải/chọn tài liệu "{Tên tài liệu}" cho yêu cầu "{Tiêu đề yêu cầu}".`
  - **Loại:** `BOUNTY_NEW_CONTRIBUTION`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/courses/{mon_hoc_id}/documents/{tai_lieu_id}`
- **Thời điểm kích hoạt:** Ngay sau khi transaction tại `FulfillBountyRequestAction` commit thành công.
- **Trạng thái:** `IMPLEMENTED`.

### UC-ST-02: Nhận thông báo khi đóng góp Bounty được người tạo chấp nhận
- **Mã sự kiện:** `BountyFulfillmentConfirmedEvent`
- **Tác nhân kích hoạt:** Người tạo yêu cầu tài liệu xác nhận giải pháp đạt yêu cầu.
- **Đối tượng nhận:** Người đóng góp tài liệu (`nguoi_dong_gop_id`).
- **Nội dung:**
  - **Tiêu đề:** `Đóng góp của bạn đã được chấp nhận!`
  - **Nội dung:** `Yêu cầu "{Tiêu đề yêu cầu}" đã xác nhận tài liệu của bạn. Điểm thưởng {Số điểm} AGU Point đã được cộng vào ví của bạn.`
  - **Loại:** `BOUNTY_FULFILLMENT_ACCEPTED`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/courses/{mon_hoc_id}/documents/{tai_lieu_id}`
- **Thời điểm kích hoạt:** Ngay sau khi transaction tại `ConfirmBountyFulfillmentAction` hoàn tất giải ngân ví điểm.
- **Trạng thái:** `IMPLEMENTED`.

### UC-ST-03: Nhận thông báo khi yêu cầu Bounty bị hủy & hoàn tiền cọc
- **Mã sự kiện:** `BountyCancelledEvent`
- **Tác nhân kích hoạt:** Người tạo yêu cầu tự hủy hoặc Moderator hủy do vi phạm.
- **Đối tượng nhận:** Người tạo yêu cầu (`nguoi_yeu_cau_id`).
- **Nội dung:**
  - **Tiêu đề:** `Yêu cầu tài liệu đã được hủy`
  - **Nội dung:** `Yêu cầu "{Tiêu đề yêu cầu}" đã hủy thành công. Số điểm cọc {Số điểm} AGU Point đã được hoàn trả về ví của bạn.`
  - **Loại:** `BOUNTY_CANCELLED`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/courses/{mon_hoc_id}?tab=bounties`
- **Thời điểm kích hoạt:** Ngay sau khi `CancelBountyRequestAction` commit thành công hoàn cọc.
- **Trạng thái:** `IMPLEMENTED`.

### UC-ST-04: Nhận thông báo khi tài liệu tải lên được phê duyệt
- **Mã sự kiện:** `DocumentModeratedEvent` (với `trang_thai = DA_DUYET`)
- **Tác nhân kích hoạt:** Kiểm duyệt viên (`KIEM_DUYET_VIEN`).
- **Đối tượng nhận:** Tác giả tài liệu (`nguoi_dang_id`).
- **Nội dung:**
  - **Tiêu đề:** `Tài liệu của bạn đã được phê duyệt`
  - **Nội dung:** `Tài liệu "{Tên tài liệu}" đã được kiểm duyệt và công khai trên hệ thống.`
  - **Loại:** `DOCUMENT_APPROVED`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/courses/{mon_hoc_id}/documents/{tai_lieu_id}`
- **Thời điểm kích hoạt:** Sau khi `DecideDocumentReviewAction` commit chuyển trạng thái sang `DA_DUYET`.
- **Trạng thái:** `IMPLEMENTED`.

### UC-ST-05: Nhận thông báo khi tài liệu tải lên bị từ chối phê duyệt
- **Mã sự kiện:** `DocumentModeratedEvent` (với `trang_thai = TU_CHOI`)
- **Tác nhân kích hoạt:** Kiểm duyệt viên.
- **Đối tượng nhận:** Tác giả tài liệu (`nguoi_dang_id`).
- **Nội dung:**
  - **Tiêu đề:** `Tài liệu của bạn không được phê duyệt`
  - **Nội dung:** `Tài liệu "{Tên tài liệu}" đã bị từ chối. Lý do: {Lý do từ chối}.`
  - **Loại:** `DOCUMENT_REJECTED`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/courses/{mon_hoc_id}/documents/{tai_lieu_id}`
- **Thời điểm kích hoạt:** Sau khi `DecideDocumentReviewAction` commit từ chối tài liệu.
- **Trạng thái:** `IMPLEMENTED`.

### UC-ST-06: Nhận thông báo khi tài liệu duyệt qua Bounty kích hoạt trả thưởng
- **Mã sự kiện:** `DocumentModeratedEvent` (với tài liệu thuộc yêu cầu Bounty đang chờ duyệt).
- **Tác nhân kích hoạt:** Kiểm duyệt viên phê duyệt tài liệu đóng góp nộp mới.
- **Đối tượng nhận:** Người đóng góp (`nguoi_dong_gop_id`).
- **Nội dung:**
  - **Tiêu đề:** `Phần thưởng Bounty đã sẵn sàng`
  - **Nội dung:** `Tài liệu đóng góp của bạn cho yêu cầu "{Tiêu đề yêu cầu}" đã được kiểm duyệt hợp lệ.`
  - **Loại:** `BOUNTY_REWARD_CLAIMED`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/courses/{mon_hoc_id}/documents/{tai_lieu_id}`
- **Trạng thái:** `IMPLEMENTED`.

### UC-ST-07: Nhận thông báo khi bài đánh giá môn học bị báo cáo vi phạm
- **Mã sự kiện:** `ReviewReportedEvent`
- **Tác nhân kích hoạt:** Người dùng khác bấm báo cáo vi phạm bài review.
- **Đối tượng nhận:** Tác giả bài đánh giá (`nguoi_dung_id`).
- **Nội dung:**
  - **Tiêu đề:** `Đánh giá môn học của bạn nhận được phản ánh`
  - **Nội dung:** `Bài đánh giá môn học của bạn tại môn "{Tên môn học}" đang được ban kiểm duyệt xem xét do có báo cáo vi phạm.`
  - **Loại:** `REVIEW_REPORTED`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/courses/{mon_hoc_id}?tab=reviews`
- **Thời điểm kích hoạt:** Sau khi `ReportReviewViolationAction` tạo bản ghi báo cáo thành công.
- **Trạng thái:** `IMPLEMENTED`.

### UC-ST-08: Nhận thông báo khi hồ sơ xác minh KYC được chấp thuận
- **Mã sự kiện:** `KycDecisionEvent` (với `trang_thai = DA_DUYET`)
- **Tác nhân kích hoạt:** Quản trị viên hoặc Kiểm duyệt viên thẩm định hồ sơ cựu sinh viên.
- **Đối tượng nhận:** Người gửi hồ sơ KYC (`nguoi_dung_id`).
- **Nội dung:**
  - **Tiêu đề:** `Hồ sơ xác thực Cựu sinh viên đã được phê duyệt`
  - **Nội dung:** `Chúc mừng bạn! Hồ sơ xác thực Cựu sinh viên của bạn đã được thông qua thành công.`
  - **Loại:** `KYC_APPROVED`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/profile`
- **Trạng thái:** `IMPLEMENTED`.

### UC-ST-09: Nhận thông báo khi hồ sơ KYC bị từ chối
- **Mã sự kiện:** `KycDecisionEvent` (với `trang_thai = TU_CHOI`)
- **Tác nhân kích hoạt:** Người thẩm định từ chối hồ sơ kèm lý do.
- **Đối tượng nhận:** Người gửi hồ sơ KYC (`nguoi_dung_id`).
- **Nội dung:**
  - **Tiêu đề:** `Hồ sơ xác thực chưa được chấp thuận`
  - **Nội dung:** `Yêu cầu xác thực Cựu sinh viên của bạn bị từ chối. Lý do: {Lý do}.`
  - **Loại:** `KYC_REJECTED`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/kyc/resubmit`
- **Trạng thái:** `IMPLEMENTED`.

### UC-ST-10: Nhận thông báo khi số dư ví điểm biến động
- **Mã sự kiện:** `WalletTransactionRecordedEvent`
- **Đối tượng nhận:** Chủ ví (`ma_nguoi_dung`).
- **Nội dung:**
  - **Tiêu đề:** `Biến động số dư ví điểm`
  - **Nội dung:** `Ví của bạn vừa được thay đổi {+/- Số điểm} điểm từ giao dịch: {Lý do}.`
  - **Loại:** `WALLET_POINTS_CHANGED`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/wallet/transactions`
- **Trạng thái:** `CONTRACT_DEFINED` (Đã định nghĩa enum và payload builder, sẵn sàng kết nối khi module Wallet hoàn thiện).

---

## 4. ĐẶC TẢ CHI TIẾT CÁC CA SỬ DỤNG CHO KIỂM DUYỆT VIÊN (MODERATOR WORK ITEMS)

### UC-MOD-01: Nhận thông báo mục công việc: Tài liệu mới chờ duyệt
- **Mã sự kiện:** `DocumentModeratedEvent` (khi tài liệu mới nộp có trạng thái `CHO_DUYET`)
- **Đối tượng nhận:** Các Kiểm duyệt viên (`KIEM_DUYET_VIEN`) phụ trách khoa tương ứng.
- **Nội dung:**
  - **Tiêu đề:** `Mục công việc: Có tài liệu mới cần kiểm duyệt`
  - **Nội dung:** `Tài liệu "{Tên tài liệu}" thuộc môn "{Tên môn}" vừa được đăng tải và đang chờ phê duyệt.`
  - **Loại:** `MOD_DOCUMENT_PENDING`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/moderator/documents?status=CHO_DUYET`
- **Trạng thái:** `IMPLEMENTED`.

### UC-MOD-02: Nhận thông báo mục công việc: Bài đánh giá môn học bị báo cáo
- **Mã sự kiện:** `ReviewReportedEvent`
- **Đối tượng nhận:** Các Kiểm duyệt viên đang hoạt động trong hệ thống.
- **Nội dung:**
  - **Tiêu đề:** `Mục công việc: Báo cáo vi phạm đánh giá môn học mới`
  - **Nội dung:** `Có báo cáo vi phạm mới cho bài đánh giá môn "{Tên môn}". Lý do: {Lý do báo cáo}.`
  - **Loại:** `MOD_REVIEW_REPORTED`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/moderator/reviews`
- **Trạng thái:** `IMPLEMENTED`.

### UC-MOD-03: Nhận thông báo mục công việc: Hồ sơ KYC mới chờ duyệt
- **Mã sự kiện:** `KycSubmittedEvent`
- **Đối tượng nhận:** Danh sách Kiểm duyệt viên hoặc Quản trị viên phụ trách xác thực tài khoản.
- **Nội dung:**
  - **Tiêu đề:** `Mục công việc: Hồ sơ KYC mới cần thẩm định`
  - **Nội dung:** `Người dùng {Tên người dùng} vừa nộp hồ sơ xác thực Cựu sinh viên.`
  - **Loại:** `MOD_KYC_PENDING`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/admin/kyc`
- **Trạng thái:** `IMPLEMENTED`.

### UC-MOD-04: Nhận thông báo mục công việc: Yêu cầu Bounty xảy ra tranh chấp
- **Mã sự kiện:** `BountyDisputeRaisedEvent`
- **Đối tượng nhận:** Kiểm duyệt viên.
- **Nội dung:**
  - **Tiêu đề:** `Mục công việc: Khiếu nại Bounty cần phân giải`
  - **Nội dung:** `Yêu cầu tài liệu #{ID} có khiếu nại giữa người yêu cầu và người giải quyết.`
  - **Loại:** `MOD_BOUNTY_DISPUTED`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/moderator/bounties/{id}/dispute`
- **Trạng thái:** `CONTRACT_DEFINED`.

### UC-MOD-05: Nhận thông báo mục công việc: Tài liệu bị người dùng báo cáo vi phạm
- **Mã sự kiện:** `DocumentReportedEvent`
- **Đối tượng nhận:** Kiểm duyệt viên.
- **Nội dung:**
  - **Tiêu đề:** `Mục công việc: Tài liệu bị báo cáo vi phạm bản quyền / nội dung`
  - **Nội dung:** `Tài liệu "{Tên tài liệu}" có phản ánh vi phạm từ cộng đồng.`
  - **Loại:** `MOD_DOCUMENT_REPORTED`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/moderator/reports`
- **Trạng thái:** `CONTRACT_DEFINED`.

### UC-MOD-06: Nhận thông báo nhắc nhở công việc kiểm duyệt tồn đọng quá hạn
- **Mã sự kiện:** `ModerationSlaBreachedEvent`
- **Đối tượng nhận:** Kiểm duyệt viên và Trưởng nhóm kiểm duyệt.
- **Nội dung:**
  - **Tiêu đề:** `Nhắc nhở: Có {Số lượng} mục kiểm duyệt đang quá hạn 48h`
  - **Nội dung:** `Vui lòng xử lý các tài liệu và báo cáo đang tồn đọng quá hạn quy định.`
  - **Loại:** `MOD_SLA_REMINDER`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/moderator/dashboard`
- **Trạng thái:** `CONTRACT_DEFINED`.

### UC-MOD-07: Nhận thông báo tổng kết công việc kiểm duyệt định kỳ
- **Mã sự kiện:** `ModerationWeeklyDigestEvent`
- **Đối tượng nhận:** Kiểm duyệt viên.
- **Nội dung:**
  - **Tiêu đề:** `Báo cáo hoạt động kiểm duyệt tuần qua`
  - **Nội dung:** `Bạn đã kiểm duyệt thành công {Số lượng} tài liệu trong tuần này.`
  - **Loại:** `MOD_DIGEST`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/moderator/stats`
- **Trạng thái:** `CONTRACT_DEFINED`.

---

## 5. ĐẶC TẢ CHI TIẾT CÁC CA SỬ DỤNG CHO QUẢN TRỊ VIÊN (ADMIN OPERATIONS)

### UC-ADM-01: Nhận thông báo cảnh báo bảo mật tài khoản
- **Mã sự kiện:** `SecurityAlertDetectedEvent`
- **Đối tượng nhận:** Ban Quản trị hệ thống (`ADMIN`).
- **Nội dung:**
  - **Tiêu đề:** `Cảnh báo an toàn hệ thống`
  - **Nội dung:** `Phát hiện hành vi bất thường hoặc đăng nhập thất bại liên tiếp đối với tài khoản #{User ID}.`
  - **Loại:** `ADMIN_SECURITY_ALERT`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/admin/security-logs`
- **Trạng thái:** `IMPLEMENTED`.

### UC-ADM-02: Nhận thông báo lỗi hệ thống nghiêm trọng
- **Mã sự kiện:** `CriticalSystemExceptionOccurredEvent`
- **Đối tượng nhận:** Toàn bộ Quản trị viên kỹ thuật.
- **Nội dung:**
  - **Tiêu đề:** `Sự cố hệ thống cấp độ cao (P1)`
  - **Nội dung:** `Queue worker hoặc dịch vụ lưu trữ gặp lỗi kết nối.`
  - **Loại:** `ADMIN_SYSTEM_ERROR`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/admin/system/health`
- **Trạng thái:** `CONTRACT_DEFINED`.

### UC-ADM-03: Nhận thông báo đề xuất bổ nhiệm kiểm duyệt viên mới
- **Mã sự kiện:** `ModeratorPromotionNominatedEvent`
- **Đối tượng nhận:** Quản trị viên cấp cao.
- **Nội dung:**
  - **Tiêu đề:** `Đề xuất cấp quyền Kiểm duyệt viên mới`
  - **Nội dung:** `Người dùng {Tên người dùng} được đề cử vai trò Kiểm duyệt viên khoa {Tên khoa}.`
  - **Loại:** `ADMIN_ROLE_PROMOTION`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/admin/users/{id}/roles`
- **Trạng thái:** `CONTRACT_DEFINED`.

### UC-ADM-04: Nhận thông báo bảo trì hệ thống định kỳ
- **Mã sự kiện:** `SystemMaintenanceScheduledEvent`
- **Đối tượng nhận:** Tất cả người dùng trong hệ thống.
- **Nội dung:**
  - **Tiêu đề:** `Thông báo bảo trì định kỳ hệ thống`
  - **Nội dung:** `Hệ thống sẽ tiến hành bảo trì từ {Giờ bắt đầu} đến {Giờ kết thúc}. Một số tính năng có thể tạm dừng.`
  - **Loại:** `SYSTEM_MAINTENANCE`
  - **Danh mục:** `PERSONAL`
  - **Target URL:** `/thong-bao`
- **Trạng thái:** `CONTRACT_DEFINED`.

### UC-ADM-05: Nhận thông báo thay đổi cấu hình hệ thống
- **Mã sự kiện:** `SystemConfigChangedEvent`
- **Đối tượng nhận:** Quản trị viên.
- **Nội dung:**
  - **Tiêu đề:** `Cấu hình hệ thống vừa được cập nhật`
  - **Nội dung:** `Tham số tỷ lệ thưởng Bounty và giới hạn upload vừa được thay đổi bởi Admin {Tên Admin}.`
  - **Loại:** `ADMIN_CONFIG_UPDATED`
  - **Danh mục:** `WORK_ITEM`
  - **Target URL:** `/admin/settings`
- **Trạng thái:** `CONTRACT_DEFINED`.

