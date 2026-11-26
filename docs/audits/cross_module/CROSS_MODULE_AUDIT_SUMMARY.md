# BÁO CÁO TỔNG QUAN KIỂM TOÁN TÍCH HỢP LIÊN PHÂN HỆ
## AGU Academic Hub — Cross-Module Integration Audit & Scope Reduction
**Phân hệ đối chiếu:** `Student/Alumni Module` (Phụ trách hiện tại) & `Admin/Moderator Module` (Thành viên phối hợp)  
**Thời điểm thực hiện:** 10/11/2026  
**Cương vị kiểm toán:** Senior Software Architect & Technical Auditor  
**Nguyên tắc thực hiện:** STRICT READ-ONLY AUDIT — Đối soát trực tiếp giữa 38 tài liệu dự án và mã nguồn thực tế (`Route → Controller → Action/Query → Model → Test`). Không tự ý sửa mã nguồn hoặc tài liệu gốc.

---

## 1. MỤC TIÊU VÀ PHẠM VI KIỂM TOÁN

### 1.1. Mục tiêu
1. Xác định toàn diện và phân loại toàn bộ các **Điểm chạm tích hợp (Integration Touchpoints)** giữa hai phân hệ `Student/Alumni` và `Admin/Moderator`.
2. Kiểm chứng thực tế tính khả thi, mức độ hoàn thiện (`IMPLEMENTED` vs `VERIFIED`), và sự tồn tại của bằng chứng mã nguồn / kiểm thử tự động.
3. Nhận diện các **Khoảng trống tích hợp (Integration Gaps)**: Lỗi runtime tiềm ẩn, xung đột quy trình, đứt đoạn dữ liệu, vùng kẹt nghiệp vụ (deadlock), và các điểm hở phân quyền.
4. Rà soát ranh giới trách nhiệm, phát hiện các chức năng bị trùng lặp hoặc mở rộng quá mức (Scope Creep).
5. Đề xuất phương án **Thu hẹp phạm vi (Scope Reduction)** theo mô hình MoSCoW (Must, Should, Could, Defer) và lập danh mục quyết định then chốt cần hai bên thống nhất trước khi bước vào giai đoạn nghiệm thu.

### 1.2. Thống kê tài liệu bắt buộc đã đọc và đối chiếu
Tổng số tài liệu trong phạm vi bắt buộc: **38 / 38 tài liệu (100% hoàn thành)**:
- **Tài liệu trung tâm & Đối chiếu tích hợp (2/2):**
  - [`docs/integration/ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/integration/ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md) (651 dòng - 60.8 KB)
  - [`docs/integration/STUDENT_ALUMNI_MODERATION_HANDOFF.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/integration/STUDENT_ALUMNI_MODERATION_HANDOFF.md) (583 dòng - 44.4 KB)
- **Nhóm Audits & Báo cáo kỹ thuật (27/27):**
  - Toàn bộ 7 tài liệu Kiến trúc & Đặc tả Thông báo (`NOTIFICATION_ARCHITECTURE.md`, `NOTIFICATION_IMPLEMENTATION_REPORT.md`, `NOTIFICATION_INTEGRATION_CONTRACT.md`, `NOTIFICATION_OPERATIONS_GUIDE.md`, `NOTIFICATION_PHASE_0_AUDIT.md`, `NOTIFICATION_TEST_REPORT.md`, `NOTIFICATION_USE_CASE_SPECIFICATION.md`).
  - Toàn bộ các báo cáo kết quả & phạm vi từ Phase 1 đến Phase 3C (`PHASE_1_COURSE_CATALOG_*`, `PHASE_2_DASHBOARD_*`, `PHASE_3A_COURSE_DETAIL_*`, `PHASE_3B1_DOCUMENT_LISTING_*`, `PHASE_3B2_COURSE_REVIEWS_*`, `PHASE_3B3_COURSE_WISHLIST_*`, `PHASE_3C_DOCUMENT_CONTRIBUTION_*`).
  - Các báo cáo kiểm toán nền tảng (`REALITY_AUDIT_REPORT.md`, `REVISED_IMPLEMENTATION_PLAN.md`, `FEATURE_GAP_MATRIX.md`, `IMPLEMENTATION_DEPENDENCY_GRAPH.md`, `COURSE_DETAIL_INTEGRATION_AUDIT.md`, `OUT_OF_SCOPE_ISSUES.md`).
