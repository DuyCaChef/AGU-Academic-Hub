# TÀI LIỆU BÀN GIAO NGHIỆP VỤ KIỂM DUYỆT TÀI LIỆU (STUDENT / ALUMNI & MODERATION HANDOFF SPECIFICATION)

- **Dự án**: AGU Academic Hub
- **Phân hệ bàn giao**: Student / Alumni Integration & Course Detail Ecosystem (Phase 3A, 3B1, 3B2, 3B3, 3C)
- **Đối tượng tiếp nhận**: Thành viên phụ trách Module Moderation (Kiểm duyệt tài liệu, Xử lý vi phạm, Quản lý khiếu nại)
- **Ngày lập**: 07/11/2026
- **Trạng thái**: Hoàn thiện tích hợp phía Student/Alumni – Sẵn sàng kết nối giao diện Moderation

---

## 1. MỤC ĐÍCH VÀ BỐI CẢNH DỰ ÁN

Tài liệu này tổng hợp toàn bộ các quy tắc nghiệp vụ, luồng dữ liệu, trạng thái mô hình và các điểm chạm (`touchpoints`) liên quan đến kiểm duyệt tài liệu học thuật và đóng góp săn thưởng (`Bounty`), được hình thành qua các phân hệ:
1. **Academic Catalog & Course Detail (Phase 3A)**: Thông tin môn học, khoa, chuyên ngành.
2. **Course Document Listing (Phase 3B1)**: Danh sách tài liệu môn học, tìm kiếm, lọc, xem chi tiết, xem trước và tải xuống.
3. **Course Reviews & Community Voting (Phase 3B2)**: Đánh giá môn học đa tiêu chí, bình chọn hữu ích (Upvote/Downvote).
4. **Course Wishlist & Bounty Integration (Phase 3B3)**: Tạo yêu cầu tìm tài liệu, đóng băng điểm thưởng, nộp tài liệu giải quyết yêu cầu, giải ngân điểm thưởng.
5. **Document Contribution & Security Hardening (Phase 3C)**: Nộp tài liệu từ máy tính (Local upload), bảo mật tài liệu chưa duyệt (AUD-H01, AUD-H02), ngăn chặn giải ngân trên tài liệu chưa qua kiểm duyệt.

Tài liệu này được biên soạn nhằm giúp thành viên phụ trách **Module Moderation** nắm bắt trọn vẹn bức tranh tổng thể, các ràng buộc kỹ thuật đã được khóa (`scope lock`), các điều kiện tiền đề, và các câu hỏi mở cần giải quyết khi xây dựng giao diện và luồng xử lý của Kiểm duyệt viên.

---

## 2. KIẾN TRÚC MÔ HÌNH VÀ VÒNG ĐỜI DỮ LIỆU (STATE MACHINES)

### 2.1. Vòng đời Trạng thái Tài liệu (`tai_lieu.trang_thai`)

Mọi tệp tài liệu trong hệ thống AGU Academic Hub được quản lý bởi máy trạng thái (`State Machine`) nghiêm ngặt:

```
[Khởi tạo tải lên]
  │
  ├──> Đăng tải trực tiếp (Standard Upload) ──────> CHO_DUYET
  │                                                   │
  └──> Đóng góp giải quyết Bounty (Local Upload) ─────┘
                                                      │
                       ┌──────────────────────────────┴──────────────────────────────┐
                       ▼ (Kiểm duyệt viên duyệt)                                     ▼ (Kiểm duyệt viên từ chối)
                   DA_DUYET                                                       TU_CHOI
                       │                                                             │
        ┌──────────────┴──────────────┐                                       (Tác giả thấy lý do,
        ▼                             ▼                                        không hiển thị công khai)
 (Báo cáo vượt ngưỡng)         (Tác giả xóa mềm trong 24h & lượt tải = 0)
CHO_KIEM_TRA / TAM_AN_KIEM_TRA        │
        │                             ▼
        │                         XOA_MEM (Soft Delete)
        ▼                             │
(Hàng đợi xử lý vi phạm)               ▼
                                XOA_VINH_VIEN (Sau 30-60 ngày trong thùng rác)
```

| Mã trạng thái | Tên hiển thị | Mô tả nghiệp vụ | Phạm vi hiển thị & Quyền truy cập |
|---|---|---|---|
| `CHO_DUYET` | Chờ duyệt | Tài liệu vừa được tải lên từ sinh viên/cựu sinh viên (cả đăng trực tiếp lẫn nộp qua Bounty). Đang nằm trong hàng đợi kiểm duyệt ban đầu. | **Bảo mật tuyệt đối (AUD-H02)**: Chỉ Tác giả (`ma_nguoi_dang`), Quản trị viên (`QUAN_TRI_VIEN`), và Kiểm duyệt viên (`KIEM_DUYET_VIEN`) mới có quyền xem chi tiết, xem trước stream hoặc tải tệp. Người dùng khác nhận HTTP 403. Không xuất hiện trên Course Catalog. |
| `DA_DUYET` | Đã duyệt | Tài liệu đã được Ban kiểm duyệt kiểm tra và chấp thuận tính hợp lệ, không vi phạm bản quyền và quy chế học thuật. | Xuất hiện công khai trên Course Detail, tìm kiếm toàn hệ thống, người dùng có thể tải về, xem trước trực tuyến, và bình chọn. |
| `TU_CHOI` | Từ chối | Tài liệu không đạt yêu cầu kiểm duyệt (nội dung sai lệch, bản chụp mờ, tài liệu thương mại có bản quyền,...). | Tác giả thấy trạng thái và lý do từ chối. Bị ẩn hoàn toàn với sinh viên khác. |
| `CHO_KIEM_TRA` | Chờ kiểm tra | Tài liệu đã duyệt nhưng bị người dùng báo cáo vi phạm với số lượng đạt ngưỡng kiểm tra (`reviewThreshold`, mặc định: 3 báo cáo hợp lệ). | Vẫn tạm thời hiển thị công khai nhưng được ưu tiên đưa vào hàng đợi kiểm tra lại của Kiểm duyệt viên. |
| `TAM_AN_KIEM_TRA` | Tạm ẩn kiểm tra | Tài liệu bị báo cáo vi phạm đạt ngưỡng tạm ẩn (`autoHideThreshold`, mặc định: 5 báo cáo hợp lệ từ người dùng uy tín). | Tự động ẩn khỏi màn hình công khai cho đến khi Kiểm duyệt viên đưa ra phán quyết (`DecideDocumentReviewAction`). |
| `XOA_MEM` | Đã xóa mềm | Tài liệu bị tác giả xóa (chỉ được xóa trong 24 giờ đầu khi lượt tải = 0) hoặc bị Kiểm duyệt viên xóa do vi phạm quy định. | Chuyển vào Thùng rác (`lich_su_thung_rac`). Lưu trữ có thời hạn (30-60 ngày) trước khi Quản trị viên xóa vĩnh viễn. |

