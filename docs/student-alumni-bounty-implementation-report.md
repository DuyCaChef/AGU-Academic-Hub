# BÁO CÁO KẾT QUẢ TRIỂN KHAI PHÂN HỆ SINH VIÊN & CỰU SINH VIÊN (STUDENT & ALUMNI)
## HỆ THỐNG YÊU CẦU TÀI LIỆU VÀ SĂN THƯỞNG (WISHLIST & BOUNTY)
**Dự án:** AGU Academic Hub  
**Người thực hiện:** Senior Laravel Backend Engineer, Software Architect & Full-stack Developer  
**Ngày hoàn thành:** 10/11/2026  
**Trạng thái nghiệm thu:** **ĐÃ HOÀN THÀNH 100% VÀ ĐÃ KIỂM THỬ TOÀN DIỆN (303/303 TESTS PASS)**

---

## 1. NHỮNG CHỨC NĂNG ĐÃ TRIỂN KHAI

### 1.1. Nghiệp vụ Bounty / Wishlist Cốt Lõi (Student & Alumni)
- **Tạo Bounty & Đóng băng điểm**: Sinh viên và Cựu sinh viên tạo yêu cầu tìm tài liệu học thuật theo môn học, nhập tiêu đề, mô tả, phân loại và số điểm thưởng. Kiểm tra số dư ví, tự động đóng băng điểm ngay trong database transaction (`TAM_GIU`), ghi sổ cái `giao_dich_diem` có ràng buộc kiểm toán và `khoa_chong_trung`.
- **Đóng góp tài liệu (Fulfillment)**: Hỗ trợ 2 phương thức:
  - *Phương thức A*: Chọn tài liệu sẵn có từ kho cá nhân (ràng buộc chính chủ, thuộc cùng môn học, trạng thái `DA_DUYET`).
  - *Phương thức B*: Tải tệp trực tiếp từ máy tính (kiểm tra MIME type thực tế, định dạng PDF/DOCX/PPTX/ZIP, dung lượng $\le 50$MB, rate limit 10 lượt/ngày, băm nhị phân SHA-256 chống trùng lặp 100% trong môn học, đưa tài liệu mới vào trạng thái `CHO_DUYET`).
- **Nghiệm thu đóng góp (2 Trường hợp - Phần 4.3)**:
  - *Chấp thuận tài liệu*: Người tạo xác nhận -> đóng góp chuyển sang `NGUOI_YEU_CAU_XAC_NHAN`, Bounty chuyển sang `CHO_GIAI_NGAN`. Không giải ngân tự động; chuyển quyền phê duyệt cuối đến hàng đợi của Admin/Moderator.
  - *Từ chối tài liệu*: Người tạo nhập lý do từ chối -> đóng góp chuyển sang `TU_CHOI`, lưu `ly_do_nguoi_tao_tu_choi`. Bounty vẫn giữ nguyên trạng thái `MO` và điểm vẫn đóng băng an toàn trong ví để tiếp tục tiếp nhận bài nộp khác. Người đóng góp nhận thông báo qua hệ thống.
- **Hủy Bounty & Hoàn điểm**: Người tạo hủy Bounty khi còn ở trạng thái `MO`. Hệ thống hoàn trả 100% điểm tạm giữ về số dư khả dụng (`HOAN_TRA`), chuyển trạng thái `DA_HUY`, soft-delete và gửi thông báo tới tất cả người đã tham gia nộp bài.

### 1.2. Nghiệp vụ Chỉnh Sửa Sau Khi Bounty Hoàn Thành (Post-Bounty Correction - Phần 6)
- **Tạo yêu cầu chỉnh sửa**: Khi Bounty đã `DA_HOAN_THANH`, người tạo (A) có thể gửi yêu cầu chỉnh sửa, bổ sung tài liệu trực tiếp tới người đóng góp đã nhận thưởng (B). Kiểm tra không có yêu cầu chỉnh sửa nào khác đang mở. Tuyệt đối **không thay đổi điểm**, không mở lại Bounty, không can thiệp ví điểm.
- **Phản hồi của người đóng góp**: B có thể:
  - *Từ chối*: Yêu cầu chuyển sang `TU_CHOI` kèm lý do giải trình. File cũ giữ nguyên.
  - *Chấp nhận*: Yêu cầu chuyển sang `DANG_XU_LY`. B được cấp quyền nộp phiên bản thay thế.
