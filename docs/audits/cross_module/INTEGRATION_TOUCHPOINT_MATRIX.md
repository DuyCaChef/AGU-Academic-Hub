# MA TRẬN ĐIỂM CHẠM TÍCH HỢP LIÊN PHÂN HỆ
## AGU Academic Hub — Cross-Module Integration Touchpoint Matrix
**Phân hệ:** `Student/Alumni` ⟷ `Admin/Moderator` ⟷ `Shared Core`  
**Quy ước trạng thái:** `VERIFIED` (Có mã + Có test đạt) | `IMPLEMENTED` (Có mã nguồn) | `CONTRACT_DEFINED` (Có đặc tả/hợp đồng) | `REQUIREMENT_DEFINED` (Mới có yêu cầu)

---

## 1. PHÂN NHÓM A: QUẢN LÝ VÀ KIỂM DUYỆT TÀI LIỆU (DOCUMENT MODERATION)

| ID | Nghiệp vụ | Module khởi tạo | Module tiếp nhận | Dữ liệu giao tiếp | Cơ chế giao tiếp | Trạng thái | Bằng chứng thực tế | Gap liên quan | Mức độ |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: | :--- | :---: | :---: |
| **INT-DOC-001** | Đăng tải tài liệu độc lập & Chặn lộ lọt | `Documents` (Student) | `Moderation` | File upload, metadata, mã hash SHA-256 | `POST /tai-lieu/dang-tai`, State `CHO_DUYET` | `VERIFIED` | `UploadDocumentAction.php`, `DocumentContributionBountyIntegrationTest.php` | - | NONE |
| **INT-DOC-002** | Xem trước & Thẩm định tệp chờ duyệt | `Moderation` | `Documents` | Stream PDF, metadata, binary file | `GET /quan-tri/tai-lieu/{id}/xem-tep` | `VERIFIED` | `DocumentModerationController@previewDocument`, `AdminRouteAuthorizationTest.php` | - | NONE |
| **INT-DOC-003** | Phê duyệt tài liệu mới đơn lẻ | `Moderation` | `Documents` / `Identity` | `ma_tai_lieu`, `ly_do_trang_thai`, Actor | `PUT /quan-tri/tai-lieu/{id}/duyet` | `VERIFIED` | `ApproveNewDocumentAction.php`, `DocumentModerationController.php:48` | - | NONE |
| **INT-DOC-004** | Phê duyệt hàng loạt tài liệu mới | `Moderation` | `Documents` | Mảng `ids: [int]`, Actor | `POST /quan-tri/tai-lieu/duyet-hang-loat` | `VERIFIED` | `BulkApproveNewDocumentsAction.php`, `ModerationBulkActionsTest.php` | - | NONE |
| **INT-DOC-005** | Từ chối phê duyệt tài liệu mới | `Moderation` | `Documents` / `Notifications` | `ma_tai_lieu`, `reason` ($\ge 10$ ký tự) | `PUT /quan-tri/tai-lieu/{id}/tu-choi` | `VERIFIED` | `RejectNewDocumentAction.php`, `DocumentRejectedNotification.php` | - | NONE |
| **INT-DOC-006** | Sinh viên báo cáo vi phạm tài liệu | `Documents` (Student) | `Moderation` | `tai_lieu_id`, `ly_do`, `mo_ta` | `POST /bao-cao/tai-lieu` (`reports.document.store`) | `VERIFIED` | `DocumentReportController@store`, `DocumentReportMonitoringTest.php` | GAP-MED-01 | MEDIUM |
| **INT-DOC-007** | Tự động đưa vào hàng đợi kiểm tra ($\ge 3$ báo cáo) | `Moderation` (Evaluator) | `Moderation` (Queue) | `so_bao_cao_hop_le`, `tu_dong_chuyen_luc` | Event/State: `CHO_KIEM_TRA` | `VERIFIED` | `DocumentReportRecalculator.php`, `DocumentReportMonitoringTest.php` | - | NONE |
| **INT-DOC-008** | Tự động tạm ẩn khẩn cấp tài liệu ($\ge 5$ báo cáo) | `Moderation` (Evaluator) | `Catalog` / `Documents` | Trạng thái: `TAM_AN_KIEM_TRA` | DB State update, ẩn khỏi catalog | `VERIFIED` | `DocumentReportRecalculator.php`, `DocumentReportMonitoringTest.php` | - | NONE |
| **INT-DOC-009** | Xử lý tài liệu trong Hàng đợi kiểm tra (Review Queue) | `Moderation` | `Documents` / `Identity` | `documentId`, `action` (KEEP, RESTORE, HIDE, DELETE), `reason` | `PUT /quan-tri/tai-lieu/{id}/quyet-dinh` | `IMPLEMENTED` | `DecideDocumentReviewAction.php:18` (Lỗi runtime thiếu import) | **GAP-CRIT-01** | **CRITICAL** |
| **INT-DOC-010** | Xử lý chi tiết báo cáo vi phạm tài liệu | `Moderation` | `Documents` / `Notifications` | `reportId`, `action` (DELETE, HIDE, DISMISS), `resolution_reason` | `PUT /quan-tri/bao-cao-tai-lieu/{id}/giai-quyet` | `VERIFIED` | `ResolveViolationReportAction.php`, `ReviewModerationWorkflowTest.php` | - | NONE |
| **INT-DOC-011** | Tự động trừ điểm uy tín & phạt tài khoản khi xóa vi phạm | `Moderation` | `Administration` / `Identity` | `author_id`, `diem_uy_tin -= 20`, `so_lan_vi_pham += 1` | Domain Service `UserViolationPenaltyEvaluator` | `IMPLEMENTED` | `UserViolationPenaltyEvaluator.php` (Bị chặn gián tiếp do GAP-CRIT-01) | **GAP-CRIT-01** | **CRITICAL** |
| **INT-DOC-012** | Tác giả tự xóa mềm tài liệu (< 24h & 0 lượt tải) | `Documents` (Student) | `Moderation` (Trash) | `id`, `reason`, `nguon_xoa = TAC_GIA` | `DELETE /tai-lieu/{id}` | `VERIFIED` | `AuthorSoftDeleteDocumentAction.php`, `DocumentManagementComprehensiveTest.php` | GAP-CRIT-02 | CRITICAL |
| **INT-DOC-013** | Tác giả gửi yêu cầu gỡ tài liệu cộng đồng ($\ge 10$ lượt tải) | `Documents` (Student) | `Moderation` (Queue) | `ma_tai_lieu`, `ly_do`, Pessimistic Lock | `POST /tai-lieu/{id}/yeu-cau-go-bo` | `VERIFIED` | `SubmitRemovalRequestAction.php`, `DocumentRemovalRequestTest.php` | GAP-CRIT-02 | CRITICAL |
| **INT-DOC-014** | Thẩm định & Ra quyết định yêu cầu gỡ tài liệu | `Moderation` | `Documents` / `Bounties` | `requestId`, `action` (APPROVE/REJECT), `reason` | `PUT /quan-tri/yeu-cau-go-tai-lieu/{id}/quyet-dinh` | `VERIFIED` | `DecideRemovalRequestAction.php`, `DocumentRemovalRequestTest.php` | - | NONE |
| **INT-DOC-015** | Xem danh sách tài liệu trong Thùng rác | `Moderation` | `Administration` | Danh sách soft-deleted documents | `GET /quan-tri/tai-lieu/da-xoa` | `VERIFIED` | `DocumentModerationController@deletedDocuments`, `AdminRouteAuthorizationTest.php` | - | NONE |
| **INT-DOC-016** | Khôi phục tài liệu từ Thùng rác | `Administration` (Chỉ Admin) | `Documents` / `Moderation` | `id`, `confirm_violation: bool` | `POST /quan-tri/tai-lieu/{id}/khoi-phuc` | `VERIFIED` | `RestoreDocumentAction.php`, `AdminRouteAuthorizationTest.php` | - | NONE |
| **INT-DOC-017** | Xóa vĩnh viễn tài liệu khỏi hệ thống | `Administration` (Chỉ Admin) | `Documents` / `Storage` | `id`, Physical unlink & forceDelete | `DELETE /quan-tri/tai-lieu/{id}/xoa-vinh-vien` | `VERIFIED` | `PermanentlyDeleteDocumentAction.php`, `AdminStorageManagementTest.php` | - | NONE |
| **INT-DOC-018** | Quản lý dung lượng & Dọn rác quá hạn 60 ngày | `Administration` (Admin) | `Storage` / `Documents` | `han_luu_thung_rac < now()` | `POST /quan-tri/luu-tru/don-dep-thung-rac` | `VERIFIED` | `AdminStorageController@purgeExpiredTrash`, `AdminStorageManagementTest.php` | - | NONE |