---

### 2.2. Vòng đời Trạng thái Yêu cầu Săn thưởng (`yeu_cau_tai_lieu.trang_thai`)

Yêu cầu tìm tài liệu (Wishlist/Bounty) liên kết chặt chẽ với số dư ví điểm (`vi_diem`) và quy trình phê duyệt giải ngân 2 lớp (Student Confirm -> Admin/Mod Approve):

```
Sinh viên tạo yêu cầu (Cọc tối thiểu 10 điểm)
              │
              ▼
             MO ─────────────────────────────┐ (Người tạo tự hủy yêu cầu)
              │                               ▼
              │ (Người tạo xác nhận       DA_HUY (Hoàn trả 100% điểm tạm giữ)
              │  bài nộp của SV B)
              ▼
        CHO_GIAI_NGAN (Mới) ─────────────────┐
        (Điểm VẪN ĐÓNG BĂNG trong ví SV A,    │ (Admin/Mod từ chối giải ngân:
         chờ Admin/Mod kiểm tra an toàn)      │  Hoàn 100% điểm tạm giữ về ví SV A,
              │                               │  mở lại yêu cầu để người khác nộp)
              │ (Admin/Mod duyệt giải ngân)   ▼
              ▼                              MO (Mở lại)
        DA_HOAN_THANH
  (Chính thức giải ngân điểm tạm giữ -> ví SV B)
```

| Trạng thái Yêu cầu | Tác động ví điểm | Mô tả |
|---|---|---|
| `MO` | Trừ `diem_kha_dung`, cộng `diem_tam_giu` của Requester (SV A) | Yêu cầu đang mở, hiển thị trên tab Wishlist của môn học. Cho phép thành viên nộp tài liệu phản hồi. |
| `CHO_GIAI_NGAN` | **Không thay đổi số dư ví** (Điểm thưởng vẫn an toàn trong `diem_tam_giu` của SV A) | SV A đã bấm "Xác nhận" chọn bài của SV B. Hệ thống chuyển sang hàng đợi Admin/Mod để kiểm tra mã độc, spam hoặc gian lận cày điểm. SV B **chưa** nhận được điểm tại bước này. |
| `DA_HOAN_THANH` | Trừ `diem_tam_giu` của SV A, cộng `diem_kha_dung` và cộng `diem_uy_tin` của SV B | Admin/Mod đã duyệt giải ngân thành công. Điểm thưởng và điểm uy tín chính thức được chuyển cho SV B. Yêu cầu hoàn tất. |
| `DA_HUY` | Hoàn trả: trừ `diem_tam_giu`, cộng lại `diem_kha_dung` của SV A | SV A tự hủy yêu cầu khi chưa nghiệm thu bài nộp nào. Toàn bộ điểm tạm giữ được hoàn trả ngay lập tức kèm bản ghi `GiaoDichDiem` loại `HOAN_TRA`. |

---

### 2.3. Vòng đời Trạng thái Bài nộp Săn thưởng (`dap_ung_yeu_cau_tai_lieu.trang_thai`)

```
Contributor (SV B) nộp bài (Method A hoặc B)
                 │
                 ▼
             CHO_DUYET ──────────────────────────────────┐
                 │                                       │ (Người tạo hoặc Admin từ chối)
                 │ (SV A bấm Xác nhận chọn bài này)      ▼
                 ▼                                    TU_CHOI
      NGUOI_YEU_CAU_XAC_NHAN (Mới)               (Lưu lý do từ chối:
                 │                                ly_do_tu_choi_giai_ngan)
                 │ (Admin/Mod duyệt giải ngân)
                 ▼
             CHAP_NHAN
```

| Trạng thái Bài nộp | Ý nghĩa | Điều kiện chuyển trạng thái |
|---|---|---|
| `CHO_DUYET` | Đang chờ nghiệm thu | Mặc định khi bài nộp được tạo. Bài nộp chưa được nhận điểm thưởng. |
| `NGUOI_YEU_CAU_XAC_NHAN` | SV A đã đồng ý chọn | SV A bấm "Xác nhận chọn bài này". **Điều kiện tiền đề**: Tài liệu đính kèm phải có trạng thái `DA_DUYET`. Trạng thái này đánh dấu bài nộp được gửi đến Admin/Mod để chờ duyệt giải ngân điểm. |
| `CHAP_NHAN` | Giải ngân thành công | Admin/Mod duyệt giải ngân thành công trên Admin Console. SV B nhận điểm thưởng vào ví. |
| `TU_CHOI` | Từ chối bài nộp | Do SV A từ chối hoặc Admin/Mod từ chối giải ngân (kèm `ly_do_tu_choi_giai_ngan` hiển thị cho sinh viên). Điểm thưởng của Requester không bị mất. |

---

## 3. CÁC ĐIỂM CHẠM VÀ QUY TẮC NGHIỆP VỤ BẮT BUỘC GIỮA HAI PHÂN HỆ

### 3.1. Phương thức Đóng góp Tài liệu (Bounty Fulfillment Methods)
Người dùng Student/Alumni có hai phương thức để nộp tài liệu giải quyết yêu cầu tìm tài liệu:

#### Phương thức A: Chọn từ Kho tài liệu cá nhân (Personal Library)
- **Luồng hoạt động**: Người nộp chọn một tài liệu đã tải lên từ trước trong kho cá nhân của mình (`tai-lieu/cua-toi`).
- **Quy tắc nghiệm ngặt (AUD-H01)**:
  1. Tài liệu phải thuộc quyền sở hữu của chính người nộp: `$document->ma_nguoi_dang === $user->ma_nguoi_dung`.
  2. Tài liệu bắt buộc phải có trạng thái `DA_DUYET` (đã được Kiểm duyệt viên phê duyệt từ trước). Tài liệu đang `CHO_DUYET` hoặc `TU_CHOI` sẽ bị từ chối ngay lập tức.
  3. Tài liệu phải thuộc cùng môn học với yêu cầu: `$document->ma_mon_hoc === $yeuCau->ma_mon_hoc`.
  4. Không được tự nộp tài liệu vào yêu cầu của chính mình (`$user->ma_nguoi_dung !== $yeuCau->ma_nguoi_tao`).
  5. Không được nộp một tài liệu hai lần vào cùng một yêu cầu (Chống spam duplicate).

