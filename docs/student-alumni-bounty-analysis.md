# TÀI LIỆU PHÂN TÍCH HIỆN TRẠNG VÀ TÁC ĐỘNG HỆ THỐNG (PHASE 2)
## Phân hệ Sinh viên & Cựu sinh viên (Student/Alumni) — Hệ thống Yêu cầu Tài liệu & Săn thưởng (Wishlist/Bounty)
**Dự án:** AGU Academic Hub  
**Tác giả:** Senior Laravel Backend Engineer, Software Architect & Full-stack Developer  
**Ngày lập:** 10/11/2026  
**Trạng thái:** Đã hoàn thành khảo sát và phân tích tác động (Ready for Design & Implementation)

---

## 1. TỔNG QUAN KIẾN TRÚC HIỆN TẠI

Hệ thống **AGU Academic Hub** đang vận hành theo kiến trúc **Modular Monolith** chặt chẽ trên nền tảng **Laravel 11, MySQL 8.0 (InnoDB) và React 18 + Inertia.js**. 

### 1.1. Cấu trúc phân tầng (Layered Architecture) trong từng Module:
- **Presentation Layer:**
  - Controllers: `app/Modules/Bounties/Presentation/Http/Controllers/`
  - Form Requests: `app/Modules/Bounties/Presentation/Http/Requests/`
  - Routes: `app/Modules/Bounties/routes.php`
  - Inertia Views: `resources/js/Pages/Wishlist/`, `resources/js/Pages/Wallet/`, `resources/js/Pages/Admin/Bounties/`
- **Application Layer:**
  - Actions: `app/Modules/Bounties/Application/Actions/` (Thực thi các use cases ghi dữ liệu)
  - Queries: `app/Modules/Bounties/Application/Queries/` (Tối ưu hóa truy vấn đọc dữ liệu)
- **Domain Layer:**
  - Evaluators & Services: `BountyRuleEvaluator.php`, `ReputationEvaluator.php`
  - Enums: `TrangThaiYeuCauTaiLieu.php`, `LoaiGiaoDichDiem.php`, `TrangThaiTaiLieu.php`, `VaiTroNguoiDung.php`
- **Infrastructure Layer:**
  - Persistence: `WishlistBountyRepository.php`, Eloquent Models (`YeuCauTaiLieu`, `DapUngYeuCauTaiLieu`, `ViDiem`, `GiaoDichDiem`)
  - Notifications & Events: `BountyNotificationListener.php`, Domain Events, Laravel Notifications.

---

## 2. KHẢO SÁT CƠ SỞ DỮ LIỆU HIỆN CÓ

### 2.1. Đánh giá tính tuân thủ quy tắc Tiếng Việt không dấu
Cơ sở dữ liệu của dự án AGU Academic Hub hiện tại **đã tuân thủ xuất sắc quy tắc đặt tên bảng và cột bằng tiếng Việt không dấu (snake_case)** theo đúng triết lý của dự án:
- `nguoi_dung`: Lưu trữ tài khoản người dùng (`ma_nguoi_dung`, `ho_ten`, `email`, `mssv`, `vai_tro`, `diem_uy_tin`,...).
- `mon_hoc`: Môn học học thuật (`ma_mon_hoc`, `ma_hoc_phan`, `ten_mon_hoc`, `ma_khoa`,...).
- `tai_lieu`: Học liệu số (`ma_tai_lieu`, `ma_mon_hoc`, `ma_nguoi_dang`, `tieu_de`, `duong_dan_tep`, `dinh_dang_tep`, `dung_luong_kb`, `ma_bam_file`, `phan_loai`, `trang_thai`,...).
- `vi_diem`: Ví điểm cá nhân (`ma_vi`, `ma_nguoi_dung`, `diem_kha_dung`, `diem_tam_giu`). Có ràng buộc `CHECK (diem_kha_dung >= 0)` và `CHECK (diem_tam_giu >= 0)`.
- `giao_dich_diem`: Sổ cái ghi nhận biến động điểm (`ma_giao_dich`, `ma_vi`, `loai_giao_dich`, `so_diem`, `thay_doi_kha_dung`, `thay_doi_tam_giu`, `loai_tham_chieu`, `ma_tham_chieu`, `khoa_chong_trung`, `thoi_gian_tao`). Cột `khoa_chong_trung` (UNIQUE VARCHAR(100)) đóng vai trò chốt chặn Idempotency cốt lõi.
- `yeu_cau_tai_lieu`: Bảng quản lý yêu cầu Bounty (`ma_yeu_cau`, `ma_mon_hoc`, `ma_nguoi_tao`, `tieu_de`, `mo_ta`, `ten_giang_vien`, `phan_loai`, `diem_thuong`, `trang_thai`, `ma_tai_lieu_dap_ung`, `ma_dap_ung_duoc_chon`, `thoi_gian_nguoi_yeu_cau_xac_nhan`, `nguoi_xac_nhan`, `thoi_gian_xac_nhan`, `created_at`, `updated_at`, `ngay_xoa_tam`).
- `dap_ung_yeu_cau_tai_lieu`: Bảng bài nộp phản hồi / đóng góp (`ma_dap_ung`, `ma_yeu_cau`, `ma_tai_lieu`, `ma_nguoi_dong_gop`, `trang_thai`, `ghi_chu_dong_gop`, `ly_do_tu_choi_giai_ngan`, `nguoi_duyet`, `thoi_gian_duyet`, `created_at`, `updated_at`). Có UNIQUE(`ma_yeu_cau`, `ma_tai_lieu`).

