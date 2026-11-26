# BẢNG MA TRẬN ĐỐI CHIẾU YÊU CẦU VÀ TRIỂN KHAI THỰC TẾ
## REQUIREMENT TO IMPLEMENTATION TRACEABILITY MATRIX (RTM)
**Vị trí:** `docs/knowledge/01_REQUIREMENT_IMPLEMENTATION_TRACEABILITY.md`  
**Ngày cập nhật:** 11/11/2026  
**Hệ thống phân loại trạng thái chuẩn hóa:**
- `IMPLEMENTED & VERIFIED`: Đã triển khai trong source code và có test tự động hoặc bằng chứng UI kiểm chứng đạt.
- `IMPLEMENTED, NOT FULLY VERIFIED`: Có code hoàn chỉnh nhưng thiếu test bao phủ toàn bộ các nhánh biên.
- `PARTIALLY IMPLEMENTED`: Mới triển khai một phần, còn thiếu mắt xích giao diện hoặc backend.
- `DESIGNED ONLY`: Có đặc tả trong tài liệu thiết kế nhưng chưa tìm thấy mã nguồn thực thi.
- `PROPOSED`: Mới chỉ là ý tưởng đề xuất, chưa thành thiết kế kỹ thuật chính thức.
- `DEPRECATED`: Đã bị thay thế hoặc hủy bỏ bởi quyết định kiến trúc mới hơn.

---

## 1. PHÂN HỆ ĐỊNH DANH & PHÂN QUYỀN (IDENTITY & ACCESS MANAGEMENT - IAM)

| ID | Yêu cầu nghiệp vụ | Tài liệu nguồn | Thành phần Code thực tế | Bộ Test xác minh | Trạng thái | Khoảng trống / Ghi chú |
|:---|:---|:---|:---|:---|:---:|:---|
| **REQ-IAM-01** | **Định danh sinh viên qua dữ liệu thô (Pre-provisioned IAM)**: Không cho sinh viên đăng ký tự do; nạp trước danh sách thô vào CSDL. | `TaiLieuYeuCau.md:72`<br>`ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:49` | `app/Modules/Identity/Application/Actions/AuthenticateUserAction.php`<br>`app/Models/DanhSachSinhVienRaw.php` | `tests/Feature/IdentityAuthComprehensiveTest.php`<br>`tests/Feature/AdminUserManagementTest.php` | **IMPLEMENTED & VERIFIED** | Chặn toàn bộ email đuôi `@student.agu.edu.vn` tại form đăng ký công khai. |
| **REQ-IAM-02** | **Cưỡng chế đổi mật khẩu lần đầu (First-time Password)**: Sinh viên đăng nhập lần đầu bị chặn các chức năng khác cho đến khi đổi mật khẩu mới. | `TaiLieuYeuCau.md:73`<br>`ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:54` | `app/Http/Middleware/EnsurePasswordChanged.php`<br>`app/Modules/Identity/Presentation/Http/Controllers/FirstTimePasswordController.php` | `tests/Feature/IdentityAuthComprehensiveTest.php` | **IMPLEMENTED & VERIFIED** | Đổi xong chuyển `trang_thai` sang `DANG_HOAT_DONG` và tự động cấp ví 100 điểm. |
| **REQ-IAM-03** | **Đăng ký KYC Cựu sinh viên (Alumni KYC)**: Cựu sinh viên nộp hồ sơ gồm năm tốt nghiệp và ảnh minh chứng bằng cấp. | `TaiLieuYeuCau.md:75`<br>`KYC_ALUMNI_WORKFLOW_REPORT.md:15` | `app/Modules/Identity/Application/Actions/SubmitAlumniKycAction.php`<br>`app/Models/YeuCauXacThuc.php` | `tests/Feature/IdentityKycComprehensiveTest.php` | **IMPLEMENTED & VERIFIED** | Hồ sơ chuyển sang `CHO_DUYET`. Tài khoản ở trạng thái `CHUA_XAC_THUC`. |
| **REQ-IAM-04** | **Bảo vệ PII minh chứng KYC bằng Signed URL**: Ảnh minh chứng cựu sinh viên lưu trong thư mục nhạy cảm, chỉ xem qua link có chữ ký số. | `TaiLieuYeuCau.md:107`<br>`ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:58` | `app/Modules/Identity/Infrastructure/Storage/PrivateKycStorage.php`<br>`app/Modules/Identity/Presentation/Http/Controllers/KycController.php` | `tests/Feature/KycQueueTest.php` | **IMPLEMENTED & VERIFIED** | Đường dẫn lưu tại `storage/app/private/kyc/`. Thời hạn Signed URL là 30 phút. |
| **REQ-IAM-05** | **Phân cấp Quản trị viên (Admin Level)**: Phân định Admin thường (`THUONG`) và Super Admin (`TOI_CAO`), chống tự khóa tài khoản của chính mình. | `TaiLieuYeuCau.md:113`<br>`ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:81` | `app/Modules/Administration/Domain/Services/UserAccountRuleEvaluator.php`<br>`app/Modules/Administration/Application/Actions/UpdateUserPermissionsAction.php` | `tests/Feature/AdminUserManagementTest.php`<br>`tests/Feature/AdminRouteAuthorizationTest.php` | **IMPLEMENTED & VERIFIED** | Chặn tự hạ quyền Super Admin cuối cùng của hệ thống. |
| **REQ-IAM-06** | **Chế tài xử phạt vi phạm lũy tiến**: Tự động trừ điểm uy tín và tạm khóa tài khoản khi có nhiều vi phạm nội dung. | `ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:120` | `app/Modules/Moderation/Domain/Services/UserViolationPenaltyEvaluator.php`<br>`app/Modules/Administration/Application/Actions/RecordUserViolationAction.php` | `tests/Feature/ModerationViolationPenaltyTest.php` | **IMPLEMENTED & VERIFIED** | Điểm uy tín giảm dần theo các mức phạt quy định trong hệ thống. |