#### Phương thức B: Tải lên tệp mới trực tiếp từ máy (Local File Upload)
- **Luồng hoạt động**: Người nộp kéo thả hoặc chọn tệp từ máy tính ngay tại Modal nộp bài trên giao diện chi tiết môn học / yêu cầu tài liệu.
- **Quy tắc an toàn và kiểm soát spam**:
  1. **Hạn mức tải lên (Rate Limiting)**: Tối đa **10 lượt tải lên/ngày/tài khoản** (theo SRS 3.2.1).
  2. **Kiểm tra tính toàn vẹn (Integrity)**: Tệp không được rỗng (`size > 0 bytes`), định dạng cho phép: `.pdf, .docx, .pptx, .zip`, dung lượng tối đa 50MB.
  3. **Lọc trùng nhị phân 100% (Binary SHA-256 Deduplication)**:
     - Hệ thống tính mã băm SHA-256 của toàn bộ tệp nhị phân (`hash_file('sha256', ...)`).
     - Kiểm tra trong phạm vi môn học hiện tại. Nếu đã có tài liệu trùng mã băm SHA-256, từ chối tải lên và thông báo rõ tài liệu đã tồn tại.
  4. **Khởi tạo trạng thái**:
     - Bản ghi `TaiLieu` mới được tạo với trạng thái **`CHO_DUYET`**.
     - Bản ghi `DapUngYeuCauTaiLieu` được tạo với trạng thái **`CHO_DUYET`**.
  5. **Bảo vệ điểm thưởng**:
     - **Tuyệt đối không giải ngân điểm tại thời điểm tải lên.**
     - Tệp tải lên được lưu an toàn trên ổ đĩa nội bộ (`local disk` trong thư mục `documents/`).
     - Tài liệu được đẩy vào hàng đợi kiểm duyệt để Kiểm duyệt viên thẩm định trước khi Người tạo có thể bấm nghiệm thu.

---

### 3.2. Quy trình Nghiệm thu và Duyệt Giải ngân Điểm thưởng 2 Lớp (Two-Step Bounty Payout Flow)

Nhằm phòng chống triệt để các hành vi gian lận (thông đồng cày điểm, tải tệp rác, file trắng, tài liệu chứa mã độc/virus để trục lợi điểm thưởng), hệ thống áp dụng **cơ chế xác thực 2 lớp** bắt buộc:

```
[SV B nộp bài] ──> [Kiểm duyệt tài liệu DA_DUYET] ──> [SV A bấm Xác nhận] ──> [Admin/Mod Duyệt giải ngân] ──> [SV B nhận điểm]
                                                                  │
                                                       (Điểm VẪN ĐÓNG BĂNG,
                                                        chưa chuyển cho SV B)
```

#### Bước 1: Sinh viên A (Người tạo yêu cầu) xác nhận chọn bài nộp
- **Endpoint**: `POST /yeu-cau-tai-lieu/{ma_yeu_cau}/xac-nhan`
- **Payload**: `{ ma_dap_ung: int }`
- **Điều kiện kiểm tra tại `ConfirmBountyFulfillmentAction`**:
  1. Quyền xác nhận: Chỉ người tạo yêu cầu (`$user->ma_nguoi_dung === $yeuCau->ma_nguoi_tao`).
  2. Trạng thái yêu cầu: Phải đang là `MO`.
  3. Trạng thái bài nộp: Phải đang là `CHO_DUYET`.
  4. Trạng thái tệp tài liệu: Bắt buộc đã được Ban kiểm duyệt duyệt (`taiLieu->trang_thai === 'DA_DUYET'`).
- **Hành động trong Transaction CSDL**:
  * Đánh dấu bài nộp: `dap_ung.trang_thai = 'NGUOI_YEU_CAU_XAC_NHAN'`.
  * Đổi trạng thái yêu cầu: `yeu_cau.trang_thai = 'CHO_GIAI_NGAN'`.
  * Ghi nhận: `ma_dap_ung_duoc_chon = $maDapUng`, `thoi_gian_nguoi_yeu_cau_xac_nhan = now()`.
  * **ĐIỂM THƯỞNG**: Điểm vẫn nằm nguyên ở `diem_tam_giu` của SV A, **TUYỆT ĐỐI CHƯA CỘNG CHO SV B**.
- **Thông báo**: Bắn `BountyPayoutApprovalRequiredNotification` vào chuông thông báo của tất cả Quản trị viên và Kiểm duyệt viên để đưa vào hàng đợi xử lý.

#### Bước 2: Ban Quản trị / Ban Kiểm duyệt thẩm định và ra quyết định giải ngân
- **Endpoint kiểm tra của Admin**:
  * Danh sách hàng đợi: `GET /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan` (`admin.bounties.payout-queue`).
  * Chi tiết yêu cầu: `GET /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/{ma_yeu_cau}` (`admin.bounties.payout-detail`).
- **Quyết định A: Phê duyệt giải ngân (`ApproveBountyPayoutAction`)**:
  * **Endpoint**: `PUT /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/{ma_yeu_cau}/duyet`
  * **Hành động**:
    1. Trừ `diem_tam_giu` của SV A, cộng `diem_kha_dung` cho SV B, tăng `diem_uy_tin` cho SV B thông qua `ReputationEvaluator::giaiNganDiemBounty`.
    2. Cập nhật `dap_ung.trang_thai = 'CHAP_NHAN'`, từ chối các bài nộp khác (`TU_CHOI`), đóng yêu cầu `yeu_cau.trang_thai = 'DA_HOAN_THANH'`.
    3. Ghi nhật ký kiểm toán: `HanhDongKiemToan::DUYET_GIAI_NGAN_BOUNTY`.
    4. Gửi notification `BountyPayoutApprovedNotification` chúc mừng SV B.
- **Quyết định B: Từ chối giải ngân & Hoàn điểm bảo vệ SV A (`RejectBountyPayoutAction`)**:
  * **Endpoint**: `PUT /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/{ma_yeu_cau}/tu-choi`
  * **Payload**: `{ ly_do: string }` (Tối thiểu 10 ký tự, giải thích rõ căn cứ từ chối).
  * **Hành động**:
    1. **Hoàn trả an toàn**: Chuyển toàn bộ điểm tạm giữ về lại số dư khả dụng (`diem_kha_dung`) của SV A qua `ReputationEvaluator::hoanDiemBounty`. SV A không bị mất điểm oan.
    2. Cập nhật `dap_ung.trang_thai = 'TU_CHOI'`, lưu `ly_do_tu_choi_giai_ngan`.
    3. Mở lại yêu cầu sang `yeu_cau.trang_thai = 'MO'`, reset `ma_dap_ung_duoc_chon = null` để sinh viên khác tiếp tục nộp bài.
    4. Ghi nhật ký kiểm toán: `HanhDongKiemToan::TU_CHOI_GIAI_NGAN_BOUNTY`.
    5. Gửi notification `BountyPayoutRejectedNotification` giải thích lý do cho cả SV A và SV B.