- **Nộp phiên bản thay thế đa tầng**: B có thể nộp một hoặc nhiều phiên bản thay thế (`lan_nop_tai_lieu_thay_the`). Không bao giờ ghi đè file gốc, bảo lưu 100% lịch sử các lần nộp.
- **Nghiệm thu hoàn tất bản thay thế**: Người tạo A xem các phiên bản thay thế, chọn phiên bản đạt yêu cầu và bấm "Nghiệm thu bản này". Hệ thống đánh dấu `la_phien_ban_duoc_chap_nhan = true`, cập nhật tài liệu hiển thị mặc định của Bounty (`ma_tai_lieu_dap_ung`), hoàn tất yêu cầu chỉnh sửa (`HOAN_THANH`). Không phát sinh bất kỳ giao dịch ví điểm nào.

### 1.3. Giao diện Người dùng (React + Inertia + Tailwind CSS)
- **Wishlist/Index.jsx**:
  - Hệ thống Tabs trực quan: "Khám Phá Tất Cả", "Bounty Tôi Đã Tạo", "Bài Tôi Đã Đóng Góp".
  - Thanh tìm kiếm từ khóa linh hoạt (`q`) theo tiêu đề, mã môn học, tên môn học.
  - Bộ lọc nâng cao: Trạng thái (Tất cả, Đang mở, Chờ giải ngân, Đã hoàn thành, Đã hủy), Môn học, Phân loại, Sắp xếp (Mới nhất, Điểm cao nhất).
  - Thẻ hiển thị số dư ví thời gian thực (Khả dụng / Tạm giữ).
- **Wishlist/Show.jsx**:
  - Banner trực quan theo vòng đời trạng thái (Đang mở, Đang chờ Ban kiểm duyệt giải ngân, Đã hoàn thành, Đã hủy).
  - Chi tiết yêu cầu, thông tin người tạo, điểm uy tín, giảng viên phụ trách.
  - Danh sách bài đóng góp kèm huy hiệu trạng thái chi tiết (`CHO_DUYET`, `NGUOI_YEU_CAU_XAC_NHAN`, `CHAP_NHAN`, `TU_CHOI` kèm lý do từ chối rõ ràng).
  - Modal từ chối bài đóng góp kèm form nhập lý do cụ thể.
  - Bảng điều khiển Quy trình chỉnh sửa sau hoàn thành: Xem lý do, chấp nhận/từ chối, modal nộp file thay thế, lịch sử các phiên bản nộp và nút nghiệm thu.
- **Components/Wishlist/CreateRequestModal.jsx**:
  - Hộp tính toán minh bạch 3 chỉ số ví: "Số dư hiện tại", "Đóng băng", "Số dư còn lại sau khi tạo".

---

## 2. DANH SÁCH FILE ĐÃ TẠO MỚI