---

## 2. PHÂN HỆ DANH MỤC HỌC THUẬT (ACADEMIC CATALOG)

| ID | Yêu cầu nghiệp vụ | Tài liệu nguồn | Thành phần Code thực tế | Bộ Test xác minh | Trạng thái | Khoảng trống / Ghi chú |
|:---|:---|:---|:---|:---|:---:|:---|
| **REQ-CAT-01** | **Phân cấp Khoa → Chuyên ngành → Môn học**: Quản lý cây phân cấp học thuật theo chuẩn Đại học An Giang. | `TaiLieuYeuCau.md:80`<br>`TaiLieuThietKe.md:55` | `app/Models/Khoa.php`<br>`app/Models/ChuyenNganh.php`<br>`app/Models/MonHoc.php` | `tests/Feature/AcademicCatalogTest.php` | **IMPLEMENTED & VERIFIED** | Quan hệ nhiều-nhiều giữa Môn học và Chuyên ngành qua bảng trung gian. |
| **REQ-CAT-02** | **Tra cứu danh mục môn học công khai**: Tìm kiếm theo mã học phần (COS101), tên môn, lọc theo khoa. | `TaiLieuYeuCau.md:28`<br>`PHASE_1_COURSE_CATALOG_SCOPE.md` | `app/Modules/AcademicCatalog/Application/Queries/GetPublicCoursesQuery.php`<br>`resources/js/Pages/Courses/Index.jsx` | `tests/Feature/PublicAcademicCatalogTest.php`<br>`tests/Feature/Phase1CourseCatalogTest.php` | **IMPLEMENTED & VERIFIED** | Đã khắc phục lỗi hiển thị nhầm danh sách tài liệu từ sau Phase 1 Catalog. |
| **REQ-CAT-03** | **Trang chi tiết môn học tích hợp đa tab**: Hiển thị thông tin môn học, tab Kho tài liệu, tab Đánh giá và tab Wishlist. | `docs/audits/COURSE_DETAIL_INTEGRATION_AUDIT.md:8` | `app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php`<br>`resources/js/Pages/Courses/Show.jsx` | `tests/Feature/Phase3aCourseDetailTest.php` | **IMPLEMENTED & VERIFIED** | Trung tâm hạt nhân liên kết 3 phân hệ chính của sinh viên. |