---

### 3.3. Bảo vệ Tài liệu Chưa Phê duyệt (AUD-H02 Protected Access)
- **Vấn đề**: Tài liệu `CHO_DUYET` chưa được kiểm tra về bản quyền, nội dung bạo lực, khiêu dâm, độc hại hoặc gian lận thi cử.
- **Cơ chế phân quyền đã thiết lập**:
  - `GET /tai-lieu/{id}` (`GetDocumentDetailQuery`): Kiểm tra quyền. Nếu không phải tác giả và không phải cán bộ (Admin/Mod) -> HTTP 403 Forbidden.
  - `GET /tai-lieu/{id}/xem-truoc` (`PreviewDocumentAction`): Chặn stream PDF -> HTTP 403 Forbidden.
  - `GET /tai-lieu/{id}/tai-ve` (`DownloadDocumentAction`): Chặn tải tệp đính kèm -> HTTP 403 Forbidden.
- **Ngoại lệ cho Kiểm duyệt viên và Quản trị viên**:
  - Kiểm duyệt viên (`laKiemDuyetVien() === true`) và Quản trị viên (`laQuanTriVien() === true`) **luôn có toàn quyền xem chi tiết, xem trước stream nội dung và tải file gốc** để phục vụ việc thẩm định nội dung trước khi ra quyết định.

---

### 3.4. Đánh giá Môn học & Bình chọn Cộng đồng (Course Reviews & Community Voting)
- Sinh viên và cựu sinh viên đánh giá môn học theo 5 tiêu chí: Điểm tổng quan (1-5 sao), Mức độ hữu ích, Độ khó đề thi, Tính ứng dụng thực tế, Phương pháp giảng dạy.
- **Cơ chế Community Voting**:
  - Người dùng có thể bấm `Upvote` (+1) hoặc `Downvote` (-1) cho từng bài đánh giá để xây dựng `diem_huu_ich`.
  - **Quy tắc độc lập**: Bình chọn cộng đồng của Review **chỉ thay đổi điểm cộng đồng của bài đánh giá, tuyệt đối không được cộng/trừ vào ví điểm (`vi_diem`) hay điểm uy tín (`diem_uy_tin`) của người viết review**.
- **Cơ chế Báo cáo vi phạm Review**:
  - Người dùng có thể báo cáo bài đánh giá có ngôn từ thù địch, xúc phạm giảng viên, hoặc thông tin sai lệch.
  - Báo cáo này cần được chuyển về hàng đợi kiểm duyệt để Kiểm duyệt viên xử lý ẩn bài đánh giá hoặc cảnh cáo tài khoản.

---

## 4. SƠ ĐỒ TUẦN TỰ NGHIỆP VỤ (MERMAID SEQUENCE DIAGRAMS)

### Sơ đồ 1: Luồng Đăng tải và Kiểm duyệt Tài liệu (Document Moderation Flow)

```mermaid
sequenceDiagram
    autonumber
    actor Contributor as Sinh viên đóng góp
    participant Frontend as Client (Giao diện Hub)
    participant DocController as Document Controller
    participant Storage as File Storage (Local)
    participant DB as Cơ sở dữ liệu (MySQL)
    actor Moderator as Ban Kiểm duyệt

    Contributor->>Frontend: Chọn tệp tải lên (PDF, DOCX, ZIP)
    Frontend->>Frontend: Tính mã băm SHA-256 client
    Frontend->>DocController: POST /tai-lieu/kiem-tra-trung-lap
    DocController->>DB: Tra cứu SHA-256 trong cùng môn học
    DB-->>DocController: Không trùng lặp
    DocController-->>Frontend: HTTP 200 OK (Được phép tải lên)

    Contributor->>Frontend: Bấm "Đăng tài liệu"
    Frontend->>DocController: POST /tai-lieu/dang-tai (multipart/form-data)
    DocController->>DocController: Kiểm tra Rate Limit (<= 10 lượt/ngày)
    DocController->>Storage: Lưu tệp an toàn vào thư mục /documents
    DocController->>DB: INSERT tai_lieu (trang_thai = 'CHO_DUYET')
    DB-->>DocController: Đã tạo bản ghi
    DocController-->>Frontend: Thành công (Chờ Ban kiểm duyệt)

    Note over DB,Moderator: Tài liệu xuất hiện trong Hàng đợi kiểm duyệt
    Moderator->>Frontend: Mở trang Chi tiết thẩm định tài liệu
    Frontend->>DocController: GET /quan-tri/tai-lieu/{id}/kiem-tra
    DocController->>Storage: Stream xem trước nội dung tệp
    DocController-->>Frontend: Hiển thị nội dung tệp và metadata
    
    alt Trường hợp Phê duyệt
        Moderator->>DocController: PUT /quan-tri/tai-lieu/{id}/quyet-dinh (action: 'KEEP' / 'RESTORE')
        DocController->>DB: UPDATE tai_lieu SET trang_thai = 'DA_DUYET'
        DocController->>DB: Ghi Audit Log & Gửi Thông báo cho Tác giả
        Note over DB: Tài liệu xuất hiện công khai trên Catalog & Course Detail
    else Trường hợp Từ chối / Xóa
        Moderator->>DocController: PUT /quan-tri/tai-lieu/{id}/quyet-dinh (action: 'DELETE', ly_do)
        DocController->>DB: UPDATE tai_lieu SET trang_thai = 'TU_CHOI' hoặc Soft Delete
        DocController->>DB: Ghi Thùng rác & Gửi Thông báo lý do cho Tác giả
    end
```

---

### Sơ đồ 2: Luồng Đóng góp Săn thưởng & Duyệt Giải ngân 2 Lớp (Bounty Two-Step Payout Flow)