---

## 2. PHÂN NHÓM B: SĂN THƯỞNG TÀI LIỆU (BOUNTY & DOCUMENT CONTRIBUTION)

| ID | Nghiệp vụ | Module khởi tạo | Module tiếp nhận | Dữ liệu giao tiếp | Cơ chế giao tiếp | Trạng thái | Bằng chứng thực tế | Gap liên quan | Mức độ |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: | :--- | :---: | :---: |
| **INT-BOUNTY-001** | Tạo yêu cầu tìm tài liệu (Wishlist Bounty) | `Bounties` (Student A) | `Identity` (Ví điểm) | `tieu_de`, `ma_mon_hoc`, `diem_thuong` | `POST /yeu-cau-tai-lieu`, Lock điểm tạm giữ | `VERIFIED` | `CreateWishlistBountyAction.php`, `WishlistBountyTest.php` | - | NONE |
| **INT-BOUNTY-002** | Đóng góp tài liệu từ kho cá nhân (Method A) | `Bounties` (Student B) | `Documents` | `ma_tai_lieu` (yêu cầu `DA_DUYET`), `ma_yeu_cau` | `POST /yeu-cau-tai-lieu/{id}/dong-gop` | `VERIFIED` | `BountyRuleEvaluator::canFulfillWithDocument`, `DocumentContributionBountyIntegrationTest.php` | - | NONE |
| **INT-BOUNTY-003** | Tải tệp mới từ máy nộp Bounty (Method B) | `Bounties` (Student B) | `Documents` / `Moderation` | File binary, dedup SHA-256, tạo `TaiLieu` (`CHO_DUYET`) | `POST /yeu-cau-tai-lieu/{id}/dong-gop` (multipart) | `VERIFIED` | `FulfillBountyWithUploadAction.php`, `DocumentContributionBountyIntegrationTest.php` | GAP-HIGH-03 | HIGH |
| **INT-BOUNTY-004** | Sinh viên A xác nhận chọn bài nộp (Step 1) | `Bounties` (Student A) | `Bounties` / `Moderation` | `ma_dap_ung`, check `tai_lieu == DA_DUYET` | `POST /yeu-cau-tai-lieu/{id}/xac-nhan`, State `CHO_GIAI_NGAN` | `VERIFIED` | `ConfirmBountyFulfillmentAction.php`, `DocumentContributionBountyIntegrationTest.php` | GAP-HIGH-03 | HIGH |
| **INT-BOUNTY-005** | Admin/Mod duyệt giải ngân Bounty (Step 2) | `Admin/Mod` (Console) | `Bounties` / `Identity` | `ma_yeu_cau`, giải phóng tạm giữ A, cộng điểm B | `PUT /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/{id}/duyet` | `VERIFIED` | `AdminBountyPayoutController@approve`, `ApproveBountyPayoutAction.php` | **GAP-HIGH-01** | **HIGH** |
| **INT-BOUNTY-006** | Admin/Mod từ chối giải ngân Bounty | `Admin/Mod` (Console) | `Bounties` / `Identity` | `ma_yeu_cau`, `ly_do`, hoàn 100% điểm về ví A | `PUT /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/{id}/tu-choi` | `VERIFIED` | `AdminBountyPayoutController@reject`, `RejectBountyPayoutAction.php` | **GAP-HIGH-01** | **HIGH** |
| **INT-BOUNTY-007** | Tự động từ chối bài nộp Bounty khi tài liệu bị xóa | `Moderation` | `Bounties` | `ma_tai_lieu`, `actor`, `reason` | Service `BountySubmissionModerationSynchronizer` | `IMPLEMENTED` | `BountySubmissionModerationSynchronizer.php:121` (Test fail do GAP-CRIT-01) | **GAP-CRIT-01** | **CRITICAL** |
| **INT-BOUNTY-008** | Thu hồi điểm (Clawback) & Hoàn tiền đền bù vi phạm | `Moderation` | `Bounties` / `Identity` | Thu hồi điểm B (chặn âm), cộng bù 100% ví A, Hủy yêu cầu | Service `ReputationEvaluator::thuHoiDiemBounty` | `IMPLEMENTED` | `ReputationEvaluator.php:120`, `BountySubmissionModerationSynchronizer.php:40` | **GAP-CRIT-01** | **CRITICAL** |
| **INT-BOUNTY-009** | Sinh viên A tự hủy yêu cầu khi chưa chọn bài nộp | `Bounties` (Student A) | `Identity` (Ví điểm) | `ma_yeu_cau`, hoàn điểm tạm giữ | `POST /yeu-cau-tai-lieu/{id}/huy` | `VERIFIED` | `CancelWishlistBountyAction.php`, `WishlistBountyTest.php` | GAP-MED-02 | MEDIUM |