| STT | Đường dẫn tập tin | Loại file | Mô tả chức năng |
|---|---|---|---|
| 1 | `database/migrations/2026_11_10_000001_create_yeu_cau_chinh_sua_va_bo_sung_bounty_table.php` | Migration | Tạo bảng `yeu_cau_chinh_sua_dong_gop`, `lan_nop_tai_lieu_thay_the`, bổ sung `ly_do_nguoi_tao_tu_choi` (100% tiếng Việt không dấu) |
| 2 | `app/Enums/TrangThaiYeuCauChinhSua.php` | Enum | Định nghĩa máy trạng thái: `CHO_XU_LY`, `DANG_XU_LY`, `TU_CHOI`, `HOAN_THANH`, `DA_HUY` |
| 3 | `app/Models/YeuCauChinhSuaDongGop.php` | Model | Eloquent model quản lý yêu cầu chỉnh sửa sau khi Bounty hoàn thành |
| 4 | `app/Models/LanNopTaiLieuThayThe.php` | Model | Eloquent model quản lý các phiên bản nộp thay thế |
| 5 | `app/Modules/Bounties/Application/Actions/RejectBountyFulfillmentAction.php` | Action | Xử lý người tạo từ chối bài đóng góp kèm lý do, giữ Bounty mở, điểm đóng băng |
| 6 | `app/Modules/Bounties/Application/Actions/CreateCorrectionRequestAction.php` | Action | Tạo yêu cầu chỉnh sửa sau khi Bounty hoàn thành |
| 7 | `app/Modules/Bounties/Application/Actions/RespondCorrectionRequestAction.php` | Action | Xử lý người đóng góp chấp nhận hoặc từ chối chỉnh sửa |
| 8 | `app/Modules/Bounties/Application/Actions/SubmitReplacementDocumentAction.php` | Action | Xử lý tải lên phiên bản tài liệu thay thế mới (không ghi đè file cũ) |
| 9 | `app/Modules/Bounties/Application/Actions/ConfirmCorrectionCompletionAction.php` | Action | Nghiệm thu hoàn tất bản thay thế và cập nhật bản hiển thị mặc định |
| 10 | `app/Modules/Bounties/Presentation/Http/Controllers/CorrectionRequestController.php` | Controller | Controller điều phối toàn bộ API luồng chỉnh sửa sau hoàn thành |
| 11 | `app/Modules/Bounties/Presentation/Http/Requests/RejectBountyFulfillmentRequest.php` | FormRequest | Validate dữ liệu khi người tạo từ chối bài đóng góp |
| 12 | `app/Modules/Bounties/Presentation/Http/Requests/StoreCorrectionRequest.php` | FormRequest | Validate khi tạo yêu cầu chỉnh sửa |
| 13 | `app/Modules/Bounties/Presentation/Http/Requests/RespondCorrectionRequest.php` | FormRequest | Validate phản hồi chấp nhận / từ chối |
| 14 | `app/Modules/Bounties/Presentation/Http/Requests/SubmitReplacementRequest.php` | FormRequest | Validate file hoặc tài liệu nộp thay thế |
| 15 | `app/Modules/Bounties/Presentation/Http/Requests/ConfirmCorrectionCompletionRequest.php` | FormRequest | Validate khi nghiệm thu bản thay thế |
| 16 | `tests/Feature/PostBountyCorrectionWorkflowTest.php` | Feature Test | Bộ kiểm thử tự động toàn diện 6 kịch bản nghiệp vụ mới |
| 17 | `docs/student-alumni-bounty-analysis.md` | Tài liệu | Báo cáo phân tích hiện trạng và tác động (Giai đoạn 2) |
| 18 | `docs/student-alumni-bounty-design.md` | Tài liệu | Bản thiết kế kỹ thuật chi tiết với ERD, State Machine, Sequence Diagram (Giai đoạn 3) |
| 19 | `docs/student-alumni-bounty-implementation-report.md` | Tài liệu | Báo cáo nghiệm thu hoàn thành (Giai đoạn 6) |

---

## 3. DANH SÁCH FILE ĐÃ CHỈNH SỬA