```mermaid
sequenceDiagram
    autonumber
    actor Requester as Sinh viên A (Tạo Yêu cầu)
    actor Contributor as Sinh viên B (Đóng góp)
    participant BountySystem as Wishlist / Bounty System
    participant DocModule as Document Module
    actor Moderator as Ban Quản trị / Kiểm duyệt
    participant Reputation as Reputation & Wallet Engine

    Requester->>BountySystem: POST /yeu-cau-tai-lieu (Đóng băng 50 điểm thưởng)
    BountySystem->>Reputation: Đóng băng 50 điểm (Kha_dung: -50, Tam_giu: +50)
    Note over BountySystem: Yêu cầu mở (trang_thai: 'MO')

    alt Phương thức B: Sinh viên B tải tệp mới từ máy
        Contributor->>BountySystem: POST /yeu-cau-tai-lieu/{id}/dong-gop (Gửi kèm file)
        BountySystem->>DocModule: Tạo TaiLieu mới (trang_thai: 'CHO_DUYET')
        BountySystem->>BountySystem: Tạo DapUngYeuCau (trang_thai: 'CHO_DUYET')
        Note over Contributor,Requester: Điểm thưởng VẪN ĐÓNG BĂNG, chưa giải ngân
        
        Note over Moderator: Kiểm duyệt viên thẩm định file ban đầu
        Moderator->>DocModule: Phê duyệt tài liệu -> UPDATE tai_lieu SET trang_thai = 'DA_DUYET'
    end

    Note over Requester: BƯỚC 1: Sinh viên A xác nhận chọn bài nộp
    Requester->>BountySystem: POST /yeu-cau-tai-lieu/{id}/xac-nhan (ma_dap_ung)
    BountySystem->>BountySystem: Kiểm tra tai_lieu->trang_thai == 'DA_DUYET' (HỢP LỆ)
    BountySystem->>BountySystem: UPDATE yeu_cau SET trang_thai = 'CHO_GIAI_NGAN'
    BountySystem->>BountySystem: UPDATE dap_ung SET trang_thai = 'NGUOI_YEU_CAU_XAC_NHAN'
    Note over BountySystem,Reputation: ĐIỂM VẪN ĐÓNG BĂNG, SV B CHƯA ĐƯỢC CỘNG ĐIỂM
    BountySystem->>Moderator: Bắn thông báo: "Yêu cầu #id cần duyệt giải ngân"

    Note over Moderator: BƯỚC 2: Admin/Mod kiểm tra an toàn và quyết định
    alt Trường hợp A: Admin/Mod PHÊ DUYỆT GIẢI NGÂN
        Moderator->>BountySystem: PUT /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/{id}/duyet
        BountySystem->>Reputation: Giải ngân điểm Bounty (giaiNganDiemBounty):
        Reputation->>Reputation: Ví A: Diem_tam_giu -= 50
        Reputation->>Reputation: Ví B: Diem_kha_dung += 50
        Reputation->>Reputation: Điểm uy tín B += 50
        BountySystem->>BountySystem: UPDATE dap_ung SET trang_thai = 'CHAP_NHAN'
        BountySystem->>BountySystem: UPDATE yeu_cau SET trang_thai = 'DA_HOAN_THANH'
        BountySystem-->>Contributor: Thông báo: "Đã nhận 50 điểm thưởng từ yêu cầu #id!"
    else Trường hợp B: Admin/Mod TỪ CHỐI GIẢI NGÂN (Có nghi vấn mã độc / gian lận)
        Moderator->>BountySystem: PUT /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/{id}/tu-choi (Lý do)
        BountySystem->>Reputation: Hoàn điểm bảo vệ SV A (hoanDiemBounty):
        Reputation->>Reputation: Ví A: Diem_tam_giu -= 50, Diem_kha_dung += 50
        BountySystem->>BountySystem: UPDATE dap_ung SET trang_thai = 'TU_CHOI' (kèm lý do)
        BountySystem->>BountySystem: UPDATE yeu_cau SET trang_thai = 'MO' (mở lại để người khác nộp)
        BountySystem-->>Requester: Thông báo: "Bài nộp bị từ chối, đã hoàn 50 điểm vào ví của bạn"
        BountySystem-->>Contributor: Thông báo: "Bài đóng góp bị từ chối giải ngân kèm lý do"
    end
```

---

### Sơ đồ 3: Luồng Báo cáo Vi phạm & Tự động Tạm ẩn (Violation Report & Auto-Hide Flow)

```mermaid
sequenceDiagram
    autonumber
    actor Reporter as Sinh viên phát hiện vi phạm
    participant ModerationAPI as Moderation / Report API
    participant Recalculator as DocumentReportRecalculator
    participant DB as Cơ sở dữ liệu
    actor Moderator as Ban Kiểm duyệt

    Reporter->>ModerationAPI: POST /tai-lieu/{id}/bao-cao (Lý do vi phạm bản quyền / sai môn học)
    ModerationAPI->>DB: INSERT bao_cao_vi_pham
    ModerationAPI->>Recalculator: recalculate(documentId)
    Recalculator->>DB: Đếm số báo cáo hợp lệ từ người dùng uy tín (diem_uy_tin >= 100)
    
    alt Số báo cáo >= 3 (Ngưỡng Review)
        Recalculator->>DB: UPDATE tai_lieu SET trang_thai = 'CHO_KIEM_TRA'
        Recalculator->>DB: Tạo Notification báo cho Ban kiểm duyệt
    end
    
    alt Số báo cáo >= 5 (Ngưỡng Auto-Hide)
        Recalculator->>DB: UPDATE tai_lieu SET trang_thai = 'TAM_AN_KIEM_TRA'
        Note over DB: Tài liệu lập tức biến mất khỏi Course Detail và Catalog
    end

    Moderator->>ModerationAPI: GET /quan-tri/tai-lieu/cho-kiem-tra (Hàng đợi vi phạm)
    Moderator->>ModerationAPI: PUT /quan-tri/tai-lieu/{id}/quyet-dinh
    alt Bác bỏ báo cáo
        Moderator->>ModerationAPI: Action: 'KEEP'
        ModerationAPI->>DB: SET trang_thai = 'DA_DUYET', SET trang_thai_xu_ly báo cáo = 'BAC_BO'
    else Xác nhận vi phạm
        Moderator->>ModerationAPI: Action: 'DELETE', Lý do: "Vi phạm bản quyền NXB"
        ModerationAPI->>DB: Soft delete tài liệu, ghi lịch sử thùng rác
        ModerationAPI->>DB: Giảm điểm uy tín của tác giả (Penalize Reputation)
    end
```

---

## 5. MA TRẬN PHÂN QUYỀN TRÁCH NHIỆM (RACI MATRIX)

Ký hiệu:
- **R (Responsible)**: Người trực tiếp thực hiện hành động.
- **A (Accountable)**: Người chịu trách nhiệm phê duyệt cuối cùng và giải trình.
- **C (Consulted)**: Người được tham vấn, đóng góp ý kiến hoặc phản hồi.
- **I (Informed)**: Người nhận thông báo kết quả.