---

## 3. PHÂN NHÓM C: ĐÁNH GIÁ MÔN HỌC & BÌNH CHỌN (COURSE REVIEWS & COMMUNITY)

| ID | Nghiệp vụ | Module khởi tạo | Module tiếp nhận | Dữ liệu giao tiếp | Cơ chế giao tiếp | Trạng thái | Bằng chứng thực tế | Gap liên quan | Mức độ |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: | :--- | :---: | :---: |
| **INT-REVIEW-001** | Sinh viên viết đánh giá & Lọc từ cấm tự động | `Reviews` (Student) | `Catalog` | `ma_hoc_phan`, 5 tiêu chí điểm, nhận xét, ẩn danh | `POST /mon-hoc/{code}/danh-gia`, State `DA_DUYET` | `VERIFIED` | `StoreCourseReviewAction.php`, `ProfanityFilterEvaluator.php` | GAP-MED-03 | MEDIUM |
| **INT-REVIEW-002** | Báo cáo bài đánh giá vi phạm (Môn học) | `Reviews` (Student) | `Moderation` | `ma_danh_gia`, `ly_do`, `mo_ta`, loại `MON_HOC` | `POST /danh-gia/{id}/bao-cao` | `VERIFIED` | `CourseReviewController@baoCaoViPham`, `ReviewModerationWorkflowTest.php` | - | NONE |
| **INT-REVIEW-003** | Báo cáo xúc phạm giảng viên độc lập | `Reviews` (Student) | `Moderation` | `ten_giang_vien`, `ly_do`, loại `GIANG_VIEN` | `POST /danh-gia/{id}/bao-cao` (kèm tên GV) | `VERIFIED` | `CourseReviewController.php:115`, `ReviewModerationWorkflowTest.php` | - | NONE |
| **INT-REVIEW-004** | Hàng đợi Báo cáo thống nhất Admin/Mod | `Moderation` | `Reviews` / `Documents` | Bộ lọc `type` (MON_HOC, GIANG_VIEN, TAI_LIEU) | `GET /quan-tri/bao-cao-danh-gia` | `VERIFIED` | `ReviewModerationController@index`, `GetReviewReportQueueQuery.php` | - | NONE |
| **INT-REVIEW-005** | Xử lý báo cáo review & Phạt điểm uy tín (-10) | `Moderation` | `Reviews` / `Identity` | `reportId`, `action` (HIDE, DELETE, DISMISS), phạt 10đ | `PUT /quan-tri/bao-cao-danh-gia/{id}/giai-quyet` | `VERIFIED` | `ResolveReviewReportAction.php`, `ReviewModerationWorkflowTest.php` | - | NONE |
| **INT-REVIEW-006** | Thao tác hàng loạt: Bác bỏ & Lưu trữ báo cáo | `Moderation` | `Reviews` / `Documents` | Mảng `reports: [{id, type}]`, `resolution_reason` | `POST /quan-tri/bao-cao/bac-bo-hang-loat` | `VERIFIED` | `BulkDismissReportsAction.php`, `BulkArchiveReportsAction.php` | - | NONE |
| **INT-REVIEW-007** | Bình chọn hữu ích Upvote/Downvote review | `Reviews` (Student) | `Reviews` | `ma_danh_gia`, `loai_vote` (1 / -1) | `POST /danh-gia/{id}/vote` | `VERIFIED` | `VoteReviewAction.php`, `CourseReviewTest.php` | - | NONE |