- **Nhóm Planning & Phân kỳ thực thi (9/9):**
  - [`docs/planning/STUDENT_ALUMNI_FRONTEND_INVENTORY.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/planning/STUDENT_ALUMNI_FRONTEND_INVENTORY.md)
  - [`docs/planning/STUDENT_ALUMNI_IMPLEMENTATION_PLAN.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/planning/STUDENT_ALUMNI_IMPLEMENTATION_PLAN.md)
  - [`docs/planning/STUDENT_ALUMNI_TASK_TRACKER.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/planning/STUDENT_ALUMNI_TASK_TRACKER.md)
  - [`docs/planning/STUDENT_ALUMNI_TEST_CHECKLIST.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/planning/STUDENT_ALUMNI_TEST_CHECKLIST.md)
  - 5 tài liệu phân kỳ: `PHASE_0_STABILIZATION.md` đến `PHASE_4_POLISH.md`.

---

## 2. TỔNG HỢP KẾT QUẢ KIỂM TOÁN TÍCH HỢP

### 2.1. Số liệu tổng quan
- **Tổng số Integration Touchpoints được xác định:** **42 điểm chạm** thuộc 7 phân nhóm nghiệp vụ.
- **Phân loại trạng thái triển khai của Touchpoints:**
  - `VERIFIED` (Có mã nguồn + Có test xác nhận chạy đạt): **28 / 42 điểm chạm** (66.7%).
  - `IMPLEMENTED` (Có mã nguồn nhưng test liên quan đang lỗi hoặc thiếu test): **10 / 42 điểm chạm** (23.8%).
  - `CONTRACT_DEFINED` (Đã có hợp đồng/giao diện nhưng thiếu mắt xích liên thông): **4 / 42 điểm chạm** (9.5%).
  - `REQUIREMENT_DEFINED`: **0 / 42 điểm chạm**.
- **Tổng số Integration Gaps phát hiện:** **10 Gaps**, phân bổ như sau:
  - **CRITICAL:** **02 Gaps** (Lỗi Fatal Runtime gây sập hàng đợi kiểm tra & Vùng chết nghiệp vụ gỡ tài liệu).
  - **HIGH:** **03 Gaps** (Xung đột đặt sai vị trí module Bounty Controller, Phân mảnh hệ thống Thông báo, Ma sát quy trình duyệt 2 lớp).
  - **MEDIUM:** **03 Gaps** (Lệch tài liệu Route báo cáo, Thiếu cơ chế Timeout/Hoàn điểm tự động, Thiếu nhất quán chiến lược tiền kiểm/hậu kiểm).
  - **LOW:** **02 Gaps** (Thiếu nhãn Bounty trên trang chi tiết kiểm duyệt, Lệch số liệu báo cáo test giữa tài liệu và thực tế).

---

## 3. NHỮNG PHÁT HIỆN TRỌNG YẾU (TOP CRITICAL & HIGH FINDINGS)