| Hoạt động / Nghiệp vụ | Sinh viên (`SINH_VIEN`) | Cựu sinh viên (`CUU_SINH_VIEN`) | Kiểm duyệt viên (`KIEM_DUYET_VIEN`) | Quản trị viên (`QUAN_TRI_VIEN`) |
|---|---|---|---|---|
| **Đăng tài liệu độc lập** | R | R | A | I |
| **Tải file mới qua Bounty (Method B)** | R | R | A | I |
| **Xem tài liệu đang chờ duyệt (`CHO_DUYET`)** | R (Chỉ tác giả) | R (Chỉ tác giả) | A / R | A / R |
| **Phê duyệt / Từ chối tài liệu mới** | - | - | **R / A** | **A** |
| **Tạo yêu cầu tìm tài liệu (Wishlist)** | R | R | - | I |
| **Hủy yêu cầu & Hoàn điểm tạm giữ** | R (Chỉ người tạo) | R (Chỉ người tạo) | - | A |
| **Nộp tài liệu săn thưởng (Method A)** | R | R | I | I |
| **Xác nhận chọn bài nộp Bounty (Bước 1)** | R (Chỉ người tạo) | R (Chỉ người tạo) | I | I |
| **Duyệt / Từ chối giải ngân Bounty (Bước 2)** | - | - | **R / A** | **A** |
| **Báo cáo vi phạm tài liệu** | R | R | A | A |
| **Xử lý tài liệu trong Hàng đợi vi phạm** | I | I | **R / A** | **A** |
| **Khôi phục tài liệu từ Thùng rác** | - | - | R | **A** |
| **Xóa vĩnh viễn tài liệu khỏi đĩa cứng** | - | - | - | **R / A** |
| **Bình chọn Review (Upvote / Downvote)** | R | R | - | - |

---

## 6. BẢNG PHÂN ĐỊNH TRẠNG THÁI TRIỂN KHAI (IMPLEMENTATION STATUS TABLE)

| Hạng mục chức năng | Module phụ trách hiện tại | Trạng thái | Ghi chú & Điểm nối cho Moderation Member |
|---|---|---|---|
| Chặn lộ lọt tài liệu `CHO_DUYET` (AUD-H02) | Documents / Bounties | `[IMPLEMENTED]` | Đã có HTTP 403 cho người ngoài trong `GetDocumentDetailQuery`, `PreviewDocumentAction`, `DownloadDocumentAction`. |
| Chặn nộp tài liệu chưa duyệt / sai chủ sở hữu (AUD-H01) | Bounties | `[IMPLEMENTED]` | Đã kiểm tra trong `BountyRuleEvaluator::canFulfillWithDocument` và `FulfillBountyRequestAction`. |
| Chặn giải ngân khi tài liệu chưa `DA_DUYET` | Bounties | `[IMPLEMENTED]` | Đã kiểm tra trong `ConfirmBountyFulfillmentAction`. |
| Upload file mới từ máy tính vào Bounty (Method B) | Bounties / Documents | `[IMPLEMENTED]` | Lưu disk `local`, tạo `TaiLieu` (`CHO_DUYET`), dedup SHA-256, rate limit 10/ngày. |
| Modal Fulfill 2 tabs & tiền kiểm SHA-256 Web Crypto | Frontend (React) | `[IMPLEMENTED]` | Đã hoàn thiện trong `FulfillRequestModal.jsx`. |
| Hàng đợi duyệt tài liệu báo cáo vi phạm | Moderation | `[IMPLEMENTED]` | `GetReviewQueueQuery` và `DocumentModerationController`. |
| Xử lý quyết định vi phạm (`KEEP`, `RESTORE`, `HIDE`, `DELETE`) | Moderation | `[IMPLEMENTED]` | `DecideDocumentReviewAction` hoạt động với Audit Log. |
| **Hàng đợi duyệt tài liệu mới đăng (`CHO_DUYET`)** | **Moderation** | `[PARTIALLY IMPLEMENTED]` | Backend query hiện lọc `['CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA']`. Cần mở rộng query để lấy cả danh sách tài liệu mới `CHO_DUYET`. |
| **Hàng đợi duyệt giải ngân Bounty (`CHO_GIAI_NGAN`)** | **Bounties / Admin** | `[IMPLEMENTED]` | Đã có màn hình `/quan-tri/yeu-cau-tai-lieu/cho-giai-ngan`, controller `AdminBountyPayoutController`, 2 Action Duyệt/Từ chối và thông báo tự động. |
| **Quy trình giải ngân 2 lớp (Requester -> Admin/Mod)** | **Bounties** | `[IMPLEMENTED]` | `ConfirmBountyFulfillmentAction` chỉ chuyển trạng thái sang `CHO_GIAI_NGAN`, `ApproveBountyPayoutAction` và `RejectBountyPayoutAction` xử lý giải ngân/hoàn điểm. |
| **Cơ chế nộp lại khi bài nộp bị từ chối** | **Bounties** | `[IMPLEMENTED]` | Khi Admin từ chối giải ngân, yêu cầu được mở lại `MO`, điểm tạm giữ được hoàn trả ngay về ví của SV A, bài nộp lưu `ly_do_tu_choi_giai_ngan`. |

---

## 7. CÂU HỎI MỞ VÀ ĐỀ XUẤT CHO THÀNH VIÊN MODERATION (OPEN QUESTIONS FOR MODERATION MEMBER)

Dưới đây là 7 vấn đề nghiệp vụ và thiết kế kiến trúc then chốt cần thành viên phụ trách Moderation làm rõ và đưa vào kịch bản hoàn thiện giao diện:

### 1. Tích hợp Hàng đợi Duyệt ban đầu (`Initial Review Queue`)
- **Hiện trạng**: Màn hình `admin.documents.review-queue` hiện chỉ truy vấn các tài liệu có `trang_thai IN ('CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA')` (do bị báo cáo vi phạm). Các tài liệu mới tải lên (`CHO_DUYET`) chưa có tab hiển thị riêng trên giao diện Admin/Moderator.
- **Câu hỏi cho bạn**:
  - Bạn dự định gom chung vào 1 bảng có bộ lọc (Filter Tab: "Tài liệu mới chờ duyệt" | "Tài liệu bị báo cáo vi phạm"), hay tạo trang riêng `admin.documents.pending-approval`?
  - *Khuyến nghị*: Gom chung với Filter Tabs `status: all | pending | reported | auto_hidden` để Kiểm duyệt viên dễ dàng lọc trên cùng 1 trang.

### 2. Ưu tiên xử lý tài liệu Đóng góp Săn thưởng (Bounty Priority Tagging)
- **Vấn đề**: Tài liệu đóng góp Bounty có liên quan đến việc đóng băng điểm của sinh viên khác. Nếu tài liệu nằm chờ duyệt quá lâu, Requester sẽ sốt ruột và có thể hủy yêu cầu.
- **Câu hỏi cho bạn**:
  - Có nên hiển thị nhãn ưu tiên `[BOUNTY SUBMISSION - X ĐIỂM]` và sắp xếp lên đầu hàng đợi kiểm duyệt để xử lý nhanh hơn tài liệu thường không?