---

## 3. PHÂN HỆ QUẢN LÝ TÀI LIỆU & LỌC TRÙNG (DOCUMENT LIFECYCLE & DEDUPLICATION)

| ID | Yêu cầu nghiệp vụ | Tài liệu nguồn | Thành phần Code thực tế | Bộ Test xác minh | Trạng thái | Khoảng trống / Ghi chú |
|:---|:---|:---|:---|:---|:---:|:---|
| **REQ-DOC-01** | **Lọc trùng lặp nhị phân 100% bằng SHA-256 (Lớp 1)**: Ngăn chặn upload file trùng mã băm trong cùng môn học. | `TaiLieuYeuCau.md:82`<br>`TaiLieuThietKe.md:106` | `app/Modules/Documents/Domain/Services/DocumentDeduplicationEvaluator.php`<br>`app/Modules/Documents/Application/Actions/UploadDocumentAction.php` | `tests/Feature/DocumentManagementComprehensiveTest.php`<br>`tests/Feature/DocumentContributionBountyIntegrationTest.php` | **IMPLEMENTED & VERIFIED** | Băm trực tiếp bằng `hash_file('sha256', ...)`. Báo lỗi 409 Conflict nếu trùng. |
| **REQ-DOC-02** | **Lọc tương đồng nội dung SimHash / Cosine Similarity (Lớp 2)**: Trích xuất text, phát hiện tương đồng >85%. | `TaiLieuYeuCau.md:83` | *Chưa tìm thấy mã nguồn xử lý SimHash / Cosine* | *Không có test* | **DESIGNED ONLY** | Chưa triển khai thư viện bóc tách text và thuật toán SimHash trong Background Queue. |
| **REQ-DOC-03** | **Xem trước trực tuyến (Protected PDF Stream)**: Xem file PDF an toàn trên trình duyệt mà không lộ đường dẫn máy chủ. | `TaiLieuYeuCau.md:84`<br>`TaiLieuKyThuat.md:72` | `app/Modules/Documents/Infrastructure/Storage/DocumentFileStorage.php`<br>`app/Modules/Documents/Application/Actions/PreviewDocumentAction.php` | `tests/Feature/DocumentManagementComprehensiveTest.php` | **IMPLEMENTED & VERIFIED** | Stream trực tiếp qua `Storage::response()` với header `inline`. |
| **REQ-DOC-04** | **Kiểm soát định dạng MIME & Chống file thực thi**: Chặn mã độc, file .exe, .sh, .bat, giới hạn dung lượng. | `TaiLieuYeuCau.md:112` | `app/Modules/Documents/Presentation/Http/Requests/UploadDocumentRequest.php`<br>`app/Modules/Bounties/Presentation/Http/Requests/SubmitReplacementRequest.php` | `tests/Feature/DocumentContributionBountyIntegrationTest.php` | **IMPLEMENTED & VERIFIED** | Chỉ chấp nhận pdf, docx, pptx, zip; giới hạn kích thước theo cấu hình hệ thống. |
| **REQ-DOC-05** | **Quản lý phiên bản tài liệu (Versioning)**: Cho phép tải lên bản cập nhật (v1.1, v2.0) liên kết với `ma_tai_lieu_goc`. | `TaiLieuYeuCau.md:85` | `app/Modules/Documents/Application/Actions/CreateDocumentVersionAction.php`<br>`app/Models/TaiLieu.php` | `tests/Feature/DocumentManagementComprehensiveTest.php` | **IMPLEMENTED & VERIFIED** | Quản lý quan hệ cha-con giữa các phiên bản tài liệu. |

---

## 4. PHÂN HỆ BẢO VỆ DỮ LIỆU & CHỐNG PHÁ HOẠI (DATA PROTECTION & ANTI-SABOTAGE)