---

## 4. PHÂN NHÓM D: ĐỊNH DANH & DUYỆT KYC (IDENTITY & KYC VERIFICATION)

| ID | Nghiệp vụ | Module khởi tạo | Module tiếp nhận | Dữ liệu giao tiếp | Cơ chế giao tiếp | Trạng thái | Bằng chứng thực tế | Gap liên quan | Mức độ |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: | :--- | :---: | :---: |
| **INT-KYC-001** | Zero-KYC sinh viên đang học & Chặn đăng ký tự do | `Administration` | `Identity` | Nạp trước `danh_sach_sinh_vien_raw`, chặn đuôi trường | `POST /quan-tri/sinh-vien-raw/nap`, chặn `/dang-ky` | `VERIFIED` | `RegisterUserRequest.php:62`, `IdentityAuthComprehensiveTest.php` | - | NONE |
| **INT-KYC-002** | Kích hoạt đổi mật khẩu lần đầu & Cấp ví 100đ | `Identity` (Student) | `Bounties` (Ví điểm) | `password`, `password_confirmation`, gán ví 100 điểm | `POST /mat-khau-lan-dau` (`password.change.update`) | `VERIFIED` | `ChangeFirstTimePasswordAction.php`, `IdentityAuthComprehensiveTest.php` | - | NONE |
| **INT-KYC-003** | Cựu sinh viên nộp hồ sơ KYC & Minh chứng tốt nghiệp | `Identity` (Alumni) | `Moderation` | MSSV cũ, năm TN, file minh chứng vào `private/kyc/` | `POST /cuu-sinh-vien/dang-ky` | `VERIFIED` | `SubmitAlumniKycAction.php`, `IdentityKycComprehensiveTest.php` | - | NONE |
| **INT-KYC-004** | Xem trước minh chứng KYC qua Signed URL (30 phút) | `Moderation` | `Storage` (Private) | Signed URL token, `ma_yeu_cau_xac_thuc` | `GET /kyc/xem-anh/{id}` (Middleware: `signed`) | `VERIFIED` | `KycController@xemTruocMinhChung`, `IdentityKycComprehensiveTest.php` | - | NONE |
| **INT-KYC-005** | Phê duyệt hồ sơ KYC đơn lẻ & Cấp ví 100đ | `Moderation` | `Identity` / `Bounties` | Chuyển `DANG_HOAT_DONG`, khởi tạo `ViDiem = 100` | `PUT /quan-tri/duyet-kyc/{id}/duyet` | `VERIFIED` | `ApproveKycAction.php`, `KycQueueTest.php` | - | NONE |
| **INT-KYC-006** | Phê duyệt hàng loạt hồ sơ KYC | `Moderation` | `Identity` / `Bounties` | Mảng `ids: [int]`, kích hoạt tài khoản đồng loạt | `POST /quan-tri/kyc/phe-duyet-hang-loat` | `VERIFIED` | `KycController@pheDuyetHangLoat`, `ModerationBulkActionsTest.php` | - | NONE |
| **INT-KYC-007** | Từ chối hồ sơ KYC kèm lý do bắt buộc | `Moderation` | `Identity` / `Notifications` | `id`, `ly_do_tu_choi`, notification giải thích | `PUT /quan-tri/duyet-kyc/{id}/tu-choi` | `VERIFIED` | `RejectKycAction.php`, `KycQueueTest.php` | - | NONE |