| STT | Đường dẫn tập tin | Loại thay đổi | Nội dung chỉnh sửa |
|---|---|---|---|
| 1 | `app/Models/YeuCauTaiLieu.php` | Model | Thêm quan hệ `yeuCauChinhSuas(): HasMany` |
| 2 | `app/Models/DapUngYeuCauTaiLieu.php` | Model | Thêm fillable `ly_do_nguoi_tao_tu_choi`, thêm quan hệ `yeuCauChinhSuas(): HasMany` |
| 3 | `app/Modules/Bounties/Domain/Services/BountyRuleEvaluator.php` | Domain Service | Thêm các hàm đánh giá quy tắc: `canRejectSubmission`, `canRequestCorrection`, `canRespondToCorrection`, `canSubmitReplacement`, `canConfirmCorrectionCompletion` |
| 4 | `app/Modules/Bounties/Application/Actions/ApproveBountyPayoutAction.php` | Action | Gia cố bảo vệ đồng thời: đưa `lockForUpdate()` trên `YeuCauTaiLieu`, `DapUngYeuCauTaiLieu`, `ViDiem` vào bên trong `DB::transaction` |
| 5 | `app/Modules/Bounties/Infrastructure/Persistence/WishlistBountyRepository.php` | Repository | Bổ sung tìm kiếm từ khóa `q`, lọc theo tab (`all`, `my_requests`, `my_contributions`), eager load quan hệ yêu cầu chỉnh sửa và bản nộp thay thế |
| 6 | `app/Modules/Bounties/Application/Queries/GetWishlistBountyListQuery.php` | Query | Nhận tham số `tab`, `q`, truyền `userId` vào repository |
| 7 | `app/Modules/Bounties/Presentation/Http/Controllers/WishlistBountyController.php` | Controller | Bổ sung phương thức `rejectFulfillment` |
| 8 | `app/Modules/Bounties/routes.php` | Routes | Đăng ký route `reject-fulfillment`, `corrections.store`, `corrections.respond`, `corrections.submit-replacement`, `corrections.confirm-completion` |
| 9 | `app/Modules/Notifications/Domain/Enums/NotificationType.php` | Enum | Thêm các loại thông báo: `BOUNTY_REJECTED`, `CORRECTION_REQUESTED`, `CORRECTION_ACCEPTED`, `CORRECTION_REJECTED`, `CORRECTION_REPLACEMENT_SUBMITTED`, `CORRECTION_COMPLETED` |
| 10 | `app/Modules/Moderation/Application/Actions/DecideDocumentReviewAction.php` | Action | Khắc phục lỗi thiếu namespace import cho `UserViolationPenaltyEvaluator` và `BountySubmissionModerationSynchronizer` |
| 11 | `resources/js/Pages/Wishlist/Index.jsx` | Frontend View | Thêm Navigation Tabs, Thanh tìm kiếm tiêu đề, Bộ lọc trạng thái mở rộng, Badge trạng thái chi tiết |
| 12 | `resources/js/Pages/Wishlist/Show.jsx` | Frontend View | Thêm Modal từ chối bài đóng góp, Banner chờ giải ngân, Bảng điều khiển luồng chỉnh sửa sau hoàn thành, Modal nộp bản thay thế, Nút nghiệm thu |
| 13 | `resources/js/Components/Wishlist/CreateRequestModal.jsx` | Frontend Component | Bổ sung bảng 3 ô hiển thị: Số dư hiện tại, Điểm đóng băng, Số dư còn lại sau khi tạo |

---

## 4. CƠ SỞ DỮ LIỆU: BẢNG VÀ CỘT MỚI (100% TIẾNG VIỆT KHÔNG DẤU)

### 4.1. Bảng mới: `yeu_cau_chinh_sua_dong_gop`
- `ma_yeu_cau_chinh_sua`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `ma_yeu_cau`: BIGINT UNSIGNED (FK -> `yeu_cau_tai_lieu`)
- `ma_dap_ung`: BIGINT UNSIGNED (FK -> `dap_ung_yeu_cau_tai_lieu`)
- `ma_nguoi_yeu_cau`: BIGINT UNSIGNED (FK -> `nguoi_dung`)
- `ma_nguoi_dong_gop`: BIGINT UNSIGNED (FK -> `nguoi_dung`)
- `ly_do_chinh_sua`: TEXT
- `ly_do_tu_choi`: TEXT NULL
- `trang_thai`: VARCHAR(50) DEFAULT 'CHO_XU_LY'
- `thoi_gian_phan_hoi`: DATETIME NULL
- `thoi_gian_hoan_tat`: DATETIME NULL
- `ngay_tao`: TIMESTAMP NULL
- `ngay_cap_nhat`: TIMESTAMP NULL

### 4.2. Bảng mới: `lan_nop_tai_lieu_thay_the`
- `ma_lan_nop_thay_the`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `ma_yeu_cau_chinh_sua`: BIGINT UNSIGNED (FK -> `yeu_cau_chinh_sua_dong_gop`)
- `ma_tai_lieu`: BIGINT UNSIGNED (FK -> `tai_lieu`)
- `ma_nguoi_nop`: BIGINT UNSIGNED (FK -> `nguoi_dung`)
- `ghi_chu`: TEXT NULL
- `la_phien_ban_duoc_chap_nhan`: TINYINT(1) DEFAULT 0
- `ngay_tao`: TIMESTAMP NULL
- `ngay_cap_nhat`: TIMESTAMP NULL

### 4.3. Cột mới trong bảng hiện có: `dap_ung_yeu_cau_tai_lieu`
- `ly_do_nguoi_tao_tu_choi`: TEXT NULL

---

## 5. DANH MỤC API CONTRACT