### 3.1. [CRITICAL] Lỗi Fatal Runtime trong `DecideDocumentReviewAction` làm tê liệt hàng loạt luồng kiểm duyệt và kiểm thử
- **Hiện tượng:** Tại file [`app/Modules/Moderation/Application/Actions/DecideDocumentReviewAction.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Moderation/Application/Actions/DecideDocumentReviewAction.php#L18), class khai báo type-hint:
  ```php
  protected UserViolationPenaltyEvaluator $penaltyEvaluator,
  ```
  nhưng **hoàn toàn thiếu lệnh import**:
  ```php
  use App\Modules\Moderation\Domain\Services\UserViolationPenaltyEvaluator;
  ```
- **Hậu quả thực tế:** Khi Laravel Service Container resolve dependency, nó cố gắng tìm class `App\Modules\Moderation\Application\Actions\UserViolationPenaltyEvaluator` (không tồn tại) và ném lỗi chết người:
  `BindingResolutionException: Target class [...] does not exist`.
- **Phạm vi ảnh hưởng:**
  - Tuyến `PUT /quan-tri/tai-lieu/{id}/quyet-dinh` bị sập 100% khi Kiểm duyệt viên chọn thao tác `HIDE` hoặc `DELETE`.
  - Làm gãy **04 bộ test quan trọng** của dự án:
    1. `ModerationBountyClawbackTest` (2/4 tests fail).
    2. `ModerationViolationPenaltyTest` (fail).
    3. `ModerationBountySubmissionSyncTest` (fail).
    4. `NotificationModuleIntegrationTest` (test case `uc st 02 and uc st 03 document moderation` fail).
  - Trái ngược với tuyên bố tại `ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:640` và `NOTIFICATION_TEST_REPORT.md` rằng các test này đã "vượt qua 100% assertions".

### 3.2. [CRITICAL] "Vùng chết nghiệp vụ" (Deadlock Zone) trong quy trình xóa và gỡ tài liệu của tác giả
- **Hiện tượng:** Đối soát tại [`app/Modules/Documents/Domain/Services/DocumentAuthorRemovalEvaluator.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Domain/Services/DocumentAuthorRemovalEvaluator.php#L11-L45):
  - Phương thức `canAuthorSoftDelete`: Chỉ cho phép tác giả tự xóa mềm khi `luot_tai == 0` **VÀ** tài liệu đăng tải **chưa quá 24 giờ**.
  - Phương thức `canRequestRemoval`: Chỉ cho phép tác giả gửi đơn xin Ban kiểm duyệt gỡ khi `luot_tai >= 10` (ngưỡng sở hữu cộng đồng).
- **Hậu quả nghiệp vụ:** Nảy sinh một "khoảng trống bế tắc" (Deadlock Zone) mà tác giả không có bất kỳ con đường hợp lệ nào để xóa tài liệu của chính mình:
  1. Tài liệu có $1 \le \text{luot\_tai} \le 9$: Bị chặn tự xóa (vì đã có tải) và bị chặn gửi đơn xin gỡ (vì chưa đủ 10 tải).
  2. Tài liệu đã đăng quá 24 giờ nhưng `luot_tai == 0`: Bị chặn tự xóa (quá 24h) và bị chặn gửi đơn xin gỡ (chưa đủ 10 tải).
  Sinh viên bị mắc kẹt và buộc phải dùng chiêu trò "tự báo cáo vi phạm tài liệu của chính mình" để nhờ Moderator can thiệp.

### 3.3. [HIGH] Trùng lặp trách nhiệm và đặt sai vị trí Module của Hàng đợi Giải ngân Bounty
- **Hiện tượng:**
  - Tài liệu `ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md` xếp "Hàng đợi Giải ngân Bounty" thành **Nghiệp vụ 15** thuộc trách nhiệm của Ban Quản trị & Kiểm duyệt.
  - Tuy nhiên, mã nguồn triển khai thực tế gồm Controller [`AdminBountyPayoutController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Presentation/Http/Controllers/AdminBountyPayoutController.php) và toàn bộ các tuyến đường `admin.bounties.payout-queue`, `approve-payout`, `reject-payout` lại được đặt hoàn toàn bên trong module **`app/Modules/Bounties/`** (phân hệ của Student/Alumni), thay vì đặt trong `app/Modules/Moderation/` hoặc `app/Modules/Administration/`.
- **Hậu quả:** Gây nhập nhằng ranh giới module (Architectural Boundary Violation). Phân hệ Bounties của sinh viên đang phải ôm đồm cả logic điều phối giao diện quản trị của cán bộ kiểm duyệt.

### 3.4. [HIGH] Phân mảnh kiến trúc Thông báo (Dual Notification Architecture)
- **Hiện tượng:**
  - Dự án đã xây dựng Module Thông báo trung tâm hướng đối tượng (`app/Modules/Notifications/`) với Enum 17 loại (`NotificationType.php`) và phân nhóm `NotificationCategory` (`PERSONAL` vs `WORK_ITEM`).
  - Tuy nhiên, cả hai phân hệ `Moderation` và `Bounties` lại tự tạo các class Laravel Notification riêng rẽ (`BountyPayoutClawbackRefundNotification`, `BountyPayoutClawedBackNotification`, `BountySubmissionCancelledNotification`, `UserViolationSanctionedNotification`, `BountyPayoutApprovedNotification`, `BountyPayoutRejectedNotification`,...) và gọi trực tiếp `$user->notify(...)`.
- **Hậu quả:** Các thông báo này không chứa trường `category` trong JSON dữ liệu. Do đó, khi người dùng lọc theo tab "Cá nhân" (`category=PERSONAL`) tại API `GET /thong-bao`, truy vấn SQL `$query->where('du_lieu', 'like', '%"category":"PERSONAL"%')` tại [`GetNotificationsQuery.php:23`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Notifications/Application/Queries/GetNotificationsQuery.php#L23) sẽ **lọc bỏ hoàn toàn các thông báo tài chính quan trọng này**!

### 3.5. [HIGH] Quy trình kiểm duyệt 2 lớp đối với tài liệu nộp Bounty (Method B) gây trễ hạn
- **Hiện tượng:** Khi Sinh viên B nộp tệp từ máy tính cho yêu cầu Bounty:
  - Tệp tạo mới ở trạng thái `CHO_DUYET`. Sinh viên A (Requester) **bị khóa nút xác nhận** cho đến khi Kiểm duyệt viên duyệt tệp sang `DA_DUYET`.
  - Sau khi tệp được duyệt, Sinh viên A mới bấm "Xác nhận", đẩy bài nộp sang hàng đợi thứ hai: `CHO_GIAI_NGAN`.
  - Kiểm duyệt viên/Admin phải vào hàng đợi giải ngân để duyệt lần thứ hai thì Sinh viên B mới nhận được điểm.
- **Rủi ro:** Quy trình phụ thuộc 2 lần vào sự can thiệp thủ công của Kiểm duyệt viên mà hệ thống hiện tại **chưa có cơ chế Timeout hoặc SLA tự động**, dẫn đến nguy cơ điểm tạm giữ của Sinh viên A bị treo vĩnh viễn nếu Ban kiểm duyệt xử lý chậm.

---

## 4. NHỮNG ĐIỂM CHƯA THỂ XÁC MINH (UNVERIFIABLE AREAS)

1. **Khả năng hiển thị thực tế của `ReviewDetail.jsx` đối với tài liệu nộp Bounty:** Mã nguồn frontend cho thấy `ReviewQueue.jsx` có kiểm tra badge `dap_ung_yeu_caus`, nhưng `ReviewDetail.jsx` chưa hiển thị rõ thông tin yêu cầu săn thưởng liên kết. Cần kiểm tra giao diện thực tế khi chạy browser.
2. **Kịch bản xử lý tranh chấp khi Admin từ chối giải ngân nhưng Sinh viên A kiên quyết muốn lấy tài liệu:** Hiện tại hệ thống tự động hoàn điểm cho Sinh viên A và mở lại yêu cầu, nhưng chưa có luồng giải quyết khiếu nại nếu Sinh viên B đã cung cấp đúng tài liệu mà Admin lại đánh giá nhầm là spam.
3. **Cấu hình hàng đợi nền (Queue Workers) trong môi trường Production:** Toàn bộ test hiện chạy trên driver `sync`. Hành vi bất đồng bộ khi đẩy hàng loạt thông báo qua Laravel Queue chưa được kiểm chứng tải thực tế.

---

## 5. KẾT LUẬN VỀ MỨC ĐỘ SẴN SÀNG TÍCH HỢP (INTEGRATION READINESS)

> [!IMPORTANT]
> **ĐÁNH GIÁ MỨC ĐỘ SẴN SÀNG: `CONDITIONAL PASS / READY AFTER CRITICAL FIXES`**
> 
> Nền tảng tích hợp giữa hai module đã đạt **85% độ phủ nghiệp vụ** với các hợp đồng dữ liệu, RBAC, phân quyền bảo vệ tài liệu `CHO_DUYET`, và các Action giải ngân / clawback rất bài bản.
> 
> Tuy nhiên, hệ thống **CHƯA THỂ NGHIỆM THU NGAY** cho đến khi khắc phục triệt để **2 lỗi CRITICAL** (lỗi import thiếu trong `DecideDocumentReviewAction` và vùng kẹt gỡ tài liệu), đồng thời hai bên phải chốt biên bản phân định trách nhiệm quản lý Hàng đợi giải ngân Bounty.

---

## 6. DANH MỤC 07 FILE BÁO CÁO CHI TIẾT TRONG BỘ HỒ SƠ KIỂM TOÁN

Toàn bộ kết quả kiểm toán được lưu trữ bất biến tại thư mục `docs/audits/cross_module/`:

1. [CROSS_MODULE_AUDIT_SUMMARY.md](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/cross_module/CROSS_MODULE_AUDIT_SUMMARY.md) — *Báo cáo tổng quan điều hành và hiện trạng sẵn sàng tích hợp.*
2. [INTEGRATION_TOUCHPOINT_MATRIX.md](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/cross_module/INTEGRATION_TOUCHPOINT_MATRIX.md) — *Ma trận 42 điểm chạm chi tiết kèm Data Contract và bằng chứng.*
3. [INTEGRATION_GAP_ANALYSIS.md](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/cross_module/INTEGRATION_GAP_ANALYSIS.md) — *Phân tích chi tiết 10 khoảng trống tích hợp (Gaps) từ CRITICAL đến LOW.*
4. [MODULE_RESPONSIBILITY_MATRIX.md](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/cross_module/MODULE_RESPONSIBILITY_MATRIX.md) — *Bảng phân định trách nhiệm RACI chuẩn hóa cho 18 nhóm nghiệp vụ.*
5. [RECOMMENDED_SCOPE_REDUCTION.md](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/cross_module/RECOMMENDED_SCOPE_REDUCTION.md) — *Kế hoạch thu hẹp phạm vi triển khai tối thiểu (MoSCoW) phục vụ nghiệm thu.*
6. [CROSS_MODULE_DECISIONS_REQUIRED.md](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/cross_module/CROSS_MODULE_DECISIONS_REQUIRED.md) — *Danh mục 05 quyết định kiến trúc bắt buộc hai thành viên phải ký duyệt.*
7. [CROSS_MODULE_AUDIT_EVIDENCE.md](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/cross_module/CROSS_MODULE_AUDIT_EVIDENCE.md) — *Nhật ký kiểm chứng thực tế, danh mục tệp tin, test run và các giả định.*