---

## 5. PHÂN NHÓM E: HỆ THỐNG THÔNG BÁO DÙNG CHUNG (SHARED NOTIFICATIONS)

| ID | Nghiệp vụ | Module khởi tạo | Module tiếp nhận | Dữ liệu giao tiếp | Cơ chế giao tiếp | Trạng thái | Bằng chứng thực tế | Gap liên quan | Mức độ |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: | :--- | :---: | :---: |
| **INT-NOTI-001** | Tra cứu danh sách thông báo & Số lượng chưa đọc | Client (Student/Mod) | `Notifications` | Unread count badge, danh sách phân trang | `GET /thong-bao`, `GET /thong-bao/so-luong-chua-doc` | `VERIFIED` | `NotificationController.php`, `NotificationModuleIntegrationTest.php` | - | NONE |
| **INT-NOTI-002** | Đánh dấu đã đọc đơn lẻ và đọc tất cả | Client (Student/Mod) | `Notifications` | `notification_id` hoặc đánh dấu toàn bộ | `POST /thong-bao/{id}/doc`, `POST /thong-bao/doc-tat-ca` | `VERIFIED` | `MarkNotificationAsReadAction.php`, `NotificationModuleIntegrationTest.php` | - | NONE |
| **INT-NOTI-003** | Xóa thông báo cá nhân | Client (Student/Mod) | `Notifications` | `id`, kiểm tra quyền sở hữu recipient | `DELETE /thong-bao/{id}` | `VERIFIED` | `NotificationController@destroy`, `NotificationModuleIntegrationTest.php` | - | NONE |
| **INT-NOTI-004** | Thông báo kết quả kiểm duyệt tài liệu (Duyệt/Từ chối) | `Moderation` | `Documents` (Tác giả) | Title, message, target document link | Domain Event ⟷ Notification Service | `IMPLEMENTED` | `DocumentApprovedNotification.php`, `DocumentRejectedNotification.php` | **GAP-HIGH-02** | **HIGH** |
| **INT-NOTI-005** | Thông báo tác vụ xử lý cho Cán bộ (Work Items) | `Bounties` / `Moderation` | `Mod/Admin` (Chuông) | Tác vụ cần làm: Duyệt giải ngân, duyệt tài liệu mới | Event ⟷ Database Channel | `VERIFIED` | `BountyPayoutApprovalRequiredNotification.php`, `NotificationModuleIntegrationTest.php` | **GAP-HIGH-02** | **HIGH** |
| **INT-NOTI-006** | Thông báo chế tài vi phạm & Tạm khóa tài khoản | `Moderation` | `Identity` (Vi phạm) | Nội dung vi phạm, mức cảnh báo, số lần vi phạm | Laravel Notification direct | `IMPLEMENTED` | `UserViolationSanctionedNotification.php` (Test fail do GAP-CRIT-01) | **GAP-CRIT-01** | **CRITICAL** |
| **INT-NOTI-007** | Thông báo hoàn tiền đền bù Clawback & Thu hồi điểm | `Moderation` | `Bounties` (SV A & B) | Số điểm thu hồi / hoàn trả, lý do vi phạm | Direct `$user->notify(new ...)` | `IMPLEMENTED` | `BountyPayoutClawbackRefundNotification.php`, `BountyPayoutClawedBackNotification.php` | **GAP-HIGH-02** | **HIGH** |