### 2.2. Nhận diện mâu thuẫn & Phương án tương thích dữ liệu
Theo yêu cầu tại Phần 2.3 & Phần 7 của bài toán:
1. **Bảng đóng góp tài liệu**: Prompt đề xuất tên gợi ý `lan_dong_gop_tai_lieu`. Tuy nhiên, codebase hiện tại đã có bảng hoạt động ổn định là `dap_ung_yeu_cau_tai_lieu` (với khóa chính `ma_dap_ung`). Theo đúng nguyên tắc *"Không tạo bảng trùng chức năng, Không tự ý đổi tên bảng hoặc cột đang được sử dụng"*, hệ thống sẽ **giữ nguyên bảng `dap_ung_yeu_cau_tai_lieu`**, không tạo thêm bảng song song gây phân mảnh dữ liệu.
2. **Cột người tạo yêu cầu**: Bảng hiện tại sử dụng `ma_nguoi_tao`. Hệ thống giữ nguyên tên cột này để tránh làm vỡ các module `Documents`, `Administration` và các test hiện có.
3. **Cột cần bổ sung trong `dap_ung_yeu_cau_tai_lieu`**: 
   - `ly_do_nguoi_tao_tu_choi` (TEXT NULL): Lưu lý do khi người tạo yêu cầu từ chối một bài đóng góp (Trường hợp 2, Phần 4.3).

### 2.3. Các bảng mới cần tạo thêm (100% Tiếng Việt không dấu)
Để đáp ứng Phần 6 (Quy trình chỉnh sửa sau khi Bounty hoàn thành):
1. **`yeu_cau_chinh_sua_dong_gop`**:
   - `ma_yeu_cau_chinh_sua`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
   - `ma_yeu_cau`: BIGINT UNSIGNED NOT NULL (FK -> `yeu_cau_tai_lieu.ma_yeu_cau`)
   - `ma_dap_ung`: BIGINT UNSIGNED NOT NULL (FK -> `dap_ung_yeu_cau_tai_lieu.ma_dap_ung`)
   - `ma_nguoi_yeu_cau`: BIGINT UNSIGNED NOT NULL (FK -> `nguoi_dung.ma_nguoi_dung`)
   - `ma_nguoi_dong_gop`: BIGINT UNSIGNED NOT NULL (FK -> `nguoi_dung.ma_nguoi_dung`)
   - `ly_do_chinh_sua`: TEXT NOT NULL
   - `ly_do_tu_choi`: TEXT NULL (lý do người đóng góp từ chối chỉnh sửa)
   - `trang_thai`: ENUM('CHO_XU_LY', 'DANG_XU_LY', 'TU_CHOI', 'HOAN_THANH', 'DA_HUY') DEFAULT 'CHO_XU_LY'
   - `thoi_gian_phan_hoi`: DATETIME NULL
   - `thoi_gian_hoan_tat`: DATETIME NULL
   - `ngay_tao`: TIMESTAMP NULL
   - `ngay_cap_nhat`: TIMESTAMP NULL
   - Ràng buộc: `INDEX (ma_yeu_cau, trang_thai)`, `INDEX (ma_nguoi_dong_gop, trang_thai)`.