| ID | Yêu cầu nghiệp vụ | Tài liệu nguồn | Thành phần Code thực tế | Bộ Test xác minh | Trạng thái | Khoảng trống / Ghi chú |
|:---|:---|:---|:---|:---|:---:|:---|
| **REQ-PRO-01** | **Ràng buộc Quyền xóa của Tác giả (24h & 0 tải)**: Tác giả chỉ được tự xóa mềm trong 24h đầu nếu chưa ai tải. | `TaiLieuYeuCau.md:89` | `app/Modules/Documents/Domain/Services/DocumentAuthorRemovalEvaluator.php`<br>`app/Modules/Documents/Application/Actions/AuthorSoftDeleteDocumentAction.php` | `tests/Feature/DocumentManagementComprehensiveTest.php` | **IMPLEMENTED & VERIFIED** | Nếu quá 24h hoặc `luot_tai > 0`, quyền tự xóa bị khóa. |
| **REQ-PRO-02** | **Quyền sở hữu cộng đồng (Community Ownership)**: Tài liệu >=10 tải trở thành tài sản chung, tác giả phải làm đơn xin gỡ. | `TaiLieuYeuCau.md:39`<br>`TaiLieuYeuCau.md:89` | `app/Modules/Documents/Domain/Services/DocumentAuthorRemovalEvaluator.php`<br>`app/Modules/Documents/Application/Actions/SubmitRemovalRequestAction.php` | `tests/Feature/DocumentRemovalRequestTest.php` | **IMPLEMENTED & VERIFIED** | Đơn xin gỡ được chuyển vào hàng đợi kiểm duyệt của Moderator/Admin. |
| **REQ-PRO-03** | **Vùng chết xóa tài liệu (Author Deadlock Zone)**: Tác giả có tài liệu 1-9 lượt tải không thể tự xóa và không thể xin gỡ. | `CROSS_MODULE_AUDIT_SUMMARY.md:76` | `app/Modules/Documents/Domain/Services/DocumentAuthorRemovalEvaluator.php` | *Đã có test chỉ ra lỗ hổng* | **PARTIALLY IMPLEMENTED / GAP** | Nghiệp vụ thực tế đang bị kẹt giữa 2 điều kiện. Cần đề xuất nới lỏng điều kiện gửi đơn gỡ. |
| **REQ-PRO-04** | **Cơ chế Xóa mềm & Thùng rác 60 ngày**: Toàn bộ thao tác xóa đều cập nhật `deleted_at`; lưu vết thùng rác 60 ngày. | `TaiLieuYeuCau.md:90`<br>`TASK-8-TRASH-MANAGEMENT-REPORT.md` | `app/Models/LichSuThungRac.php`<br>`app/Modules/Administration/Application/Actions/PurgeExpiredTrashAction.php` | `tests/Feature/AdminStorageManagementTest.php` | **IMPLEMENTED & VERIFIED** | Chỉ Super Admin mới có quyền dọn dẹp hoặc khôi phục tệp trong thùng rác. |
| **REQ-PRO-05** | **Hàng đợi Báo cáo vi phạm (Report Queue)**: Tiếp nhận báo cáo vi phạm nội dung, bản quyền, mã độc. | `TaiLieuYeuCau.md:91`<br>`REPORT_VIOLATION_MODERATION.md` | `app/Modules/Moderation/Application/Actions/ResolveViolationReportAction.php`<br>`app/Models/BaoCaoViPham.php` | `tests/Feature/DocumentReportMonitoringTest.php` | **IMPLEMENTED & VERIFIED** | Hỗ trợ các thao tác kiểm duyệt: Bác bỏ, Tạm ẩn kiểm tra, hoặc Xóa mềm. |

---

## 5. PHÂN HỆ SĂN THƯỞNG & DANH SÁCH MONG MUỐN (BOUNTY & WISHLIST)

