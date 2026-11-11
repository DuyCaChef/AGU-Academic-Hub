# BÁO CÁO PHÂN TÍCH KHOẢNG TRỐNG TÍCH HỢP LIÊN PHÂN HỆ
## AGU Academic Hub — Cross-Module Integration Gap Analysis
**Dự án:** AGU Academic Hub (ĐHQG-HCM / Đại học An Giang)  
**Phân hệ đối chiếu:** `Student/Alumni` & `Admin/Moderator`  
**Ngày lập:** 10/11/2026 — Senior Technical Auditor

---

## 1. DANH MỤC TỔNG HỢP CÁC KHOẢNG TRỐNG (GAP INVENTORY)

| Gap ID | Phân loại trạng thái | Mức độ | Tiêu đề tóm tắt | Module chịu trách nhiệm |
| :--- | :--- | :---: | :--- | :--- |
| **GAP-CRIT-01** | `CONFIRMED_GAP` / `IMPLEMENTATION_GAP` | **CRITICAL** | Thiếu lệnh import class trong `DecideDocumentReviewAction` gây sập hàng đợi kiểm tra | `Admin/Moderator` (`Moderation`) |
| **GAP-CRIT-02** | `CONFIRMED_GAP` / `DESIGN_GAP` | **CRITICAL** | "Vùng chết nghiệp vụ" (Deadlock Zone) trong quy trình xóa/gỡ tài liệu của tác giả | `Student/Alumni` & `Moderation` |
| **GAP-HIGH-01** | `CONFIRMED_GAP` / `RESPONSIBILITY_GAP` | **HIGH** | Đặt sai vị trí Controller & Route Quản trị giải ngân Bounty trong module Sinh viên | `Admin/Moderator` ⟷ `Bounties` |
| **GAP-HIGH-02** | `CONFIRMED_GAP` / `ARCHITECTURE_GAP` | **HIGH** | Phân mảnh hệ thống thông báo: Bỏ qua OOP Shared Notification Core làm mất bộ lọc | `Shared/Core` (`Notifications`) |
| **GAP-HIGH-03** | `CONFIRMED_GAP` / `WORKFLOW_GAP` | **HIGH** | Ma sát kiểm duyệt 2 lớp đối với tài liệu nộp Bounty (Method B) làm treo cọc điểm | `Bounties` & `Moderation` |
| **GAP-MED-01** | `DOCUMENTATION_GAP` | **MEDIUM** | Bất đồng bộ URI Route Báo cáo vi phạm giữa tài liệu bàn giao và mã nguồn thực tế | `Documentation` / `Moderation` |
| **GAP-MED-02** | `IMPLEMENTATION_GAP` | **MEDIUM** | Thiếu cơ chế Timeout / SLA tự động hoàn tiền khi yêu cầu Bounty bị bỏ quên | `Bounties` (Student) |
| **GAP-MED-03** | `DESIGN_GAP` / `DOCUMENTATION_GAP` | **MEDIUM** | Lệch pha giữa mô tả tài liệu (Tiền kiểm review) và mã nguồn thực tế (Hậu kiểm review) | `Reviews` & `Moderation` |
| **GAP-LOW-01** | `UI_GAP` | **LOW** | Thiếu ngữ cảnh nhãn Bounty trên màn hình chi tiết thẩm định tài liệu Admin | `Admin/Moderator` (`Moderation`) |
| **GAP-LOW-02** | `TEST_GAP` | **LOW** | Tuyên bố kiểm thử 100% đạt nhưng thực tế nhiều test case đang bị chặn | `Admin/Moderator` (`Audits`) |

---

## 2. PHÂN TÍCH CHI TIẾT TỪNG KHOẢNG TRỐNG

---