### 3. Xử lý đồng bộ khi Kiểm duyệt viên từ chối tài liệu nộp Bounty
- **Hiện trạng**: Khi Kiểm duyệt viên chọn `action = 'DELETE'` hoặc gán `TU_CHOI` cho `TaiLieu`:
- **Câu hỏi cho bạn**:
  - Bản ghi `dap_ung_yeu_cau_tai_lieu` tương ứng có tự động cập nhật sang `TU_CHOI` không?
  - Sinh viên đóng góp có được phép nộp lại file mới cho cùng một yêu cầu đó không?
  - *Khuyến nghị*: Khi từ chối `TaiLieu`, tự động set `dap_ung_yeu_cau_tai_lieu.trang_thai = 'TU_CHOI'`, gửi kèm lý do từ chối vào thông báo cho Contributor để họ sửa file và nộp lại bản khác.

### 4. Thời hạn kiểm duyệt tối đa và Cơ chế Tự động Hủy/Hoàn điểm (SLA Timeout)
- **Vấn đề**: Sinh viên A tạo Bounty 50 điểm. Sinh viên B nộp tài liệu vào ngày 1. Ban kiểm duyệt không xử lý trong suốt 14 ngày.
- **Câu hỏi cho bạn**:
  - Hệ thống có nên quy định SLA kiểm duyệt (ví dụ: tối đa 72 giờ)?
  - Nếu quá hạn mà chưa duyệt, Sinh viên A có quyền bấm "Rút lại cọc điểm / Hủy yêu cầu" không, hay phải chờ Moderator giải quyết xong?

### 5. Cơ chế Phê duyệt Tự động (Fast-Track / Auto-Approval) theo Điểm uy tín
- **Vấn đề**: Các sinh viên có điểm uy tín cao (ví dụ: `diem_uy_tin >= 200` hoặc Cựu sinh viên đã KYC thành công) khi đăng tài liệu có cần phải chờ duyệt thủ công từng file không?
- **Câu hỏi cho bạn**:
  - Có nên thiết lập cấu hình `moderation.auto_approve_min_reputation`: nếu tác giả có uy tín cao và file vượt qua kiểm tra hash/virus, tự động set `DA_DUYET` ngay lập tức; chỉ chuyển sang kiểm duyệt nếu có báo cáo vi phạm (hậu kiểm)?

### 6. Xử lý Thu hồi Điểm khi Tài liệu bị Xóa do Vi phạm Bản quyền sau khi Đã Giải ngân
- **Kịch bản**: Sinh viên B nộp tài liệu, được duyệt, Sinh viên A nghiệm thu và chuyển 50 điểm cho B. 1 tuần sau, tác giả gốc khiếu nại bản quyền, Kiểm duyệt viên xác minh đúng là vi phạm và xóa tài liệu.
- **Câu hỏi cho bạn**:
  - Có thu hồi (clawback) 50 điểm đã giải ngân từ ví của B không?
  - Nếu ví của B đã tiêu hết điểm thì số dư có được phép âm không, hay trừ vào điểm uy tín (`diem_uy_tin`) của B và khóa tài khoản?

### 7. Kiểm duyệt Bài đánh giá Môn học (Course Reviews)
- **Vấn đề**: Hiện tại Phase 3B2 đã hoàn thiện bình chọn Upvote/Downvote và báo cáo vi phạm review.
- **Câu hỏi cho bạn**:
  - Giao diện Moderation của bạn đã có màn hình quản lý các Báo cáo bài đánh giá (`Review Violation Queue`) tương tự như tài liệu chưa, hay mới chỉ làm cho tài liệu?
  - *Khuyến nghị*: Có thể tái sử dụng cấu trúc bảng `bao_cao_vi_pham` (hoặc mở rộng cột `loai_doi_tuong: TAI_LIEU | DANH_GIA`) để hiển thị chung trong cùng module Moderation.

---

## 8. HƯỚNG DẪN KẾT NỐI API & GIAO DIỆN PHÍA STUDENT (STUDENT INTEGRATION CONTRACT)

Phần này đặc biệt dành cho đồng nghiệp phụ trách giao diện người dùng **Sinh viên / Cựu sinh viên (Student/Alumni)** để kết nối đồng bộ 100% với Backend và Admin Console:

### 8.1. Các thay đổi về Data Model & Schema
Sinh viên gọi API chi tiết yêu cầu (`GET /yeu-cau-tai-lieu/{ma_yeu_cau}`) sẽ nhận được cấu trúc dữ liệu mới:

1. **`yeu_cau_tai_lieu`**:
   - `trang_thai`: Bổ sung giá trị `'CHO_GIAI_NGAN'`.
     * `MO`: Đang mở tìm kiếm, cho phép nộp bài.
     * `CHO_GIAI_NGAN`: SV A đã bấm xác nhận, chờ Admin/Mod duyệt giải ngân.
     * `DA_HOAN_THANH`: Đã duyệt và giải ngân điểm thành công.
     * `DA_HUY`: Yêu cầu đã hủy, điểm hoàn trả.
   - `ma_dap_ung_duoc_chon`: (int | null) Mã bài nộp mà SV A đã chọn.
   - `thoi_gian_nguoi_yeu_cau_xac_nhan`: (datetime | null) Thời điểm SV A nhấn xác nhận.
   - Quan hệ Eloquent: `$yeuCau->dapUngDuocChon` (liên kết với bài nộp được chọn).

2. **`dap_ung_yeu_cau_tai_lieu`**:
   - `trang_thai`: Bổ sung giá trị `'NGUOI_YEU_CAU_XAC_NHAN'`.
     * `CHO_DUYET`: Bài nộp mới, chờ SV A xem xét.
     * `NGUOI_YEU_CAU_XAC_NHAN`: Được SV A bấm chọn, đang chờ Admin duyệt.
     * `CHAP_NHAN`: Admin đã duyệt, đã nhận điểm thưởng.
     * `TU_CHOI`: Bị từ chối bởi SV A hoặc bởi Admin.
   - `ly_do_tu_choi_giai_ngan`: (string | null) Lý do Admin từ chối giải ngân (ví dụ: *"File rác", "Chứa mã độc", "Sai môn học"*).

---

### 8.2. Hướng dẫn hiển thị Giao diện trên trang Chi tiết Yêu cầu (Wishlist/Show.jsx)