| ID | Yêu cầu nghiệp vụ | Tài liệu nguồn | Thành phần Code thực tế | Bộ Test xác minh | Trạng thái | Khoảng trống / Ghi chú |
|:---|:---|:---|:---|:---|:---:|:---|
| **REQ-BTY-01** | **Tạo yêu cầu & Đóng băng điểm (Escrow Holding)**: Khóa điểm khả dụng vào điểm tạm giữ, chống âm ví, Idempotency key. | `TaiLieuYeuCau.md:95`<br>`student-alumni-bounty-design.md:30` | `app/Modules/Bounties/Domain/Services/ReputationEvaluator.php`<br>`app/Modules/Bounties/Application/Actions/CreateBountyRequestAction.php` | `tests/Feature/WishlistBountyTest.php` | **IMPLEMENTED & VERIFIED** | Ghi sổ cái `GiaoDichDiem` loại `TAM_GIU`. Khóa hàng bằng `lockForUpdate()`. |
| **REQ-BTY-02** | **Đóng góp tài liệu giải quyết Bounty (2 phương thức)**: Chọn file có sẵn đã duyệt (Method A) hoặc upload file mới (Method B). | `student-alumni-bounty-analysis.md:89` | `app/Modules/Bounties/Application/Actions/FulfillBountyRequestAction.php` | `tests/Feature/DocumentContributionBountyIntegrationTest.php` | **IMPLEMENTED & VERIFIED** | Method B tạo tài liệu ở trạng thái `CHO_DUYET` chờ Mod duyệt trước. |
| **REQ-BTY-03** | **Người tạo nghiệm thu bài đóng góp**: Người yêu cầu có quyền Xác nhận (Accept) hoặc Từ chối (Reject) bài nộp. | `student-alumni-bounty-design.md:75` | `app/Modules/Bounties/Application/Actions/ConfirmBountyFulfillmentAction.php`<br>`app/Modules/Bounties/Application/Actions/RejectBountyFulfillmentAction.php` | `tests/Feature/PostBountyCorrectionWorkflowTest.php` | **IMPLEMENTED & VERIFIED** | Xác nhận chuyển Bounty sang `CHO_GIAI_NGAN`. Từ chối ghi nhận lý do rõ ràng. |
| **REQ-BTY-04** | **Phê duyệt Giải ngân 2 bước (Admin/Mod Payout Approval)**: Ban Quản trị thẩm định lần cuối trước khi thực chi điểm thưởng. | `ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:37`<br>`student-alumni-bounty-design.md:110` | `app/Modules/Bounties/Application/Actions/ApproveBountyPayoutAction.php`<br>`app/Modules/Bounties/Presentation/Http/Controllers/AdminBountyPayoutController.php` | `tests/Feature/PostBountyCorrectionWorkflowTest.php` | **IMPLEMENTED & VERIFIED** | Ngăn chặn gian lận cày điểm và thông đồng giữa 2 tài khoản sinh viên. |
| **REQ-BTY-05** | **Thu hồi điểm thưởng (Bounty Clawback)**: Tự động đòi lại điểm khi tài liệu đã ăn thưởng bị gỡ do vi phạm bản quyền. | `ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:430` | `app/Modules/Moderation/Domain/Services/BountyClawbackHandler.php`<br>`app/Modules/Moderation/Application/Actions/DecideDocumentReviewAction.php` | `tests/Feature/ModerationBountyClawbackTest.php` | **IMPLEMENTED & VERIFIED** | Khấu trừ ví người nộp, hoàn lại ví người yêu cầu, ghi sổ cái `HOAN_TRA`. |
| **REQ-BTY-06** | **Chỉnh sửa sau khi hoàn thành Bounty (Correction Workflow)**: Yêu cầu sửa đổi/bổ sung tài liệu mà không mất thêm điểm. | `student-alumni-bounty-design.md:150` | `app/Modules/Bounties/Application/Actions/CreateCorrectionRequestAction.php`<br>`app/Modules/Bounties/Application/Actions/SubmitReplacementDocumentAction.php`<br>`app/Modules/Bounties/Application/Actions/ConfirmCorrectionCompletionAction.php` | `tests/Feature/PostBountyCorrectionWorkflowTest.php` | **IMPLEMENTED & VERIFIED** | Đã triển khai đầy đủ 2 bảng CSDL, 5 actions và kiểm thử 6/6 kịch bản đạt chuẩn. |

---

## 6. PHÂN HỆ ĐÁNH GIÁ MÔN HỌC (COURSE REVIEWS ENGINE)

