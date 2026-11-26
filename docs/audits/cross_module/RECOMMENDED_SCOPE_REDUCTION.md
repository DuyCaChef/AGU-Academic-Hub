# ĐỀ XUẤT THU HẸP PHẠM VI TRIỂN KHAI VÀ NGHIỆM THU (SCOPE REDUCTION)
## AGU Academic Hub — Minimum Viable Integration Scope (MVIS)
**Giai đoạn:** Chuẩn bị bàn giao và nghiệm thu liên phân hệ  
**Phương pháp luận:** MoSCoW Prioritization (Must Have - Should Have - Could Have - Won't Have / Defer)  
**Mục tiêu chiến lược:** Tối ưu hóa nguồn lực kiểm thử, triệt tiêu 100% rủi ro runtime crash, hoàn thiện các điểm giao tiếp nghiệp vụ cốt lõi mà không làm phát sinh thêm nợ kỹ thuật.

---

## 1. TỔNG HỢP PHÂN LOẠI PHẠM VI (EXECUTIVE SCOPE SUMMARY)

```
┌────────────────────────────────────────────────────────────────────────┐
│                        TỔNG THỂ TÍNH NĂNG TÍCH HỢP                     │
└────────────────────────────────────┬───────────────────────────────────┘
                                     │
         ┌───────────────────────────┼───────────────────────────┐
         ▼                           ▼                           ▼
 ┌───────────────┐           ┌───────────────┐           ┌───────────────┐
 │ GROUP A: MUST │           │GROUP B: SHOULD│           │ GROUP C: COULD│
 │  (BẮT BUỘC)   │           │ (HOÀN THIỆN)  │           │  (TIỆN ÍCH)   │
 │   12 MỤC      │           │    05 MỤC     │           │    04 MỤC     │
 └───────┬───────┘           └───────┬───────┘           └───────┬───────┘
         │                           │                           │
         └───────────────────────────┼───────────────────────────┘
                                     │
                                     ▼
                             ┌───────────────┐
                             │ GROUP D: DEFER│
                             │ (HOÃN/LOẠI BỎ)│
                             │    06 MỤC     │
                             └───────────────┘
```

---

## 2. CHI TIẾT CÁC NHÓM PHẠM VI

---

### GROUP A — MUST HAVE (BẮT BUỘC ĐỂ NGHIỆP VỤ CỐT LÕI VẬN HÀNH & ĐỦ ĐIỀU KIỆN NGHIỆM THU)

Những chức năng này là xương sống của hệ thống, bắt buộc phải hoàn thiện 100% và vượt qua kiểm thử hồi quy:

| STT | Tên chức năng / Thành phần giữ lại | Căn cứ tài liệu & Lý do giữ lại | Rủi ro nếu không triển khai |
| :---: | :--- | :--- | :--- |
| **A-01** | **Vá lỗi Import tại `DecideDocumentReviewAction` (GAP-CRIT-01)** | Căn cứ `ADMIN_SPEC` Nghiệp vụ 6 & 15. Đây là lỗi runtime crash làm tê liệt tuyến `PUT /quan-tri/tai-lieu/{id}/quyet-dinh` và 4 bộ test tự động. | **CỰC KỲ CAO**: Hệ thống sập ngay khi cán bộ kiểm duyệt xử lý tài liệu vi phạm. |
| **A-02** | **Xóa bỏ "Vùng chết nghiệp vụ" khi gỡ tài liệu (GAP-CRIT-02)** | Căn cứ `ADMIN_SPEC` Nghiệp vụ 7 và nguyên tắc sở hữu cộng đồng. Cho phép tác giả gửi đơn xin gỡ cho mọi tài liệu khi không đủ điều kiện tự xóa trong 24h. | **CAO**: Sinh viên bị kẹt tài liệu cá nhân, kích động hành vi spam báo cáo vi phạm. |
| **A-03** | **Đóng băng mã nguồn & Giữ nguyên Hàng đợi Giải ngân Bounty** | Căn cứ `ADMIN_SPEC` Nghiệp vụ 15. Dù Controller nằm ở `app/Modules/Bounties`, luồng duyệt giải ngân 2 lớp đã chạy ổn định và có test bao phủ. | **CAO**: Nếu xáo trộn di dời lúc này sẽ gây vỡ route frontend và chậm tiến độ nghiệm thu. |
| **A-04** | **Hàng đợi Phê duyệt tài liệu mới (`CHO_DUYET`) đơn lẻ & hàng loạt** | Căn cứ `ADMIN_SPEC` Nghiệp vụ 6 & 16. Tài liệu đăng mới phải được duyệt thì mới xuất bản lên Course Detail. | **BẮT BUỘC**: Luồng xuất bản tài liệu bị ngưng trệ nếu không có hàng đợi này. |
| **A-05** | **Bảo mật tài liệu chưa duyệt (AUD-H01, AUD-H02)** | Căn cứ `STUDENT_ALUMNI_MODERATION_HANDOFF` Mục 3.3. Chặn tải tệp, chặn xem trước stream đối với người lạ khi tài liệu chưa `DA_DUYET`. | **BẮT BUỘC**: Nguy cơ rò rỉ tài liệu độc hại, file rác, đề thi lộ lọt. |
| **A-06** | **Quy trình Giải ngân 2 lớp (Requester Confirm $\rightarrow$ Admin Approve)** | Căn cứ `STUDENT_ALUMNI_MODERATION_HANDOFF` Mục 3.2. Chống thông đồng cày điểm ảo giữa hai tài khoản sinh viên. | **BẮT BUỘC**: Gian lận điểm thưởng làm vỡ nền kinh tế ví điểm của hệ thống. |
| **A-07** | **Cơ chế Thu hồi điểm Clawback & Hoàn tiền đền bù vi phạm bản quyền** | Căn cứ `ADMIN_SPEC` Nghiệp vụ 15. Hoàn trả 100% điểm cho SV A và thu hồi điểm SV B (chặn âm) khi tài liệu bị xóa. | **CAO**: Mất công bằng tài chính khi sinh viên nhận phải tài liệu vi phạm bản quyền. |
| **A-08** | **Zero-KYC sinh viên đang học & Đổi mật khẩu lần đầu** | Căn cứ `ADMIN_SPEC` Nghiệp vụ 3. Sinh viên đối soát từ `danh_sach_sinh_vien_raw`, cấp ví 100 điểm khi đổi mật khẩu. | **BẮT BUỘC**: Sinh viên chính quy không thể kích hoạt tài khoản để sử dụng hệ thống. |
| **A-09** | **Hàng đợi Duyệt KYC Cựu sinh viên & Xem ảnh Signed URL 30 phút** | Căn cứ `ADMIN_SPEC` Nghiệp vụ 4. Bảo vệ thông tin nhạy cảm (PII) trên bằng tốt nghiệp của cựu sinh viên. | **BẮT BUỘC**: Vi phạm an toàn dữ liệu cá nhân nếu để lộ link ảnh bằng tốt nghiệp công khai. |
| **A-10** | **Hàng đợi Báo cáo vi phạm thống nhất (Tài liệu & Đánh giá môn học)** | Căn cứ `ADMIN_SPEC` Nghiệp vụ 5 & 14. Tiếp nhận báo cáo từ sinh viên và áp dụng chế tài tự động. | **BẮT BUỘC**: Không có cơ chế làm sạch nội dung xấu độc trên cổng học thuật. |
| **A-11** | **Bộ lọc từ cấm tự động (Profanity Filter) & Hậu kiểm bài đánh giá** | Căn cứ `ADMIN_SPEC` Nghiệp vụ 14. Chặn ngay từ phản cảm, bài sạch được xuất bản ngay không cần chờ duyệt thủ công. | **BẮT BUỘC**: Xuất hiện ngôn từ xúc phạm giảng viên và nhà trường trên diễn đàn. |
| **A-12** | **Nhật ký kiểm toán nghiệp vụ bất biến (Immutable Audit Log)** | Căn cứ `ADMIN_SPEC` Nghiệp vụ 11. Ghi nhận toàn bộ thao tác khóa tài khoản, duyệt giải ngân, xóa tài liệu, duyệt KYC. | **BẮT BUỘC**: Không thể quy trách nhiệm khi xảy ra tranh chấp hoặc khiếu nại dữ liệu. |

---

### GROUP B — SHOULD HAVE (QUAN TRỌNG NHƯNG NÊN HOÀN THIỆN SAU KHI LUỒNG CỐT LÕI THÔNG SUỐT)

Những chức năng cần thiết để nâng cao tính ổn định, nên thực hiện ngay sau khi chốt xong Group A:

| STT | Tên chức năng | Lý do & Ảnh hưởng | Kế hoạch triển khai |
| :---: | :--- | :--- | :--- |
| **B-01** | **Bổ sung `'category' => 'PERSONAL'` vào payload các Notification (GAP-HIGH-02)** | Giúp các thông báo giải ngân, clawback, chế tài không bị rơi rớt khi người dùng bấm tab lọc "Cá nhân". | Thực hiện nhanh: chỉ cần sửa phương thức `toDatabase()` của 6 file Notification. |
| **B-02** | **Hiển thị Badge Bounty trên màn hình `ReviewDetail.jsx` (GAP-LOW-01)** | Cung cấp thông tin tài liệu này đang phục vụ yêu cầu Bounty nào và treo thưởng bao nhiêu điểm để Mod ưu tiên duyệt. | Bổ sung 1 khối thông tin nhỏ trong `ReviewDetail.jsx`. |
| **B-03** | **Cơ chế Hủy tự động & Hoàn tiền cho Bounty bị treo quá 14 ngày (GAP-MED-02)** | Tránh tình trạng sinh viên bị đóng băng điểm vĩnh viễn khi yêu cầu không có ai đóng góp hoặc bị bỏ quên. | Viết 1 Artisan command: `php artisan bounties:purge-expired`. |
| **B-04** | **Chuẩn hóa URI tài liệu bàn giao `STUDENT_ALUMNI_MODERATION_HANDOFF.md` (GAP-MED-01)** | Sửa dòng 341 từ `/tai-lieu/{id}/bao-cao` thành `/bao-cao/tai-lieu` để tài liệu đồng nhất với route backend. | Cập nhật tài liệu markdown. |
| **B-05** | **Bộ lọc tìm kiếm theo Khoa / Chuyên ngành trong Thùng rác Admin** | Hỗ trợ Quản trị viên lọc tài liệu đã xóa mềm theo ngành học để dễ đối soát trước khi dọn dẹp dung lượng. | Mở rộng query trong `GetDeletedDocumentsQuery.php`. |

---

### GROUP C — COULD HAVE (TIỆN ÍCH CẢI THIỆN TRẢI NGHIỆM — NẾU CÒN THỜI GIAN)

Các tính năng gia tăng giá trị nhưng hoàn toàn có thể nghiệm thu mà không cần có mặt:

1. **Thao tác duyệt giải ngân hàng loạt cho Bounty:** Hiện tại số lượng Bounty nộp cùng thời điểm không quá lớn; Kiểm duyệt viên hoàn toàn có thể kiểm tra từng tệp và duyệt đơn lẻ để đảm bảo an toàn.
2. **Cơ chế Phê duyệt tự động (Fast-Track Auto-Approval) theo Điểm uy tín:** Ý tưởng tự động duyệt file cho sinh viên có `diem_uy_tin >= 200`. Tạm thời hoãn vì rủi ro tài khoản uy tín bị hack hoặc nộp nhầm file bản quyền vẫn tồn tại. Mọi file nên được tiền kiểm thủ công trong giai đoạn đầu vận hành.
3. **Màn hình biểu đồ thống kê chuyên sâu trên Dashboard Quản trị:** Tạm thời sử dụng các khối số liệu Count thẻ tĩnh (`StatCard`) đã có sẵn, chưa cần tích hợp thư viện biểu đồ phức tạp (Recharts/Chart.js).
4. **Bộ lọc nâng cao theo dung lượng file trên giao diện Storage Management:** Đã có cảnh báo file lớn $\ge 20$MB, chưa cần bộ lọc thanh trượt đa chiều.

---

### GROUP D — OUT OF SCOPE / DEFER (HOÃN HOẶC LOẠI BỎ KHỎI ĐỢT NGHIỆM THU HIỆN TẠI)

Các chức năng bị mở rộng quá mức (Scope Creep) hoặc không khả thi trong giai đoạn này:

| STT | Chức năng đề xuất hoãn / loại bỏ | Lý do hoãn | Ảnh hưởng đến nghiệm thu | Điều kiện xem xét lại |
| :---: | :--- | :--- | :--- | :--- |
| **D-01** | **WebSockets Realtime Broadcasting (Laravel Reverb)** | Dự án đang chạy cơ chế Polling HTTP qua `GET /thong-bao/so-luong-chua-doc` rất nhẹ nhàng, tin cậy và không phụ thuộc daemon worker phức tạp trên hosting. | **KHÔNG ẢNH HƯỞNG**: Chuông thông báo vẫn cập nhật sau mỗi thao tác hoặc định kỳ. | Xem xét khi hệ thống có trên 5,000 người dùng trực tuyến đồng thời. |
| **D-02** | **Quét đạo văn / Quét bản quyền tự động bằng AI (Plagiarism AI Scanner)** | Vượt quá phạm vi một đồ án tốt nghiệp / cổng học thuật nội bộ; chi phí API và độ trễ xử lý quá lớn. Hệ thống đã có Deduplication nhị phân 100% bằng mã băm SHA-256. | **KHÔNG ẢNH HƯỞNG**: Đã có quy trình hậu kiểm và cơ chế Clawback thu hồi điểm khi có khiếu nại. | Xem xét khi nhà trường cấp kinh phí kết nối Turnitin API. |
| **D-03** | **Cơ chế Trọng tài phân giải tranh chấp Bounty đa cấp (Arbitration Court)** | Quá phức tạp. Hiện tại quy định rõ: Người tạo yêu cầu nghiệm thu trước, Admin duyệt giải ngân sau; nếu Admin từ chối thì 100% điểm hoàn trả cho Requester. | **KHÔNG ẢNH HƯỞNG**: Quy trình 2 lớp hiện tại đã bảo vệ tối đa cả 2 bên. | Xem xét khi phát sinh khiếu nại thực tế từ người dùng. |
| **D-04** | **Phân quyền động tùy biến chi tiết (Dynamic Permissions UI)** | RBAC hiện tại với 4 vai trò tĩnh (`QUAN_TRI_VIEN`, `KIEM_DUYET_VIEN`, `SINH_VIEN`, `CUU_SINH_VIEN`) kết hợp cấp bậc `CapQuanTri` là quá đủ chặt chẽ. Việc làm giao diện tick chọn từng quyền (Permissions Matrix) là không cần thiết. | **KHÔNG ẢNH HƯỞNG**: Bảo vệ vững chắc bằng Middleware cứng. | Xem xét nếu trường mở rộng thêm vai trò `GIANG_VIEN` hoặc `TRO_GIANG`. |
| **D-05** | **Tự động liên thông tài khoản với Cổng đào tạo trường qua SSO/LDAP** | Dự án đã giải quyết bài toán định danh hoàn hảo bằng cơ chế nạp sẵn dữ liệu `danh_sach_sinh_vien_raw` và Zero-KYC qua đổi mật khẩu lần đầu. | **KHÔNG ẢNH HƯỞNG**: Đảm bảo 100% sinh viên AGU có tài khoản chuẩn hóa mà không phụ thuộc hạ tầng mạng trường. | Xem xét khi Trung tâm Tin học AGU cung cấp API CAS/SAML2. |
| **D-06** | **Tự động nén và chuyển đổi định dạng tài liệu sang PDF trên server (File Transcoding)** | Tốn tài nguyên CPU server và phụ thuộc vào các thư viện nhị phân bên ngoài (LibreOffice, Unoconv). Hiện tại chỉ cho phép xem trước trực tiếp file PDF, các file ZIP/DOCX người dùng tải về máy để xem. | **KHÔNG ẢNH HƯỞNG**: Đã có thông báo rõ định dạng hỗ trợ xem trước trên giao diện. | Xem xét khi nâng cấp hạ tầng Cloud Storage có Worker riêng. |

---

## 3. KẾT LUẬN CHIẾN LƯỢC CHO PHẠM VI NGHIỆM THU

> [!TIP]
> Bằng việc tập trung tối đa nguồn lực vào **12 mục Group A** và xử lý dứt điểm **2 lỗi CRITICAL**, hai nhóm sẽ có một hệ thống **chạy thực tế trơn tru 100%, không lỗi runtime, không kẹt dữ liệu, và vượt qua toàn bộ các kịch bản kiểm thử tích hợp (End-to-End Workflow)**. Việc loại bỏ 6 mục Group D giúp đội ngũ tiết kiệm ít nhất 40-50 giờ làm việc không cần thiết, đảm bảo dự án về đích đúng hạn với chất lượng kỹ thuật cao nhất.

