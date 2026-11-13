# BẢNG THUẬT NGỮ KỸ THUẬT & NGHIỆP VỤ (BILINGUAL GLOSSARY)
## COMPREHENSIVE DOMAIN & TECHNICAL DICTIONARY
**Vị trí:** `docs/knowledge/13_GLOSSARY.md`  
**Ngày lập:** 11/11/2026  
**Nguyên tắc:** Chuẩn hóa song ngữ Việt - Anh. Mọi thuật ngữ đều được giải thích rõ ngữ cảnh sử dụng trong dự án AGU Academic Hub.

---

## 1. THUẬT NGỮ NGHIỆP VỤ HỌC THUẬT & HỆ THỐNG (DOMAIN TERMS)

| Thuật ngữ Tiếng Việt | Thuật ngữ Tiếng Anh | Giải thích chi tiết trong AGU Academic Hub |
|:---|:---|:---|
| **Yêu cầu Tìm Tài liệu / Săn thưởng** | **Bounty / Wishlist Request** | Bài đăng của sinh viên yêu cầu tài liệu học tập hiếm (đề thi, bài giảng) kèm mức điểm thưởng trích từ ví để khích lệ người khác đóng góp. |
| **Ký quỹ / Đóng băng Điểm** | **Escrow / Point Holding** | Cơ chế tạm khóa một số điểm trong ví khả dụng của người tạo yêu cầu sang số dư tạm giữ nhằm bảo đảm khả năng thanh toán khi có người giải quyết yêu cầu. |
| **Giải ngân Điểm thưởng** | **Bounty Payout / Disbursement** | Hành động chuyển điểm thưởng từ quỹ tạm giữ của người yêu cầu sang quỹ khả dụng của người đóng góp sau khi tài liệu được nghiệm thu hợp lệ. |
| **Nghiệm thu Nội dung** | **Fulfillment Acceptance** | Bước kiểm tra đầu tiên của chính sinh viên yêu cầu để xác nhận tài liệu nộp có đúng nội dung môn học và đúng chất lượng hay không. |
| **Phê duyệt Giải ngân Ban Quản trị** | **Moderation Payout Approval** | Bước thẩm định thứ hai do Kiểm duyệt viên hoặc Admin thực hiện để chống thông đồng cày điểm ảo và kiểm tra bản quyền trước khi tiền tệ thực chi. |
| **Thu hồi Điểm thưởng** | **Bounty Clawback** | Quy trình tự động đòi lại điểm thưởng từ người nộp và bồi hoàn cho người tạo yêu cầu khi tài liệu đã ăn thưởng bị phát hiện vi phạm bản quyền và bị gỡ bỏ. |
| **Yêu cầu Chỉnh sửa sau Hoàn thành** | **Post-Bounty Correction Request** | Cơ chế cho phép người tạo yêu cầu đề nghị người nộp bổ sung/sửa đổi file khi phát hiện sai sót chuyên môn mà không bị mất thêm điểm thưởng. |
| **Lần nộp Tài liệu Thay thế** | **Replacement Document Submission** | Phiên bản tài liệu mới (v2.0) do người đóng góp tải lên để khắc phục các thiếu sót trong yêu cầu chỉnh sửa; không ghi đè lên file gốc. |
| **Định danh Sinh viên Nạp trước** | **Pre-provisioned Student IAM** | Cơ chế định danh tự động dựa trên danh sách sinh viên thô do nhà trường cấp nạp sẵn vào CSDL; sinh viên chỉ cần đổi mật khẩu lần đầu để kích hoạt. |
| **Xác thực Danh tính Cựu Sinh viên** | **Alumni KYC (Know Your Customer)** | Quy trình thẩm định bằng tốt nghiệp, chứng nhận tốt nghiệp của cựu sinh viên để cấp quyền truy cập hệ thống. |
| **Quyền Sở hữu Cộng đồng** | **Community Ownership** | Quy tắc bảo tồn học liệu: Tài liệu khi đạt mốc từ 10 lượt tải trở lên sẽ trở thành tài sản chung của toàn trường; tác giả không thể tự ý xóa bỏ. |
| **Xóa mềm** | **Soft Delete** | Hành vi ẩn tài liệu khỏi hệ thống bằng cách cập nhật cột `deleted_at`; dữ liệu vật lý và bản ghi CSDL được giữ nguyên trong thùng rác 60 ngày. |
| **Ẩn danh Có Kiểm soát** | **Controlled Anonymity** | Cơ chế bảo vệ sinh viên: Che giấu danh tính trên giao diện và API công khai, nhưng lưu vết ID tại CSDL có bảo mật để hậu kiểm khi có vu khống. |