| ID | Yêu cầu nghiệp vụ | Tài liệu nguồn | Thành phần Code thực tế | Bộ Test xác minh | Trạng thái | Khoảng trống / Ghi chú |
|:---|:---|:---|:---|:---|:---:|:---|
| **REQ-REV-01** | **Đánh giá môn học theo 3 tiêu chí trực quan**: Thang điểm 1-5 sao cho Độ khó, Tính thực tiễn và Khối lượng bài tập. | `TaiLieuYeuCau.md:101`<br>`TaiLieuThietKe.md:120` | `app/Models/DanhGiaMonHoc.php`<br>`app/Modules/Reviews/Application/Actions/StoreCourseReviewAction.php` | `tests/Feature/CourseReviewTest.php` | **IMPLEMENTED & VERIFIED** | Tính toán điểm trung bình từng tiêu chí và điểm tổng thể của môn học. |
| **REQ-REV-02** | **Ẩn danh 2 lớp bảo vệ sinh viên**: Che giấu danh tính trên UI và API; chỉ lưu `ma_nguoi_dung` tại CSDL phục vụ hậu kiểm. | `TaiLieuYeuCau.md:102` | `app/Modules/Reviews/Presentation/Http/Resources/CourseReviewResource.php` | `tests/Feature/CourseReviewTest.php` | **IMPLEMENTED & VERIFIED** | Prop truyền xuống React che giấu tên và MSSV thành "Sinh viên ẩn danh". |
| **REQ-REV-03** | **Bộ lọc từ cấm tự động & Bẻ gãy Leetspeak**: Quét và chặn các từ lăng mạ, bôi nhọ, biến thể ký tự (`0->o`, `đ.m`). | `TaiLieuYeuCau.md:103`<br>`BAO_CAO_TAI_CAU_TRUC_MODULAR_MONOLITH.md:144` | `app/Modules/Reviews/Domain/Services/ProfanityFilterEvaluator.php` | `tests/Feature/CourseReviewTest.php` | **IMPLEMENTED & VERIFIED** | Bắt lỗi validation ngay tại Form Request trước khi lưu vào CSDL. |
| **REQ-REV-04** | **Bình chọn Review hữu ích & Chống tự bình chọn**: Cho phép Upvote review hay; chặn tác giả tự vote cho bài của mình. | `TaiLieuKyThuat.md:89` | `app/Modules/Reviews/Domain/Services/CourseReviewRuleEvaluator.php`<br>`app/Modules/Reviews/Application/Actions/ToggleHelpfulVoteAction.php` | `tests/Feature/CourseReviewTest.php` | **IMPLEMENTED & VERIFIED** | Tăng biến đếm và điểm uy tín cho người viết review chất lượng. |

---

## 7. PHÂN HỆ QUẢN TRỊ & HỆ THỐNG (ADMINISTRATION & SYSTEM ENGINE)