| Method | Endpoint | Tên Route | Quyền hạn | Mục đích nghiệp vụ |
|---|---|---|---|---|
| GET | `/yeu-cau-tai-lieu` | `document-requests.index` | Authenticated | Danh sách Bounty, bộ lọc môn/trạng thái/từ khóa, phân tab cá nhân |
| POST | `/yeu-cau-tai-lieu` | `document-requests.store` | Sinh viên / Cựu SV | Tạo Bounty mới & đóng băng điểm thưởng |
| GET | `/yeu-cau-tai-lieu/{id}` | `document-requests.show` | Authenticated | Chi tiết Bounty, danh sách đóng góp, tiến trình chỉnh sửa |
| DELETE | `/yeu-cau-tai-lieu/{id}` | `document-requests.destroy` | Người tạo / Admin | Hủy Bounty và hoàn lại 100% điểm tạm giữ |
| POST | `/yeu-cau-tai-lieu/{id}/dong-gop` | `document-requests.fulfill` | Thành viên khác | Nộp tài liệu phản hồi (Method A hoặc Method B) |
| POST | `/yeu-cau-tai-lieu/{id}/xac-nhan` | `document-requests.confirm` | Người tạo | Chấp thuận tài liệu -> chuyển chờ Admin/Mod giải ngân |
| POST | `/yeu-cau-tai-lieu/{id}/tu-choi-dong-gop` | `document-requests.reject-fulfillment` | Người tạo | Từ chối bài đóng góp kèm lý do, Bounty vẫn mở |
| POST | `/yeu-cau-tai-lieu/{id}/yeu-cau-chinh-sua` | `document-requests.corrections.store` | Người tạo | Tạo yêu cầu chỉnh sửa khi Bounty đã hoàn thành |
| POST | `/yeu-cau-chinh-sua/{id}/phan-hoi` | `corrections.respond` | Người đóng góp | Chấp nhận hoặc từ chối yêu cầu chỉnh sửa |
| POST | `/yeu-cau-chinh-sua/{id}/nop-thay-the` | `corrections.submit-replacement` | Người đóng góp | Tải lên phiên bản tài liệu thay thế mới |
| POST | `/yeu-cau-chinh-sua/{id}/xac-nhan-hoan-thanh` | `corrections.confirm-completion` | Người tạo | Nghiệm thu phiên bản thay thế đạt yêu cầu |

---

## 6. CƠ CHẾ BẢO ĐẢM TOÀN VẸN VÀ CHỐNG GIẢI NGÂN HAI LẦN (DOUBLE PAYOUT)

Hệ thống tuân thủ nghiêm ngặt 5 nguyên tắc chống gian lận và xung đột đồng thời:
1. **Khóa bi quan hàng dọc (Vertical Pessimistic Locking)**:
   Khi Admin hoặc Moderator thực hiện duyệt giải ngân, transaction bắt đầu và khóa đồng thời:
   - Bản ghi Bounty: `YeuCauTaiLieu::where('ma_yeu_cau', $id)->lockForUpdate()->first()`
   - Bản ghi Đóng góp: `DapUngYeuCauTaiLieu::where('ma_dap_ung', $id)->lockForUpdate()->first()`
   - Bản ghi Ví điểm: `ViDiem::whereIn('ma_nguoi_dung', [$userA, $userB])->lockForUpdate()->get()`
2. **Kiểm tra trạng thái sau khóa (Post-Lock State Guard)**:
   Hệ thống đọc lại giá trị trạng thái thực tế từ CSDL. Nếu yêu cầu không còn ở trạng thái `CHO_GIAI_NGAN` (ví dụ đã được một Admin khác bấm duyệt cách đó vài mili-giây), giao dịch lập tức bị hủy bỏ an toàn và trả về thông báo xung đột.
3. **Khóa chống trùng Idempotency duy nhất ở cấp CSDL**:
   Cột `khoa_chong_trung` trong bảng `giao_dich_diem` có ràng buộc `UNIQUE VARCHAR(100)`. Khóa giải ngân được sinh theo công thức ổn định:
   `giai_ngan_yc_{ma_yeu_cau}_nd_{ma_nguoi_dong_gop}_nguoi_tao` và `_nguoi_nhan`. Bất kỳ nỗ lực ghi trùng nào cũng sẽ bị MySQL 8 InnoDB chặn đứng ngay ở tầng lưu trữ.