2. **`lan_nop_tai_lieu_thay_the`**:
   - `ma_lan_nop_thay_the`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
   - `ma_yeu_cau_chinh_sua`: BIGINT UNSIGNED NOT NULL (FK -> `yeu_cau_chinh_sua_dong_gop.ma_yeu_cau_chinh_sua`)
   - `ma_tai_lieu`: BIGINT UNSIGNED NOT NULL (FK -> `tai_lieu.ma_tai_lieu`)
   - `ma_nguoi_nop`: BIGINT UNSIGNED NOT NULL (FK -> `nguoi_dung.ma_nguoi_dung`)
   - `ghi_chu`: TEXT NULL
   - `la_phien_ban_duoc_chap_nhan`: TINYINT(1) NOT NULL DEFAULT 0
   - `ngay_tao`: TIMESTAMP NULL
   - `ngay_cap_nhat`: TIMESTAMP NULL
   - Ràng buộc: `INDEX (ma_yeu_cau_chinh_sua, la_phien_ban_duoc_chap_nhan)`.

---

## 3. PHÂN TÍCH MA TRẬN TÁC ĐỘNG VÀ NGHIỆP VỤ

### 3.1. Luồng 1: Tạo Bounty và Đóng Băng Điểm (Phần 4.1)
- **Hiện trạng**: Đã có `CreateBountyRequestAction` và `ReputationEvaluator::dongBangDiemBounty`.
- **Đánh giá**: Đã bọc trong `DB::transaction`, ghi sổ cái `GiaoDichDiem` với `loai_giao_dich = TAM_GIU`, chặn số dư âm qua DB CHECK constraint và validation.
- **Tác động cần bổ sung**: Đảm bảo phân trang danh sách hỗ trợ tìm kiếm theo tiêu đề (`q` hoặc `search`), lọc theo trạng thái, môn học, phân loại và sắp xếp.

### 3.2. Luồng 2: Đóng Góp Tài Liệu (Phần 4.2)
- **Hiện trạng**: Đã có `FulfillBountyRequestAction` hỗ trợ cả 2 phương thức:
  - Phương thức A: Chọn tài liệu sẵn có trong kho cá nhân (phải thuộc cùng môn học, chính chủ, trạng thái `DA_DUYET`).
  - Phương thức B: Tải tệp trực tiếp từ máy tính (kiểm tra MIME, extension, dung lượng, rate limit 10 lượt/ngày, băm nhị phân SHA-256 chống trùng lặp môn học, tạo tài liệu ở trạng thái `CHO_DUYET`).
- **Đánh giá**: Rất an toàn, đạt tiêu chuẩn kiểm duyệt của trường.

### 3.3. Luồng 3: Người Tạo Xác Nhận / Từ Chối Tài Liệu (Phần 4.3)
- **Hiện trạng**:
  - Đã có `ConfirmBountyFulfillmentAction`: Người tạo xác nhận -> chuyển `dap_ung` sang `NGUOI_YEU_CAU_XAC_NHAN`, chuyển Bounty sang `CHO_GIAI_NGAN`. Điểm **chưa giải ngân**, bắn thông báo tới Admin/Mod (`BountyPayoutApprovalRequiredNotification`).
  - **KHOẢNG TRỐNG (GAP)**: Chưa có hành động Người tạo **Từ Chối** tài liệu đóng góp kèm lý do (`RejectBountyFulfillmentAction`).
- **Phương án bổ sung**:
  - Xây dựng Action `RejectBountyFulfillmentAction` và FormRequest `RejectBountyFulfillmentRequest`.
  - Cập nhật trạng thái `dap_ung` sang `TU_CHOI`, lưu `ly_do_nguoi_tao_tu_choi`.
  - Bounty vẫn giữ nguyên trạng thái `MO` và điểm vẫn đóng băng an toàn trong `diem_tam_giu` để chờ đóng góp khác.
  - Bắn thông báo cho người đóng góp về lý do bị từ chối.

### 3.4. Luồng 4: Admin/Moderator Duyệt Giải Ngân (Phần 4.4 & Phần 8)
- **Hiện trạng**:
  - Hàng đợi Admin: `AdminBountyPayoutController`, `ApproveBountyPayoutAction`, `RejectBountyPayoutAction`.
  - Quyết định DEC-01 đã thống nhất: Admin hoặc Moderator là 2 vai trò thay thế ở cùng một bước duyệt, duyệt tại `/quan-tri/yeu-cau-tai-lieu/cho-giai-ngan`.
- **Gia cố bảo vệ chống Race Condition / Double Payout (Phần 8)**:
  - Cần đảm bảo trong `ApproveBountyPayoutAction`: Thực hiện `lockForUpdate()` trên cả `YeuCauTaiLieu`, `DapUngYeuCauTaiLieu`, và `ViDiem` của cả 2 bên.
  - Kiểm tra trạng thái nghiêm ngặt: Nếu yêu cầu không còn ở trạng thái `CHO_GIAI_NGAN` (do đã có Admin khác duyệt trước), lập tức dừng giao dịch và thông báo xung đột.
  - Sổ cái `GiaoDichDiem` có ràng buộc khóa chống trùng `khoa_chong_trung` duy nhất (`giai_ngan_yc_{id}_nd_{id}_nguoi_tao` và `_nguoi_nhan`), triệt tiêu 100% rủi ro cộng điểm 2 lần ở cấp độ CSDL InnoDB.