| ID | Yêu cầu nghiệp vụ | Tài liệu nguồn | Thành phần Code thực tế | Bộ Test xác minh | Trạng thái | Khoảng trống / Ghi chú |
|:---|:---|:---|:---|:---|:---:|:---|
| **REQ-ADM-01** | **Nhật ký kiểm toán nghiệp vụ bất biến (Immutable Audit Logs)**: Ghi log mọi thay đổi trạng thái quản trị, chống sửa/xóa log. | `ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:71`<br>`BAO_CAO_TASK_11_NHAT_KY_KIEM_TOAN_ADMIN.md` | `app/Models/NhatKyKiemToan.php`<br>`app/Modules/Administration/Domain/Services/AuditLogger.php` | `tests/Feature/AdminAuditLogTest.php` | **IMPLEMENTED & VERIFIED** | Model ném `DomainException` nếu có lệnh `update()` hoặc `delete()` can thiệp. |
| **REQ-ADM-02** | **Dashboard tổng quan vận hành hệ thống**: Thống kê số lượng người dùng, tài liệu, báo cáo chờ xử lý, dung lượng lưu trữ. | `BAO_CAO_TASK_10_DASHBOARD_TONG_QUAN_ADMIN.md` | `app/Modules/Administration/Application/Queries/GetAdminDashboardOverviewQuery.php`<br>`resources/js/Pages/Admin/Dashboard.jsx` | `tests/Feature/AdminDashboardOverviewTest.php` | **IMPLEMENTED & VERIFIED** | Tích hợp biểu đồ thống kê và bảng theo dõi nhanh các chỉ số vận hành. |
| **REQ-ADM-03** | **Quản lý & Giám sát dung lượng ổ đĩa (Storage Management)**: Theo dõi phân bổ dung lượng giữa các khoa, cảnh báo ngưỡng 80%, 90%. | `ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:31` | `app/Modules/Administration/Application/Queries/GetStorageOverviewQuery.php`<br>`app/Modules/Administration/Domain/Services/StorageRuleEvaluator.php` | `tests/Feature/AdminStorageManagementTest.php` | **IMPLEMENTED & VERIFIED** | Cho phép lọc file có dung lượng lớn bất thường để kiểm tra tối ưu hóa. |
| **REQ-ADM-04** | **Thao tác Hàng loạt (Bulk Actions) cho Quản trị & Kiểm duyệt**: Duyệt KYC hàng loạt, bác bỏ/lưu trữ báo cáo vi phạm hàng loạt. | `ADMIN_MODERATOR_INTEGRATION_SPECIFICATION.md:38` | `app/Modules/Moderation/Application/Actions/BulkDismissReportsAction.php`<br>`app/Modules/Moderation/Application/Actions/BulkArchiveReportsAction.php` | `tests/Feature/ModerationBulkActionsTest.php` | **IMPLEMENTED & VERIFIED** | Tiết kiệm thời gian thao tác cho cán bộ kiểm duyệt khi có lượng báo cáo lớn. |

---

## 8. PHÂN HỆ THÔNG BÁO NGHIỆP VỤ (BUSINESS NOTIFICATIONS)

| ID | Yêu cầu nghiệp vụ | Tài liệu nguồn | Thành phần Code thực tế | Bộ Test xác minh | Trạng thái | Khoảng trống / Ghi chú |
|:---|:---|:---|:---|:---|:---:|:---|
| **REQ-NTF-01** | **Hệ thống thông báo phân loại Category**: Phân định rạch ròi giữa thông báo cá nhân (`PERSONAL`) và mục cần xử lý (`WORK_ITEM`). | `NOTIFICATION_ARCHITECTURE.md`<br>`NOTIFICATION_INTEGRATION_CONTRACT.md` | `app/Modules/Notifications/Domain/Enums/NotificationCategory.php`<br>`app/Modules/Notifications/Application/Queries/GetNotificationsQuery.php` | `tests/Feature/BusinessNotificationWorkflowTest.php` | **IMPLEMENTED & VERIFIED** | Chuông thông báo hiển thị số lượng chưa đọc realtime qua polling/request. |
| **REQ-NTF-02** | **Đồng bộ bảng notifications sang Tiếng Việt**: Chuẩn hóa các trường lưu trữ trong database (`du_lieu`, `nguoi_nhan_id`, `da_doc_luc`). | `7946774 (commit)`<br>`2026_11_08_000001_chuyen_cot_bang_notifications_sang_tieng_viet.php` | `app/Modules/Notifications/Infrastructure/Notifications/SystemNotification.php` | `tests/Feature/NotificationModuleIntegrationTest.php` | **IMPLEMENTED & VERIFIED** | Đã migrate thành công và sửa các câu truy vấn Eloquent tương ứng. |
| **REQ-NTF-03** | **Bắn thông báo qua Event/Listener hướng module**: Khi có sự kiện (Nộp tài liệu, Duyệt KYC, Giải ngân), tự động gửi thông báo. | `NOTIFICATION_USE_CASE_SPECIFICATION.md` | `app/Modules/Notifications/Domain/Events/*`<br>`app/Modules/Notifications/Infrastructure/Listeners/*` | `tests/Feature/BusinessNotificationWorkflowTest.php` | **IMPLEMENTED & VERIFIED** | Tách rời logic nghiệp vụ ghi dữ liệu với logic điều phối thông báo. |