### GAP-CRIT-01: Thiếu lệnh import class trong `DecideDocumentReviewAction` gây fatal error
- **Mã Gap:** `GAP-CRIT-01`
- **Mức độ nghiêm trọng:** `CRITICAL`
- **Phân loại trạng thái:** `CONFIRMED_GAP` / `IMPLEMENTATION_GAP`
- **Tài liệu nguồn:**
  - `docs/integration/ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md`: Mục Nghiệp vụ 6 (dòng 275), Nghiệp vụ 15 (dòng 432).
  - `docs/integration/STUDENT_ALUMNI_MODERATION_HANDOFF.md`: Mục 6 (dòng 407).
- **Vị trí tệp mã nguồn:**
  - [`app/Modules/Moderation/Application/Actions/DecideDocumentReviewAction.php:18`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Moderation/Application/Actions/DecideDocumentReviewAction.php#L18)
- **Bằng chứng từ thực tế kiểm thử:**
  - Class `DecideDocumentReviewAction` khai báo:
    ```php
    public function __construct(
        protected UserViolationPenaltyEvaluator $penaltyEvaluator,
        protected BountySubmissionModerationSynchronizer $bountySynchronizer
    ) {}
    ```
    nhưng phía trên đầu tệp **không hề có** dòng:
    ```php
    use App\Modules\Moderation\Domain\Services\UserViolationPenaltyEvaluator;
    ```
    (trong khi class `UserViolationPenaltyEvaluator` thực tế nằm ở namespace `App\Modules\Moderation\Domain\Services`).
  - Khi chạy lệnh kiểm thử tự động:
    ```bash
    php artisan test --filter=ModerationBountyClawbackTest
    ```
    kết quả trả về lập tức:
    ```
    FAILED: Tests\Feature\ModerationBountyClawbackTest > admin deleting document via review queue triggers clawback
    BindingResolutionException: Target class [App\Modules\Moderation\Application\Actions\UserViolationPenaltyEvaluator] does not exist.
    ```
  - Lỗi này cũng làm sập thêm 3 bộ test khác: `ModerationViolationPenaltyTest`, `ModerationBountySubmissionSyncTest`, và test case `uc st 02 and uc st 03` trong `NotificationModuleIntegrationTest`.
- **Hậu quả nếu không xử lý:**
  - Bất kỳ khi nào Kiểm duyệt viên hoặc Quản trị viên truy cập màn hình Thẩm định tài liệu nguy cơ cao (`/quan-tri/tai-lieu/{id}/kiem-tra`) và bấm nút quyết định `HIDE` hoặc `DELETE` (`PUT /quan-tri/tai-lieu/{id}/quyet-dinh`), hệ thống lập tức ném mã lỗi HTTP 500 (Internal Server Error) ra màn hình người dùng. Toàn bộ chuỗi xử lý tự động phía sau (thu hồi giải ngân Bounty, hoàn tiền cho SV A, trừ điểm uy tín tác giả vi phạm, gửi thông báo) bị đứt đoạn hoàn toàn giữa chừng trong Database Transaction.
- **Giải pháp đề xuất:**
  - Bổ sung lệnh import `use App\Modules\Moderation\Domain\Services\UserViolationPenaltyEvaluator;` vào phần header của file `app/Modules/Moderation/Application/Actions/DecideDocumentReviewAction.php`.
- **Module chịu trách nhiệm:** `Admin/Moderator Module` (Nhánh `Moderation`).

---

### GAP-CRIT-02: Vùng chết nghiệp vụ (Deadlock Zone) trong quy trình xóa và gỡ tài liệu của tác giả
- **Mã Gap:** `GAP-CRIT-02`
- **Mức độ nghiêm trọng:** `CRITICAL`
- **Phân loại trạng thái:** `CONFIRMED_GAP` / `DESIGN_GAP`
- **Tài liệu nguồn:**
  - `docs/integration/ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md`: Mục Nghiệp vụ 7 (dòng 304-307) và Checklist dòng 620-622.
  - `docs/integration/STUDENT_ALUMNI_MODERATION_HANDOFF.md`: Mục 2.1 (dòng 43-44).
- **Vị trí tệp mã nguồn:**
  - [`app/Modules/Documents/Domain/Services/DocumentAuthorRemovalEvaluator.php:11-45`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Domain/Services/DocumentAuthorRemovalEvaluator.php#L11-L45)
- **Bằng chứng từ mã nguồn:**
  - Logic tại `DocumentAuthorRemovalEvaluator`:
    ```php
    public function canAuthorSoftDelete(TaiLieu $document, NguoiDung $user): array {
        ...
        if ($document->luot_tai > 0) return ['allowed' => false, 'status' => 422, 'message' => 'Tài liệu đã có lượt tải và không thể tự xóa.'];
        if ($document->created_at?->lt(now()->subDay())) return ['allowed' => false, 'status' => 422, 'message' => 'Chỉ có thể tự xóa tài liệu trong 24 giờ đầu.'];
        return ['allowed' => true, ...];
    }

    public function canRequestRemoval(TaiLieu $document, NguoiDung $user): array {
        ...
        $threshold = config('documents.community_ownership_download_threshold', 10);
        if ($document->luot_tai < $threshold) {
            return ['allowed' => false, 'status' => 422, 'message' => "Tài liệu chỉ thuộc quyền sở hữu cộng đồng từ {$threshold} lượt tải."];
        }
        return ['allowed' => true, ...];
    }
    ```
- **Hậu quả nếu không xử lý:**
  - Phát sinh 2 kịch bản bế tắc hoàn toàn cho tác giả:
    1. **Tài liệu có từ 1 đến 9 lượt tải:** Tác giả không thể tự xóa (vì `luot_tai > 0`), nhưng cũng không được phép gửi đơn xin gỡ (vì `luot_tai < 10`).
    2. **Tài liệu có 0 lượt tải nhưng đã đăng sau 24 giờ:** Tác giả không thể tự xóa (vì quá 24h), và cũng không thể nộp đơn xin gỡ (vì `luot_tai < 10`).
  - Người dùng không có bất kỳ kênh hợp lệ nào để yêu cầu rút tài liệu cá nhân đã đăng. Điều này thúc đẩy sinh viên lách luật bằng cách tạo tài khoản phụ để spam báo cáo vi phạm, làm ô nhiễm hàng đợi kiểm duyệt.
- **Giải pháp đề xuất:**
  - Định nghĩa lại quy tắc phân định hai nhánh rõ ràng:
    - **Nhánh A (Tự xóa mềm không cần duyệt):** Chỉ cho phép khi `luot_tai == 0` và đăng trong vòng 24 giờ đầu.
    - **Nhánh B (Nộp đơn xin gỡ qua Ban kiểm duyệt):** Cho phép nộp đơn đối với **MỌI trường hợp còn lại** (tức là khi không đủ điều kiện tự xóa ở Nhánh A, bao gồm cả tài liệu đã đăng quá 24h hoặc tài liệu đã có từ 1 lượt tải trở lên).
  - Điều chỉnh `DocumentAuthorRemovalEvaluator::canRequestRemoval`: Nếu tài liệu không đủ điều kiện tự xóa mềm thì mặc nhiên được quyền nộp đơn xin gỡ có lý do chính đáng để Kiểm duyệt viên xem xét.
- **Module chịu trách nhiệm:** `Shared Architecture` (`Documents` & `Moderation`).

---

### GAP-HIGH-01: Đặt sai vị trí Controller & Tuyến đường Quản trị Giải ngân Bounty trong Phân hệ Sinh viên
- **Mã Gap:** `GAP-HIGH-01`
- **Mức độ nghiêm trọng:** `HIGH`
- **Phân loại trạng thái:** `CONFIRMED_GAP` / `RESPONSIBILITY_GAP` & `DUPLICATION`
- **Tài liệu nguồn:**
  - `docs/integration/ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md`: Mục Nghiệp vụ 15 (dòng 400-405) khẳng định đây là phân hệ Quản trị.
  - `docs/integration/STUDENT_ALUMNI_MODERATION_HANDOFF.md`: Mục 6 dòng 409-410 ghi nhận phân hệ phụ trách hiện tại là `Bounties / Admin`.
- **Vị trí tệp mã nguồn:**
  - Controller: [`app/Modules/Bounties/Presentation/Http/Controllers/AdminBountyPayoutController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Presentation/Http/Controllers/AdminBountyPayoutController.php)
  - Routes: [`app/Modules/Bounties/routes.php:26-36`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/routes.php#L26-L36)
- **Bằng chứng từ mã nguồn:**
  - File `app/Modules/Bounties/routes.php` chứa đoạn cấu hình:
    ```php
    Route::middleware('role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN')
        ->prefix('quan-tri')
        ->name('admin.')
        ->group(function (): void {
            Route::prefix('yeu-cau-tai-lieu')->name('bounties.')->group(...);
        });
    ```
- **Hậu quả nếu không xử lý:**
  - Vi phạm ranh giới Module Monolith: Phân hệ Bounties (thuộc trách nhiệm của lập trình viên phía Student/Alumni) lại đang chứa controller điều phối màn hình quản trị của Admin/Moderator.
  - Khi lập trình viên Admin/Moderator bảo trì hoặc bổ sung tính năng duyệt hàng loạt, họ có thể vô tình viết đè hoặc tạo ra một Controller khác trong `app/Modules/Moderation/`, dẫn đến xung đột tuyến đường hoặc trùng lặp logic xử lý.
- **Giải pháp đề xuất:**
  - Giữ nguyên hợp đồng URL và Route Name (`admin.bounties.payout-queue`, `admin.bounties.approve-payout`, `admin.bounties.reject-payout`).
  - Thống nhất rõ ràng: Giao dịch chuyển giao quyền sở hữu Controller này sang module `app/Modules/Moderation/` hoặc module `app/Modules/Administration/`, hoặc giữ nguyên ở `Bounties` với cam kết đóng băng mã nguồn (Freeze) không sửa đổi.
- **Module chịu trách nhiệm:** Hai bên thống nhất (`Student/Alumni` ⟷ `Admin/Moderator`).

---

### GAP-HIGH-02: Phân mảnh hệ thống thông báo làm mất tác dụng của bộ lọc Category
- **Mã Gap:** `GAP-HIGH-02`
- **Mức độ nghiêm trọng:** `HIGH`
- **Phân loại trạng thái:** `CONFIRMED_GAP` / `ARCHITECTURE_GAP`
- **Tài liệu nguồn:**
  - `docs/audits/NOTIFICATION_ARCHITECTURE.md`: Mục 1, Nguyên tắc 1 & Nguyên tắc 3.
  - `docs/audits/NOTIFICATION_INTEGRATION_CONTRACT.md`: Mục 2.
- **Vị trí tệp mã nguồn:**
  - Các file thông báo độc lập:
    - `app/Modules/Moderation/Infrastructure/Notifications/BountyPayoutClawbackRefundNotification.php`
    - `app/Modules/Moderation/Infrastructure/Notifications/BountyPayoutClawedBackNotification.php`
    - `app/Modules/Moderation/Infrastructure/Notifications/BountySubmissionCancelledNotification.php`
    - `app/Modules/Moderation/Infrastructure/Notifications/UserViolationSanctionedNotification.php`
    - `app/Modules/Bounties/Infrastructure/Notifications/BountyPayoutApprovedNotification.php`
    - `app/Modules/Bounties/Infrastructure/Notifications/BountyPayoutRejectedNotification.php`
  - Tệp truy vấn danh sách thông báo: [`app/Modules/Notifications/Application/Queries/GetNotificationsQuery.php:23`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Notifications/Application/Queries/GetNotificationsQuery.php#L23)
- **Bằng chứng từ mã nguồn:**
  - Các thông báo trên khi build payload `toDatabase()` chỉ truyền: `title`, `message`, `ma_yeu_cau`, `url` mà **không hề có thuộc tính `'category' => 'PERSONAL'`**.
  - Trong khi đó, tại `GetNotificationsQuery.php`, logic lọc thông báo theo danh mục được viết:
    ```php
    if (!empty($category)) {
        $query->where('du_lieu', 'like', '%"category":"' . $category . '"%');
    }
    ```
- **Hậu quả nếu không xử lý:**
  - Khi sinh viên bấm vào tab lọc "Thông báo cá nhân" (`category=PERSONAL`) trên giao diện Bảng điều khiển hoặc Trung tâm thông báo, toàn bộ các thông báo quan trọng về giải ngân điểm ví, hoàn tiền đền bù Clawback, thu hồi điểm, và cảnh báo vi phạm sẽ **hoàn toàn biến mất khỏi danh sách**. Sinh viên tưởng lầm rằng mình không nhận được thông báo hoặc hệ thống bị lỗi giao dịch.
- **Giải pháp đề xuất:**
  - Cập nhật payload `toDatabase()` của toàn bộ các class notification trên để bổ sung `'category' => 'PERSONAL'`, hoặc điều phối việc gửi thông báo qua `App\Modules\Notifications\Domain\Services\NotificationService` theo đúng kiến trúc dùng chung đã thiết kế.
- **Module chịu trách nhiệm:** `Shared Infrastructure` (`Notifications`).

---

### GAP-HIGH-03: Ma sát kiểm duyệt 2 lớp đối với tài liệu nộp Bounty (Method B) làm treo cọc điểm
- **Mã Gap:** `GAP-HIGH-03`
- **Mức độ nghiêm trọng:** `HIGH`
- **Phân loại trạng thái:** `CONFIRMED_GAP` / `WORKFLOW_GAP`
- **Tài liệu nguồn:**
  - `docs/integration/ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md`: Mục 4.2 (Sơ đồ vòng đời Bounty).
  - `docs/integration/STUDENT_ALUMNI_MODERATION_HANDOFF.md`: Mục 3.1 & 3.2, và Câu hỏi mở số 4 (SLA Timeout).
- **Vị trí tệp mã nguồn:**
  - [`app/Modules/Bounties/Application/Actions/ConfirmBountyFulfillmentAction.php:48-52`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Application/Actions/ConfirmBountyFulfillmentAction.php#L48-L52)
- **Bằng chứng từ mã nguồn:**
  - Sinh viên A tạo Bounty treo thưởng 50 điểm (điểm bị đóng băng).
  - Sinh viên B tải tệp mới lên (Method B) $\rightarrow$ Tệp mang trạng thái `CHO_DUYET`.
  - Sinh viên A vào xem bài nộp nhưng nút [Xác nhận] bị vô hiệu hóa vì `taiLieu->trang_thai !== 'DA_DUYET'`.
  - Kiểm duyệt viên phải vào duyệt tệp sang `DA_DUYET` (Lớp 1).
  - Sinh viên A mới bấm được "Xác nhận", đẩy bài nộp sang `CHO_GIAI_NGAN`.
  - Kiểm duyệt viên phải vào Hàng đợi giải ngân để bấm Duyệt giải ngân (Lớp 2) thì điểm mới được chuyển.
- **Hậu quả nếu không xử lý:**
  - Quy trình quá dài và phụ thuộc 2 lần vào sự có mặt của Kiểm duyệt viên. Nếu Kiểm duyệt viên chậm trễ ở Lớp 1, Sinh viên A không thể nghiệm thu, dẫn đến nản lòng hoặc hủy yêu cầu. Sinh viên B đóng góp tài liệu tốt nhưng phải chờ đợi mòn mỏi mà không biết tình trạng bài nộp của mình đang tắc ở khâu nào.
- **Giải pháp đề xuất:**
  - Thêm cờ thông báo rõ ràng trên giao diện Sinh viên A: *"Tài liệu này đang được Ban kiểm duyệt thẩm định chất lượng ban đầu"*.
  - Tại giao diện Admin Review Queue, gắn nhãn ưu tiên `[Bounty Submission - X điểm]` để Kiểm duyệt viên ưu tiên duyệt các file nộp cho Bounty trước file thường.
- **Module chịu trách nhiệm:** `Bounties` (Student) ⟷ `Moderation` (Admin).

---

### GAP-MED-01: Bất đồng bộ URI Route Báo cáo vi phạm giữa tài liệu bàn giao và mã nguồn thực tế
- **Mã Gap:** `GAP-MED-01`
- **Mức độ nghiêm trọng:** `MEDIUM`
- **Phân loại trạng thái:** `DOCUMENTATION_GAP`
- **Tài liệu nguồn:**
  - `docs/integration/STUDENT_ALUMNI_MODERATION_HANDOFF.md`: Sơ đồ tuần tự số 3 (dòng 341) ghi `POST /tai-lieu/{id}/bao-cao`.
  - `docs/integration/ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md`: Nghiệp vụ 5 (dòng 244) ghi `POST /bao-cao/tai-lieu`.
- **Vị trí tệp mã nguồn:**
  - Tuyến thực tế tại [`app/Modules/Moderation/routes.php:13`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Moderation/routes.php#L13): `Route::post('/bao-cao/tai-lieu', [DocumentReportController::class, 'store'])->name('reports.document.store');`
- **Thực tế giao diện:**
  - File component `resources/js/Components/Documents/DocumentReportModal.jsx:34` đã gọi đúng `route('reports.document.store')`.
- **Hậu quả & Đánh giá:**
  - Mặc dù mã nguồn thực tế đang khớp nhau, nhưng tài liệu `STUDENT_ALUMNI_MODERATION_HANDOFF.md` viết sai định dạng URI, gây hiểu nhầm nghiêm trọng cho các nhà phát triển đọc tài liệu kiến trúc khi tích hợp API từ bên thứ ba hoặc viết kiểm thử tự động.
- **Giải pháp:** Cập nhật tài liệu bàn giao cho chuẩn xác với `reports.document.store`.
- **Module chịu trách nhiệm:** Phân hệ Tài liệu & Kiểm toán tài liệu.

---

### GAP-MED-02: Thiếu cơ chế Timeout / SLA tự động hoàn tiền khi yêu cầu Bounty bị bỏ quên
- **Mã Gap:** `GAP-MED-02`
- **Mức độ nghiêm trọng:** `MEDIUM`
- **Phân loại trạng thái:** `IMPLEMENTATION_GAP`
- **Tài liệu nguồn:**
  - `docs/integration/STUDENT_ALUMNI_MODERATION_HANDOFF.md`: Câu hỏi mở số 4 (SLA Timeout, dòng 437-442).
- **Hiện trạng:**
  - Hiện tại hệ thống chỉ cho phép Sinh viên A tự bấm nút [Hủy yêu cầu] khi trạng thái còn là `MO`.
  - Nếu yêu cầu đã chuyển sang `CHO_GIAI_NGAN` (đang chờ Admin duyệt) mà Admin/Mod không bao giờ vào duyệt thì nút [Hủy yêu cầu] đã bị ẩn trên giao diện, và hệ thống Backend cũng **không có bất kỳ Scheduler hay Console Command nào** để quét các yêu cầu bị treo quá 7 ngày nhằm tự động hoàn tiền.
- **Hậu quả:** Điểm thưởng có nguy cơ bị đóng băng vô thời hạn trong `diem_tam_giu`.
- **Giải pháp:** Thiết lập Scheduled Task `bounties:cancel-expired-requests` tự động hoàn tiền cọc nếu sau 14 ngày yêu cầu không được giải quyết.
- **Module chịu trách nhiệm:** `Bounties` (Student).

---

### GAP-MED-03: Lệch pha giữa mô tả tài liệu (Tiền kiểm review) và mã nguồn thực tế (Hậu kiểm review)
- **Mã Gap:** `GAP-MED-03`
- **Mức độ nghiêm trọng:** `MEDIUM`
- **Phân loại trạng thái:** `DESIGN_GAP` / `DOCUMENTATION_GAP`
- **Tài liệu nguồn:**
  - `docs/integration/ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md`: Checklist dòng 616 ghi *"Chỉ hiển thị các review có trang_thai == 'DA_DUYET'"*.
- **Vị trí tệp mã nguồn:**
  - [`app/Modules/Reviews/Application/Actions/StoreCourseReviewAction.php:49`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Application/Actions/StoreCourseReviewAction.php#L49)
- **Hiện trạng thực tế:**
  - Khi sinh viên đăng bài đánh giá môn học: Hệ thống chạy bộ lọc từ cấm tự động `ProfanityFilterEvaluator`. Nếu văn bản hợp lệ, bài đánh giá được lưu ngay với trạng thái `'trang_thai' => 'DA_DUYET'`.
  - Hàng đợi của Admin (`admin.review-reports.index`) thực chất chỉ là **Hàng đợi xử lý Báo cáo vi phạm** (Hậu kiểm), không phải hàng đợi duyệt từng bài đánh giá mới (Tiền kiểm).
- **Hậu quả & Đánh giá:**
  - Nếu thành viên phát triển Frontend hiểu nhầm câu chữ trong tài liệu rằng bài đánh giá môn học phải chờ Admin duyệt thì sinh viên viết review xong sẽ không thấy hiển thị ngay, gây trải nghiệm tệ. Cần làm rõ: Đánh giá môn học tuân theo cơ chế **Hậu kiểm (Post-moderation)**, còn Tài liệu học thuật tuân theo cơ chế **Tiền kiểm (Pre-moderation)**.
- **Module chịu trách nhiệm:** `Reviews` & `Moderation`.

---

### GAP-LOW-01: Thiếu ngữ cảnh nhãn Bounty trên màn hình chi tiết thẩm định tài liệu Admin
- **Mã Gap:** `GAP-LOW-01`
- **Mức độ nghiêm trọng:** `LOW`
- **Phân loại trạng thái:** `UI_GAP`
- **Vị trí tệp:**
  - `resources/js/Pages/Admin/Documents/ReviewDetail.jsx`
- **Hiện trạng:**
  - Màn hình `ReviewQueue.jsx` có hiển thị tag nhỏ nếu tài liệu thuộc bài nộp Bounty, nhưng khi Moderator click vào xem chi tiết tại `ReviewDetail.jsx` thì giao diện hoàn toàn không hiển thị thông tin bài nộp săn thưởng (Tên yêu cầu, sinh viên yêu cầu, số điểm thưởng gắn liền).
- **Giải pháp:** Bổ sung khối Alert thông tin Bounty vào `ReviewDetail.jsx` để cán bộ kiểm duyệt nắm được tính chất khẩn cấp của tài liệu.
- **Module chịu trách nhiệm:** `Admin/Moderator Module`.

---

### GAP-LOW-02: Tuyên bố kiểm thử 100% đạt nhưng thực tế nhiều test case đang bị chặn do lỗi import
- **Mã Gap:** `GAP-LOW-02`
- **Mức độ nghiêm trọng:** `LOW`
- **Phân loại trạng thái:** `TEST_GAP`
- **Tài liệu nguồn:**
  - `docs/audits/NOTIFICATION_TEST_REPORT.md`: Báo cáo 270/270 passed.
  - `docs/integration/ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md`: Khuyến nghị kiểm thử dòng 640-649.
- **Hiện trạng:**
  - Do lỗi thiếu import tại GAP-CRIT-01, khi chạy toàn bộ test suite, có ít nhất 4 file test bị văng exception và không thể hoàn tất assertion.
- **Giải pháp:** Cập nhật lại kết quả test thực tế sau khi đã áp dụng bản vá cho GAP-CRIT-01.
- **Module chịu trách nhiệm:** `Technical Audit`.