---

## 6. PHÂN NHÓM F: PHÂN QUYỀN RBAC & TOÀN VẸN DỮ LIỆU (AUTHORIZATION & DATA INTEGRITY)

| ID | Nghiệp vụ | Module khởi tạo | Module tiếp nhận | Dữ liệu giao tiếp | Cơ chế giao tiếp | Trạng thái | Bằng chứng thực tế | Gap liên quan | Mức độ |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: | :--- | :---: | :---: |
| **INT-AUTH-001** | Bảo vệ tuyến đường qua RoleMiddleware RBAC | `Core` (Kernel) | Mọi Module | Token session, role enum (`VaiTroNguoiDung`) | Middleware `role:QUAN_TRI_VIEN,KIEM_DUYET_VIEN` | `VERIFIED` | `RoleMiddleware.php`, `AdminRouteAuthorizationTest.php` | - | NONE |
| **INT-AUTH-002** | Chặn tài khoản bị tạm khóa / khóa vĩnh viễn | `Identity` | `Student/Alumni` | `trang_thai IN ('TAM_KHOA', 'KHOA_VINH_VIEN')` | Middleware kiểm tra trạng thái hoạt động | `VERIFIED` | `EnsureAccountIsActive.php`, `AdminUserManagementTest.php` | - | NONE |
| **INT-AUTH-003** | Chặn truy cập tài liệu chưa duyệt (AUD-H02) | `Documents` / `Bounties` | Khách / Sinh viên khác | HTTP 403 Forbidden nếu không phải Tác giả / Admin / Mod | `GetDocumentDetailQuery.php`, `PreviewDocumentAction.php` | `VERIFIED` | `DocumentContributionBountyIntegrationTest.php` | - | NONE |
| **INT-AUTH-004** | Bảo vệ phân quyền Thùng rác (Mod chỉ xem, Admin sửa/xóa) | `Moderation` | `Administration` | Chặn HTTP 403 đối với Mod khi khôi phục hoặc xóa vĩnh viễn | Route Group Middleware `role:QUAN_TRI_VIEN` | `VERIFIED` | `app/Modules/Moderation/routes.php:64`, `AdminRouteAuthorizationTest.php` | - | NONE |
| **INT-AUTH-005** | Khóa an toàn dữ liệu danh mục môn học | `AcademicCatalog` | `Catalog` / `Documents` | Không cho vô hiệu hóa/xóa môn đã có tài liệu, review, bounty | `AcademicCatalogController.php`, `AcademicCatalogTest.php` | `VERIFIED` | `AcademicCatalogTest.php` | - | NONE |