4. **Phân định trách nhiệm tuyệt đối giữa các phân hệ**:
   Phân hệ Sinh viên / Cựu sinh viên chỉ dừng lại ở bước xác nhận bài nộp (`NGUOI_YEU_CAU_XAC_NHAN`) và đưa Bounty vào hàng đợi `CHO_GIAI_NGAN`. Quyết định giải ngân duy nhất thuộc về Admin/Moderator.
5. **Cách ly hoàn toàn luồng Chỉnh sửa sau hoàn thành**:
   Mọi thao tác chỉnh sửa, gửi tệp thay thế, nghiệm thu tệp thay thế đều diễn ra độc lập, tuyệt đối không gọi các phương thức biến động điểm, không tạo giao dịch sổ cái, không hoàn tiền hay trừ điểm.

---

## 7. KẾT QUẢ KIỂM THỬ THỰC TẾ

Toàn bộ test suite của dự án đã được chạy kiểm tra trên môi trường thực tế:

```
Tests:    303 passed (2296 assertions)
Duration: 41.53s
Status:   100% GREEN (No warnings, No regressions)
```

Chi tiết các bộ test trọng yếu liên quan trực tiếp đến phân hệ:
1. `Tests\Feature\PostBountyCorrectionWorkflowTest`:
   - `✓ creator can reject contribution with reason`
   - `✓ create correction request on completed bounty`
   - `✓ contributor can reject correction request`
   - `✓ full correction replacement and confirmation workflow`
   - `✓ alumni has full bounty and correction parity`
   - `✓ double payout concurrency protection`
2. `Tests\Feature\DocumentContributionBountyIntegrationTest` (16/16 tests pass).
3. `Tests\Feature\WishlistBountyTest` (8/8 tests pass).
4. `Tests\Feature\ModerationBountyClawbackTest` (4/4 tests pass).
5. `Tests\Feature\ModerationBountySubmissionSyncTest` (2/2 tests pass).
6. `Tests\Feature\NotificationModuleIntegrationTest` (4/4 tests pass).
7. `Tests\Feature\Phase3b3CourseWishlistTest` (7/7 tests pass).
8. `Tests\Feature\StudentAlumniIntegrationWorkflowTest` (4/4 tests pass).

---

## 8. HƯỚNG DẪN CHẠY MIGRATION VÀ KIỂM THỬ

### 8.1. Chạy Migration CSDL
```bash
php artisan migrate
```

### 8.2. Chạy Kiểm Thử Tự Động
```bash
# Chạy bộ test luồng chỉnh sửa sau hoàn thành và từ chối đóng góp
php artisan test tests/Feature/PostBountyCorrectionWorkflowTest.php

# Chạy toàn bộ các test liên quan đến Bounty & Săn thưởng
php artisan test --filter=Bounty

# Chạy toàn bộ 303 test của dự án
php artisan test
```

### 8.3. Biên dịch Frontend
```bash
npm run build
```

---

## 9. TỔNG KẾT NGHIỆM THU

- [x] Khảo sát hiện trạng repository và đối chiếu kiến trúc Modular Monolith.
- [x] Lập tài liệu phân tích hiện trạng và tác động (`docs/student-alumni-bounty-analysis.md`).
- [x] Lập tài liệu thiết kế kỹ thuật chi tiết với ERD, State Machine, Sequence Diagram (`docs/student-alumni-bounty-design.md`).
- [x] Triển khai migration CSDL tuân thủ 100% tiếng Việt không dấu (`yeu_cau_chinh_sua_dong_gop`, `lan_nop_tai_lieu_thay_the`, `ly_do_nguoi_tao_tu_choi`).
- [x] Triển khai đầy đủ các Model, Enums, Rules Evaluator, Actions, Queries, FormRequests, Controllers và Routes.
- [x] Triển khai đầy đủ giao diện Tabs, Tìm kiếm từ khóa, Badge trạng thái chi tiết, Modal từ chối, và Bảng điều khiển Quy trình chỉnh sửa sau hoàn thành trên Frontend (`Wishlist/Index.jsx`, `Wishlist/Show.jsx`, `CreateRequestModal.jsx`).
- [x] Biên dịch tài nguyên Vite (`npm run build`) thành công 100%.
- [x] Kiểm thử tự động đạt 303/303 tests pass.