### 3.5. Luồng 5: Hủy Bounty & Hoàn Điểm (Phần 4.5)
- **Hiện trạng**: Đã có `CancelBountyRequestAction`.
- **Đánh giá**: Hoàn điểm trong transaction, kiểm tra Bounty đang `MO`, trả `diem_tam_giu` về `diem_kha_dung`, ghi giao dịch `HOAN_TRA`, chuyển trạng thái `DA_HUY`, soft-delete và phát sự kiện `BountyCancelledEvent`.

### 3.6. Luồng 6: Quản Lý Bounty Của Tôi & Lịch Sử (Phần 9.4, 9.5 & Phần 10)
- **KHOẢNG TRỐNG (GAP)**:
  - Hiện tại trang `Wishlist/Index.jsx` mới chỉ hiển thị danh sách chung toàn trường.
  - Sinh viên chưa có giao diện phân tab rõ ràng:
    - Tab 1: "Tất cả yêu cầu" (Khám phá chung).
    - Tab 2: "Yêu cầu của tôi" (Bounties do tôi tạo, nhóm theo: Đang mở, Chờ giải ngân, Đã hoàn thành, Đã hủy).
    - Tab 3: "Đóng góp của tôi" (Các bài tôi đã nộp và trạng thái nghiệm thu/giải ngân).
- **Phương án bổ sung**:
  - Bổ sung scope / filter hoặc API endpoints:
    - `GET /yeu-cau-tai-lieu` hỗ trợ `tab=all|my_requests|my_contributions` (hoặc endpoint chuyên biệt tương thích Inertia).
  - Nâng cấp giao diện `Wishlist/Index.jsx` với hệ thống Tabs trực quan.

### 3.7. Luồng 7: Quy Trình Chỉnh Sửa Sau Khi Bounty Hoàn Thành (Phần 6)
- **KHOẢNG TRỐNG LỚN NHẤT**: Toàn bộ nghiệp vụ Phần 6 chưa có trong codebase.
- **Quy tắc bất di bất dịch**:
  - Bounty **luôn giữ nguyên `DA_HOAN_THANH`**, không mở lại Bounty.
  - Tuyệt đối **không tạo giao dịch ví điểm**, không trừ điểm B, không hoàn điểm A.
  - Không can thiệp bởi Admin/Mod (đây là thỏa thuận trực tiếp giữa A và B).
  - Không ghi đè file cũ; mọi lần nộp tài liệu thay thế của B đều tạo bản ghi mới (`lan_nop_tai_lieu_thay_the`) và liên kết tệp tin mới (`TaiLieu`).
  - Chỉ có tối đa **01 yêu cầu chỉnh sửa đang hoạt động** (`CHO_XU_LY` hoặc `DANG_XU_LY`) cho một lần đóng góp đã duyệt.
- **Phương án bổ sung**:
  - Tạo Migration 2 bảng `yeu_cau_chinh_sua_dong_gop` và `lan_nop_tai_lieu_thay_the`.
  - Tạo Models: `YeuCauChinhSuaDongGop`, `LanNopTaiLieuThayThe`.
  - Tạo Actions:
    - `CreateCorrectionRequestAction`
    - `RespondCorrectionRequestAction` (Chấp nhận / Từ chối)
    - `SubmitReplacementDocumentAction` (B nộp file mới)
    - `ConfirmCorrectionCompletionAction` (A nghiệm thu phiên bản hoàn chỉnh)
  - Tích hợp giao diện trong `Wishlist/Show.jsx` khi Bounty ở trạng thái `DA_HOAN_THANH`.

---

## 4. KẾT LUẬN VÀ LỘ TRÌNH THỰC HIỆN TIẾP THEO

- **Giai đoạn 1 (Khảo sát)**: Đã hoàn tất 100%. Xác định chính xác các thành phần đã có và các khoảng trống cần bổ sung.
- **Giai đoạn 2 (Phân tích tác động)**: Đã hoàn tất trong tài liệu này. Không có mâu thuẫn phá vỡ kiến trúc; toàn bộ schema mới đều tuân thủ 100% tiếng Việt không dấu.
- **Tiến sang Giai đoạn 3 (Thiết kế)**: Lập tài liệu `docs/student-alumni-bounty-design.md` chi tiết với ERD, State Diagram, API Contract trước khi bước vào triển khai mã nguồn.