---

## 7. PHÂN NHÓM G: GIAO DIỆN DÙNG CHUNG (SHARED UI & FRONTEND INTEGRATION)

| ID | Nghiệp vụ | Module khởi tạo | Module tiếp nhận | Dữ liệu giao tiếp | Cơ chế giao tiếp | Trạng thái | Bằng chứng thực tế | Gap liên quan | Mức độ |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: | :--- | :---: | :---: |
| **INT-UI-001** | Đồng bộ Header Chuông thông báo & Dropdown | `Student/Alumni` (FE) | `Notifications` (BE) | `unread_count`, mảng thông báo rút gọn | Inertia Props / Axios Polling `/thong-bao` | `VERIFIED` | `StudentHeader.jsx`, `NotificationDropdown.jsx` | - | NONE |
| **INT-UI-002** | Modal nộp bài Bounty 2 tabs & Tiền kiểm SHA-256 | `Bounties` (FE) | `Documents` (BE) | Tab Kho cá nhân / Tab Upload máy tính | `FulfillRequestModal.jsx`, Web Crypto SHA-256 | `VERIFIED` | `FulfillRequestModal.jsx`, `DocumentContributionBountyIntegrationTest.php` | - | NONE |
| **INT-UI-003** | Trạng thái Badge & Nút Xác nhận trên Wishlist Detail | `Bounties` (FE) | `Moderation` (BE) | Hiển thị "Chờ Admin duyệt giải ngân", disable nếu chưa duyệt | Inertia View: `Wishlist/Show.jsx` | `VERIFIED` | `Wishlist/Show.jsx:410-450`, `DocumentContributionBountyIntegrationTest.php` | GAP-HIGH-03 | HIGH |
| **INT-UI-004** | Modal Báo cáo vi phạm tài liệu & Đánh giá môn | `Student/Alumni` (FE) | `Moderation` (BE) | Lý do vi phạm, mô tả, đính kèm tên giảng viên | `DocumentReportModal.jsx`, `CourseReviews.jsx` | `VERIFIED` | `DocumentReportModal.jsx`, `CourseReviews.jsx:77` | GAP-MED-01 | MEDIUM |
| **INT-UI-005** | Modal Gửi yêu cầu gỡ tài liệu sở hữu cộng đồng | `Documents` (FE) | `Moderation` (BE) | `reason`, cảnh báo điều kiện $\ge 10$ lượt tải | `Documents/Show.jsx` (Modal Gỡ tài liệu) | `VERIFIED` | `Documents/Show.jsx`, `DocumentRemovalRequestTest.php` | GAP-CRIT-02 | CRITICAL |
| **INT-UI-006** | Màn hình Ví điểm & Hiển thị 5 loại giao dịch biến động | `Bounties` (FE) | `Identity` (BE) | Số dư khả dụng, tạm giữ, lịch sử biến động điểm | Inertia View: `Wallet/Index.jsx` | `VERIFIED` | `Wallet/Index.jsx`, `Phase2DashboardTest.php` | - | NONE |