---

## 2. THUẬT NGỮ KIẾN TRÚC & KỸ THUẬT PHẦN MỀM (TECHNICAL TERMS)

| Thuật ngữ Tiếng Việt | Thuật ngữ Tiếng Anh | Giải thích chi tiết trong AGU Academic Hub |
|:---|:---|:---|
| **Kiến trúc Khối Mô-đun** | **Modular Monolith** | Kiến trúc triển khai trên một ứng dụng duy nhất nhưng mã nguồn được chia thành các Bounded Contexts độc lập theo nghiệp vụ, dễ bảo trì và mở rộng. |
| **Tách biệt Đọc - Ghi** | **Command-Query Separation (CQS)** | Nguyên lý phân tách: Actions chỉ làm nhiệm vụ ghi dữ liệu (thay đổi trạng thái), Queries chỉ làm nhiệm vụ đọc dữ liệu (Read-only, Cache). |
| **Bộ Điều phối Tinh gọn** | **Skinny Controller** | Controller có kích thước nhỏ (~30-70 dòng), không chứa logic tính toán hay câu lệnh SQL, chỉ làm nhiệm vụ nhận request và điều hướng. |
| **Khóa Bi quan theo Dòng** | **Pessimistic Row-level Locking (`lockForUpdate`)** | Kỹ thuật khóa độc quyền một bản ghi trong CSDL MySQL InnoDB trong suốt giao dịch Transaction, buộc các request khác phải chờ, triệt tiêu Race Condition. |
| **Tính Lũy đẳng / Khóa Chống Trùng** | **Idempotency / Idempotency Key** | Thuộc tính của một thao tác mà khi thực thi nhiều lần vẫn cho ra cùng một kết quả duy nhất; sử dụng chuỗi định danh duy nhất trong CSDL để chống giao dịch lặp. |
| **Hiệu ứng Tuyết lở Mã băm** | **Avalanche Effect** | Tính chất của hàm băm mật mã học (như SHA-256): Chỉ cần thay đổi 1 bit nhỏ trong tệp, toàn bộ chuỗi băm hex đầu ra sẽ thay đổi hoàn toàn. |
| **Kho Lưu trữ Nhạy cảm** | **Private Storage** | Thư mục lưu file trên máy chủ (`storage/app/private/`) hoàn toàn không được tạo symlink ra web public; chặn đứng việc tải trực tiếp qua URL. |
| **Đường dẫn có Chữ ký số Thời hạn** | **Temporary Signed URL** | Đường dẫn URL được mã hóa bằng thuật toán HMAC-SHA256 kèm thời hạn tự hủy (ví dụ: 30 phút); quá hạn sẽ bị CSDL/Server từ chối truy cập. |
| **Nhật ký Kiểm toán Bất biến** | **Immutable Audit Logs** | Bảng ghi nhận lịch sử thao tác của quản trị viên (`nhat_ky_kiem_toan`) có cơ chế chặn toàn bộ lệnh sửa (`update`) và xóa (`delete`), bảo đảm tính pháp lý. |
| **Truy vấn Thừa N+1** | **N+1 Query Problem** | Lỗi hiệu năng phổ biến của ORM khi thực thi 1 câu truy vấn cha và bắn thêm N câu truy vấn con lặp lại trong vòng lặp; giải quyết bằng Eager Loading `with()`. |
| **Giao thức Tương tác Inertia** | **Inertia.js Protocol** | Cầu nối hiện đại giữa Laravel backend và React frontend; giúp xây dựng SPA mượt mà mà không cần viết REST API tách rời. |
| **Bộ Đánh giá Quy tắc Nghiệp vụ** | **Domain Evaluator** | Lớp dịch vụ thuần túy trong tầng Domain chứa đựng các thuật toán thẩm định điều kiện (ví dụ: `ReputationEvaluator`, `BountyRuleEvaluator`). |