#### A. Đối với Sinh viên A (Người tạo yêu cầu - Requester)
1. **Khi yêu cầu ở trạng thái `MO`**:
   - Hiển thị danh sách các bài nộp.
   - Bài nộp có `tai_lieu.trang_thai === 'DA_DUYET'` $\rightarrow$ Hiển thị nút **[Xác nhận nhận tài liệu]**.
   - Bài nộp có `tai_lieu.trang_thai === 'CHO_DUYET'` $\rightarrow$ Disable nút [Xác nhận] kèm chú thích: *"Tài liệu đang chờ Ban kiểm duyệt duyệt nội dung trước khi bạn có thể nghiệm thu"*.
   - Cho phép bấm nút **[Hủy yêu cầu]** để nhận lại 100% điểm tạm giữ vào ví.

2. **Khi SV A bấm [Xác nhận nhận tài liệu]**:
   - Gửi request: `POST /yeu-cau-tai-lieu/{ma_yeu_cau}/xac-nhan` với body `{ ma_dap_ung: number }`.
   - Phản hồi thành công từ server:
     ```json
     {
       "success": true,
       "message": "Đã xác nhận bài đóng góp! Hệ thống đang chờ Admin duyệt để giải ngân 30 điểm cho Nguyễn Văn B."
     }
     ```
   - **Cập nhật giao diện sau khi bấm**:
     * Huy hiệu trạng thái yêu cầu chuyển thành: `Chờ Admin duyệt giải ngân` (màu hổ phách / warning).
     * Bài nộp được chọn hiển thị huy hiệu: `Đã chọn · Chờ Admin duyệt giải ngân`.
     * Ẩn nút "Xác nhận" và ẩn nút "Hủy yêu cầu" (đang trong quá trình hậu kiểm).
     * Hiển thị Alert thông tin: *"Bạn đã chọn tài liệu này. Điểm thưởng của bạn đang được tạm giữ an toàn và sẽ chính thức được chuyển cho người đóng góp sau khi Admin kiểm tra an toàn tệp tin."*

3. **Khi Admin phê duyệt giải ngân**:
   - Yêu cầu chuyển sang `DA_HOAN_THANH`. Huy hiệu màu xanh lá: `Đã hoàn thành & Đã giải ngân`.

4. **Khi Admin từ chối giải ngân**:
   - Yêu cầu tự động quay lại `MO`. Nút [Hủy yêu cầu] xuất hiện lại.
   - Số dư khả dụng (`diem_kha_dung`) của SV A được hoàn trả lại nguyên vẹn.
   - SV A nhận được Database Notification: *"Bài đóng góp bạn xác nhận cho yêu cầu #id đã bị Admin từ chối. Lý do: [Lý do]. Điểm thưởng đã được hoàn trả vào ví của bạn. Yêu cầu đã được mở lại."*
   - SV A có thể tiếp tục xem và chọn các bài nộp khác hoặc chờ người khác đóng góp.

---

#### B. Đối với Sinh viên B (Người nộp bài đóng góp - Contributor)
1. **Sau khi SV A xác nhận**:
   - Bài nộp của SV B hiển thị huy hiệu: `Người yêu cầu đã xác nhận · Chờ Admin duyệt giải ngân`.
   - SV B **chưa** nhận được điểm trong ví tại thời điểm này.
2. **Khi Admin phê duyệt (`ApproveBountyPayoutAction`)**:
   - Bài nộp chuyển sang `CHAP_NHAN`.
   - SV B nhận được Notification: *"Chúc mừng! Tài liệu đóng góp của bạn cho yêu cầu #id đã được Admin duyệt. Bạn nhận được {diem_thuong} điểm thưởng."* (link: `/vi-diem`).
   - Ví điểm của SV B tự động được cộng điểm khả dụng và điểm uy tín.
3. **Khi Admin từ chối (`RejectBountyPayoutAction`)**:
   - Bài nộp chuyển sang `TU_CHOI`.
   - Hiển thị thông báo lý do từ chối: `{dap_ung.ly_do_tu_choi_giai_ngan}`.
   - SV B nhận được Notification: *"Bài đóng góp của bạn cho yêu cầu #id đã bị Admin từ chối giải ngân. Lý do: [Lý do]."*
   - SV B có thể dựa vào lý do để khắc phục (tải file đúng chuẩn hơn) và nộp lại bài đóng góp mới.

---

### 8.3. Danh sách Notifications (Database Channel) cần đồng bộ trên NotificationDropdown.jsx

Các thông báo đều được lưu tại bảng `notifications` với kênh `database`. Thuộc tính `data` có định dạng:

1. **`BountyPayoutApprovedNotification` (Gửi cho SV B khi được duyệt)**:
   ```json
   {
     "title": "Điểm thưởng Bounty đã được giải ngân",
     "message": "Chúc mừng! Tài liệu đóng góp của bạn cho yêu cầu #12 đã được Admin duyệt. Bạn nhận được 30 điểm thưởng.",
     "ma_yeu_cau": 12,
     "diem_thuong": 30,
     "url": "/vi-diem"
   }
   ```

2. **`BountyPayoutRejectedNotification` (Gửi cho SV A khi bị từ chối)**:
   ```json
   {
     "title": "Bài đóng góp bị từ chối bởi Admin",
     "message": "Bài đóng góp bạn xác nhận cho yêu cầu #12 đã bị Admin từ chối. Lý do: File rác không có nội dung. Điểm thưởng 30 điểm đã được hoàn trả vào ví của bạn. Yêu cầu đã được mở lại.",
     "ma_yeu_cau": 12,
     "url": "/yeu-cau-tai-lieu/12"
   }
   ```

3. **`BountyPayoutRejectedNotification` (Gửi cho SV B khi bị từ chối)**:
   ```json
   {
     "title": "Bài đóng góp của bạn bị Admin từ chối giải ngân",
     "message": "Bài đóng góp của bạn cho yêu cầu #12 đã bị từ chối duyệt giải ngân. Lý do: File rác không có nội dung.",
     "ma_yeu_cau": 12,
     "url": "/yeu-cau-tai-lieu/12"
   }
   ```

---

## 9. TỔNG KẾT BÀN GIAO

Toàn bộ nền tảng dữ liệu, bảo mật, API và giao diện tương tác của nhóm người dùng `SINH_VIEN` và `CUU_SINH_VIEN` trong việc Đăng tải tài liệu, Nộp tài liệu Bounty, Đóng băng điểm, Chặn giải ngân khi chưa duyệt, Bảo vệ tài liệu chờ duyệt và **Hàng đợi kiểm duyệt giải ngân 2 lớp của Ban Quản trị** đã được kiểm thử và tích hợp hoàn chỉnh.

Thành viên phụ trách phân hệ Student/Alumni có thể căn cứ trực tiếp vào Section 8 của tài liệu này để hoàn thiện giao diện hiển thị badge, xử lý button và chuông thông báo mà không gặp bất kỳ vướng mắc hay thiếu hụt thông tin nào.


