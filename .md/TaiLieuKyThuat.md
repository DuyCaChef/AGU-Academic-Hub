# TÀI LIỆU KỸ THUẬT (TECHNICAL SPECIFICATION)
## HỆ THỐNG AGU ACADEMIC HUB - KIẾN TRÚC MODULAR MONOLITH 4 TẦNG

---

### 1. Phân ước chung & Tiêu chuẩn Kỹ thuật

* **Mô hình Kiến trúc:** Modular Monolith 4 tầng (Domain-Driven Design).
* **Giao tiếp Client - Server:** Inertia.js v2 Protocol kết hợp RESTful JSON Response khi Request yêu cầu `wantsJson()`.
* **Quy chuẩn Định danh Tuyến đường:** Hỗ trợ chuẩn hóa URL tiếng Việt thân thiện với sinh viên và đồng thời hỗ trợ các Route Aliases quốc tế chuẩn Laravel.
* **Mã phản hồi chuẩn (HTTP Status Codes):**
  * **200 OK:** Truy vấn dữ liệu, render giao diện Inertia hoặc cập nhật thành công.
  * **201 Created:** Khởi tạo tài nguyên mới thành công.
  * **302 Found / Redirect:** Chuyển hướng sau mutation thành công kèm session flash message.
  * **400 Bad Request:** Yêu cầu không hợp lệ hoặc logic nghiệp vụ bị vi phạm.
  * **401 Unauthorized:** Chưa đăng nhập hoặc phiên làm việc đã hết hạn.
  * **403 Forbidden:** Không có thẩm quyền thao tác (chặn bởi Policy / Rule Evaluator).
  * **404 Not Found:** Không tìm thấy tài nguyên trong cơ sở dữ liệu.
  * **409 Conflict:** Xung đột tài nguyên (ví dụ: phát hiện mã băm tệp tin SHA-256 trùng lặp 100%).
  * **422 Unprocessable Entity:** Lỗi xác thực dữ liệu đầu vào (Form Request Validation).
  * **429 Too Many Requests:** Vượt quá ngưỡng giới hạn tốc độ (Rate Limiting).

---

### 2. Danh mục 7 Bounded Contexts & Chi tiết 4 Tầng Kiến trúc

#### A. Phân hệ Quản trị Hệ thống (Administration Module)
* **Thư mục:** `app/Modules/Administration/`
* **Service Provider:** `AdministrationServiceProvider`
* **Chi tiết 4 tầng:**
  * **Presentation:**
    * `AdminStorageController`: Quản lý thống kê dung lượng ổ đĩa và dọn dẹp thùng rác hết hạn.
    * `AdminUserController`: Quản lý danh sách người dùng, cập nhật trạng thái (khóa/mở khóa) và phân quyền quản trị.
    * `AdminDashboardController`: Tổng hợp chỉ số hệ thống toàn diện cho bảng điều khiển trung tâm.
  * **Application:**
    * Queries: `GetStorageStatsQuery` (bọc cache 15 phút), `GetUnusuallyLargeFilesQuery`, `GetAdminUserListQuery`, `GetUserDetailQuery`, `GetAdminDashboardStatsQuery`.
    * Actions: `PurgeExpiredTrashAction` (xóa tệp disk và ghi audit log `lich_su_thung_rac` trong transaction), `UpdateUserStatusAction`, `AssignUserRoleAction`, `UpdateUserPermissionsAction`.
  * **Domain:**
    * `StorageRuleEvaluator`: Đánh giá ngưỡng cảnh báo dung lượng vàng (80%) và đỏ (90%), xác định tệp rác quá hạn lưu trữ 30 ngày.
    * `UserAccountRuleEvaluator`: Ràng buộc tài khoản quản trị (chặn tự khóa chính mình, chặn hạ cấp Super Admin cuối cùng).
    * `ByteSize`: Value Object chuyển đổi đơn vị lưu trữ (Byte, KB, MB, GB).
  * **Infrastructure:**
    * `StorageRepository`: Truy vấn nhóm theo thời gian tương thích cả MySQL (`DATE_FORMAT`) lẫn SQLite (`strftime`).
    * `AdminUserRepository`: Truy vấn thông tin tài khoản và nhật ký lịch sử quyền.
    * `StorageAuditDisk`: Tương tác tệp tin vật lý trên disk và quản lý dọn dẹp an toàn.

| Phương thức | Endpoint | Route Name | Phân quyền | Mô tả chức năng |
|:---|:---|:---|:---|:---|
| GET | `/quan-tri` | `admin.dashboard` | Admin/Mod | Bảng điều khiển thống kê tổng quan hệ thống |
| GET | `/quan-tri/luu-tru` | `admin.storage.index` | Super Admin | Thống kê dung lượng ổ đĩa, phân bố chuyên mục và tăng trưởng |
| DELETE | `/quan-tri/luu-tru/thung-rac-het-han` | `admin.storage.purge-expired-trash` | Super Admin | Dọn dẹp vĩnh viễn tệp tin rác quá hạn 30 ngày |
| GET | `/quan-tri/nguoi-dung` | `admin.users.index` | Admin | Quản lý danh sách người dùng, lọc vai trò và trạng thái |
| GET | `/quan-tri/nguoi-dung/{maNguoiDung}` | `admin.users.show` | Admin | Xem chi tiết thông tin và lịch sử biến động tài khoản |
| PUT | `/quan-tri/nguoi-dung/{maNguoiDung}/trang-thai` | `admin.users.update-status` | Super Admin | Khóa hoặc mở khóa tài khoản người dùng |
| PUT | `/quan-tri/nguoi-dung/{maNguoiDung}/phan-quyen` | `admin.users.assign-role` | Super Admin | Phân quyền quản trị và cấp độ quản trị viên |

---

#### B. Phân hệ Danh mục Đào tạo (AcademicCatalog Module)
* **Thư mục:** `app/Modules/AcademicCatalog/`
* **Service Provider:** `AcademicCatalogServiceProvider`
* **Chi tiết 4 tầng:**
  * **Presentation:**
    * `AcademicCatalogController`: Quản trị danh mục Khoa, Chuyên ngành và Môn học.
    * `FacultyController`: Xem danh sách Khoa và các môn học trực thuộc dành cho sinh viên.
    * `CourseController`: Tra cứu, tìm kiếm môn học và xem chi tiết học phần.
  * **Application:**
    * Actions: `StoreFacultyAction`, `UpdateFacultyAction`, `ToggleFacultyStatusAction`, `StoreMajorAction`, `UpdateMajorAction`, `ToggleMajorStatusAction`, `StoreCourseAction`, `UpdateCourseAction`, `ToggleCourseStatusAction`.
    * Queries: `GetAdminCatalogQuery`, `GetPublicFacultiesQuery`, `GetFacultyCoursesQuery`, `GetPublicCoursesQuery`, `GetCourseDetailQuery`.
  * **Domain:**
    * `AcademicCatalogRuleEvaluator`: Ràng buộc tính toàn vẹn khi vô hiệu hóa Khoa (tự động khóa môn học liên quan, chặn tạo mới môn học thuộc Khoa đã tắt).
  * **Infrastructure:**
    * `FacultyRepository`, `MajorRepository`, `CourseRepository`.

| Phương thức | Endpoint | Route Name | Phân quyền | Mô tả chức năng |
|:---|:---|:---|:---|:---|
| GET | `/khoa` | `faculties.index` | Public | Danh sách các Khoa/Bộ môn đang hoạt động |
| GET | `/khoa/{id}/mon-hoc` | `faculties.courses` | Public | Danh sách môn học thuộc một Khoa cụ thể |
| GET | `/mon-hoc` | `courses.index` | Public | Tra cứu danh mục môn học (hỗ trợ tìm kiếm theo mã/tên) |
| GET | `/mon-hoc/{course_code}` | `courses.show` | Public | Thông tin chi tiết học phần, số tín chỉ và điểm xếp hạng |
| GET | `/quan-tri/danh-muc` | `admin.catalog.index` | Admin | Giao diện quản trị toàn bộ danh mục đào tạo |
| POST | `/quan-tri/danh-muc/khoa` | `admin.catalog.faculties.store` | Admin | Thêm mới Khoa đào tạo |
| PUT | `/quan-tri/danh-muc/khoa/{maKhoa}` | `admin.catalog.faculties.update` | Admin | Chỉnh sửa thông tin Khoa |
| PUT | `/quan-tri/danh-muc/khoa/{maKhoa}/trang-thai` | `admin.catalog.faculties.toggle` | Admin | Bật/tắt trạng thái hoạt động của Khoa |
| POST | `/quan-tri/danh-muc/mon-hoc` | `admin.catalog.courses.store` | Admin | Tạo môn học mới |
| PUT | `/quan-tri/danh-muc/mon-hoc/{maMonHoc}` | `admin.catalog.courses.update` | Admin | Cập nhật thông tin môn học |
| PUT | `/quan-tri/danh-muc/mon-hoc/{maMonHoc}/trang-thai` | `admin.catalog.courses.toggle` | Admin | Bật/tắt trạng thái hoạt động của môn học |

---

#### C. Phân hệ Định danh & Xác thực (Identity Module)
* **Thư mục:** `app/Modules/Identity/`
* **Service Provider:** `IdentityServiceProvider`
* **Chi tiết 4 tầng:**
  * **Presentation:**
    * `AuthenticatedSessionController`: Xử lý đăng nhập, đối soát đăng nhập lần đầu và đăng xuất.
    * `ProfileController`: Xem, cập nhật hồ sơ cá nhân và xóa tài khoản.
    * `RegisteredUserController`: Tiếp nhận đăng ký sinh viên mới.
    * `PasswordController`, `PasswordResetLinkController`, `NewPasswordController`, `ConfirmablePasswordController`.
    * `EmailVerificationController`: Xử lý lời nhắc và xác minh email.
    * `KycController`: Đăng ký KYC cựu sinh viên, hàng đợi duyệt KYC và xem ảnh bảo mật qua Signed URL.
    * `FirstTimePasswordController`: Đổi mật khẩu bắt buộc kích hoạt tài khoản sinh viên.
    * Form Requests: `LoginRequest`, `ProfileUpdateRequest`, `RegisterUserRequest`, `AlumniKycRequest`.
  * **Application:**
    * Actions: `AuthenticateUserAction`, `LogoutUserAction`, `RegisterStudentAction`, `UpdateUserPasswordAction`, `UpdateUserProfileAction`, `DeleteUserAccountAction`, `SubmitAlumniKycAction`, `ApproveKycAction`, `RejectKycAction`, `ChangeFirstTimePasswordAction`.
    * Queries: `GetUserProfileQuery`, `GetKycQueueQuery`, `GetAlumniRegistrationFormQuery`.
  * **Domain:**
    * `IdentityRuleEvaluator`: Xác thực email AGU, chặn tài khoản tạm khóa, chặn tài khoản cựu sinh viên chờ duyệt KYC, nhận diện lần đầu đăng nhập.
    * `AlumniKycRuleEvaluator`: Thẩm định điều kiện phê duyệt hoặc từ chối hồ sơ cựu sinh viên.
    * `KycProofFile`: Value Object thẩm định tính an toàn và hợp lệ của tệp ảnh minh chứng tốt nghiệp.
  * **Infrastructure:**
    * `IdentityRepository`: Tìm kiếm người dùng, đối soát dữ liệu thô `danh_sach_sinh_vien_raw`, tra cứu mã khoa.
    * `KycRepository`: Lưu trữ hồ sơ xác thực và cập nhật trạng thái trong database transaction.
    * `PrivateKycStorage`: Lưu tệp tin tại `storage/app/private/kyc/` và tạo Temporary Signed URL thời hạn 30 phút.

| Phương thức | Endpoint | Route Name | Phân quyền | Mô tả chức năng |
|:---|:---|:---|:---|:---|
| GET | `/dang-nhap` | `login` | Guest | Giao diện đăng nhập hệ thống |
| POST | `/dang-nhap` | `login` | Guest | Xác thực đăng nhập (hỗ trợ Email trường hoặc MSSV) |
| POST | `/dang-xuat` | `logout` | Auth | Đăng xuất người dùng và hủy phiên làm việc |
| GET | `/dang-ky` | `register` | Guest | Giao diện đăng ký tài khoản sinh viên |
| POST | `/dang-ky` | `register` | Guest | Xử lý đăng ký tài khoản sinh viên mới |
| GET | `/ho-so` | `profile.edit` | Auth | Giao diện quản lý hồ sơ cá nhân |
| PATCH | `/ho-so` | `profile.update` | Auth | Cập nhật thông tin cá nhân (họ tên, email) |
| DELETE | `/ho-so` | `profile.destroy` | Auth | Xóa tài khoản người dùng kèm xác thực mật khẩu |
| GET | `/mat-khau-lan-dau` | `password.change` | Auth | Giao diện cưỡng chế đổi mật khẩu lần đầu kích hoạt tài khoản |
| POST | `/mat-khau-lan-dau` | `password.change.update` | Auth | Cập nhật mật khẩu mới và kích hoạt tài khoản |
| GET | `/cuu-sinh-vien/dang-ky` | `alumni.register` | Guest | Biểu mẫu nộp hồ sơ KYC dành cho Cựu sinh viên |
| POST | `/cuu-sinh-vien/dang-ky` | `alumni.register.store` | Guest | Gửi hồ sơ và tải lên minh chứng tốt nghiệp |
| GET | `/kyc/xem-anh/{ma_xac_thuc}` | `kyc.preview` | Signed URL | Xem trước ảnh minh chứng KYC an toàn (sống 30 phút) |
| GET | `/quan-tri/duyet-kyc` | `admin.kyc.index` | Admin/Mod | Danh sách hàng đợi hồ sơ KYC chờ duyệt |
| PUT | `/quan-tri/duyet-kyc/{id}/duyet` | `admin.kyc.approve` | Admin/Mod | Phê duyệt hồ sơ KYC và kích hoạt tài khoản cựu sinh viên |
| PUT | `/quan-tri/duyet-kyc/{id}/tu-choi` | `admin.kyc.reject` | Admin/Mod | Từ chối hồ sơ KYC kèm lý do phản hồi |

---

#### D. Phân hệ Quản lý Tài liệu (Documents Module)
* **Thư mục:** `app/Modules/Documents/`
* **Service Provider:** `DocumentsServiceProvider`
* **Chi tiết 4 tầng:**
  * **Presentation:**
    * `DocumentController`: Điều phối đăng tải, xem trước, tải về, bình chọn upvote, phiên bản và xóa mềm.
  * **Application:**
    * Actions: `UploadDocumentAction`, `CreateDocumentVersionAction`, `DownloadDocumentAction`, `PreviewDocumentAction`, `UpvoteDocumentAction`, `AuthorSoftDeleteDocumentAction`, `SubmitRemovalRequestAction`.
    * Queries: `CheckDuplicateDocumentQuery`, `GetDocumentDetailQuery`.
  * **Domain:**
    * `DocumentDeduplicationEvaluator`: Quy trình kiểm tra trùng lặp 2 lớp (Lớp 1 SHA-256 Hash nhị phân, Lớp 2 SimHash độ tương đồng tiêu đề/văn bản).
    * `DocumentAuthorRemovalEvaluator`: Ràng buộc tác giả chỉ được xóa mềm trong 24 giờ đầu và khi số lượt tải bằng 0.
  * **Infrastructure:**
    * `DocumentRepository`: Truy vấn dữ liệu tài liệu, tăng lượt tải, toggle upvote.
    * `DocumentFileStorage`: Lưu trữ và tạo luồng đọc file PDF an toàn (Stream Response) chống lộ đường dẫn disk máy chủ.

| Phương thức | Endpoint | Route Name | Phân quyền | Mô tả chức năng |
|:---|:---|:---|:---|:---|
| GET | `/mon-hoc/{code}/tai-lieu` | `courses.documents` | Verified User | Danh sách tài liệu theo môn học kèm bộ lọc và phân trang |
| GET | `/tai-lieu/dang-tai` | `documents.create` | Verified User | Giao diện đăng tải tài liệu học tập mới |
| POST | `/tai-lieu/dang-tai` | `documents.store` | Verified User | Tải lên tài liệu mới (kiểm tra trùng lặp 2 lớp) |
| POST | `/tai-lieu/kiem-tra-trung-lap` | `documents.check-duplicate` | Verified User | Kiểm tra trước mã băm SHA-256 trước khi tải tệp lớn |
| GET | `/tai-lieu/{id}` | `documents.show` | Verified User | Xem chi tiết thông tin tài liệu và danh sách phiên bản |
| GET | `/tai-lieu/{id}/xem-truoc` | `documents.preview` | Verified User | Luồng xem trực tuyến tệp PDF an toàn (Stream Response) |
| GET | `/tai-lieu/{id}/tai-ve` | `documents.download` | Verified User | Tải file về máy và tự động tăng lượt tải (`luot_tai`) |
| POST | `/tai-lieu/{id}/binh-chon` | `documents.upvote` | Verified User | Thích hoặc bỏ thích tài liệu |
| POST | `/tai-lieu/{id}/tao-phien-ban` | `documents.create-version` | Verified User | Tải lên bản cập nhật mới (Version N+1) |
| DELETE | `/tai-lieu/{id}` | `documents.destroy` | Author/Policy | Tác giả xóa mềm tài liệu (áp dụng luật 24h & 0 lượt tải) |
| POST | `/tai-lieu/{id}/yeu-cau-go-bo` | `documents.request-removal` | Author | Gửi yêu cầu gỡ bỏ tài liệu đã thuộc sở hữu cộng đồng |

---

#### E. Phân hệ Kiểm duyệt & Thùng rác (Moderation Module)
* **Thư mục:** `app/Modules/Moderation/`
* **Service Provider:** `ModerationServiceProvider`
* **Chi tiết 4 tầng:**
  * **Presentation:**
    * `DocumentModerationController`: Hàng đợi duyệt tài liệu mới, xem chi tiết và đưa ra quyết định duyệt/từ chối.
    * `DocumentReportController`: Tiếp nhận báo cáo vi phạm cộng đồng và hàng đợi xử lý báo cáo.
    * `DocumentRemovalRequestController`: Tiếp nhận và xử lý yêu cầu gỡ bỏ tài liệu cộng đồng từ tác giả.
    * Quản lý thùng rác: Khôi phục tài liệu hoặc xóa vĩnh viễn tệp quá hạn 60 ngày.
  * **Application:**
    * Actions: `DecideDocumentReviewAction`, `SubmitDocumentReportAction`, `ResolveViolationReportAction`, `ArchiveViolationReportAction`, `DecideRemovalRequestAction`, `RestoreDocumentAction`, `PermanentlyDeleteDocumentAction`.
    * Queries: `GetReviewQueueQuery`, `GetReviewDetailQuery`, `GetReportQueueQuery`, `GetReportDetailQuery`, `GetRemovalRequestsQuery`, `GetRemovalRequestDetailQuery`, `GetDeletedDocumentsQuery`.
  * **Domain:**
    * `ModerationDecisionEvaluator`: Thẩm định quy tắc duyệt/từ chối tài liệu và xử lý báo cáo vi phạm.
    * `DocumentReportRecalculator`: Tự động đồng bộ số lượng báo cáo vi phạm vào tài liệu.
  * **Infrastructure:**
    * `ModerationReportRepository`, `RemovalRequestRepository`, `TrashManagementRepository`.

| Phương thức | Endpoint | Route Name | Phân quyền | Mô tả chức năng |
|:---|:---|:---|:---|:---|
| POST | `/bao-cao/tai-lieu` | `reports.document.store` | Verified User | Gửi báo cáo tài liệu vi phạm chuẩn mực |
| GET | `/quan-tri/bao-cao` | `admin.reports.index` | Mod/Admin | Hàng đợi các báo cáo vi phạm cần xử lý |
| GET | `/quan-tri/bao-cao/{id}` | `admin.reports.show` | Mod/Admin | Xem chi tiết nội dung báo cáo vi phạm |
| PUT | `/quan-tri/bao-cao/{id}/giai-quyet` | `admin.reports.resolve` | Mod/Admin | Giải quyết báo cáo: Ẩn tài liệu hoặc bác bỏ báo cáo |
| GET | `/quan-tri/tai-lieu-kiem-duyet` | `admin.documents.review-queue` | Mod/Admin | Hàng đợi các tài liệu mới tải lên chờ phê duyệt |
| PUT | `/quan-tri/tai-lieu/{id}/quyet-dinh` | `admin.documents.decide` | Mod/Admin | Phê duyệt hoặc từ chối tài liệu vào kho chung |
| GET | `/quan-tri/yeu-cau-go-tai-lieu` | `admin.documents.removal-requests.index` | Mod/Admin | Danh sách yêu cầu xin gỡ tài liệu từ tác giả |
| PUT | `/quan-tri/yeu-cau-go-tai-lieu/{id}/quyet-dinh` | `admin.documents.removal-requests.decide` | Mod/Admin | Quyết định duyệt xóa mềm hoặc từ chối gỡ tài liệu |
| GET | `/quan-tri/tai-lieu/da-xoa` | `admin.documents.deleted` | Admin | Quản lý danh sách tài liệu trong thùng rác |
| POST | `/quan-tri/tai-lieu/{id}/khoi-phuc` | `admin.documents.restore` | Admin | Khôi phục tài liệu đã bị xóa mềm |
| DELETE | `/quan-tri/tai-lieu/{id}/xoa-vinh-vien` | `admin.documents.permanent-delete` | Admin | Xóa vĩnh viễn tài liệu và tệp tin trên đĩa sau 60 ngày |

---

#### F. Phân hệ Đánh giá Môn học (Reviews Module)
* **Thư mục:** `app/Modules/Reviews/`
* **Service Provider:** `ReviewsServiceProvider`
* **Chi tiết 4 tầng:**
  * **Presentation:**
    * `CourseReviewController`: Xem danh sách review, gửi bài mới, chỉnh sửa, xóa mềm, bình chọn hữu ích, báo cáo review.
    * `CourseReviewResource`: Định dạng dữ liệu và thực thi cơ chế **Ẩn danh 2 lớp** (bảo vệ tuyệt đối mã người dùng).
    * Form Requests: `StoreCourseReviewRequest`, `UpdateCourseReviewRequest`, `ReportReviewRequest`.
  * **Application:**
    * Actions: `StoreCourseReviewAction` (kiểm tra từ cấm Lớp 2), `UpdateCourseReviewAction`, `DeleteCourseReviewAction`, `ToggleHelpfulVoteAction`, `ReportReviewViolationAction`.
    * Queries: `GetCourseReviewsQuery` (tính toán thống kê điểm trung bình 3 tiêu chí).
  * **Domain:**
    * `ProfanityFilterEvaluator`: Lọc từ cấm tiếng Việt, bẻ gãy ký tự Leetspeak (`0->o`, `1->i`, `3->e`), bắt từ chèn dấu (`đ.m`, `d m`), cache từ điển 24 giờ.
    * `CourseReviewRuleEvaluator`: Phân quyền chỉnh sửa (chỉ chính tác giả), quyền xóa mềm (tác giả hoặc Admin/Mod), chặn tự vote hữu ích cho bài của chính mình.
  * **Infrastructure:**
    * `CourseReviewRepository`: Tính toán điểm trung bình độ khó, thực tiễn, khối lượng; phân trang eager load quan hệ; toggle vote.

| Phương thức | Endpoint | Route Name | Phân quyền | Mô tả chức năng |
|:---|:---|:---|:---|:---|
| GET | `/mon-hoc/{ma_hoc_phan}/danh-gia` | `courses.reviews.index` | Auth | Xem danh sách đánh giá của môn học và chỉ số thống kê |
| POST | `/mon-hoc/{ma_hoc_phan}/danh-gia` | `courses.reviews.store` | Student/Alumni | Gửi đánh giá môn học mới (chạy qua bộ lọc từ cấm) |
| PUT | `/danh-gia/{ma_danh_gia}` | `reviews.update` | Author | Chỉnh sửa đánh giá của chính mình |
| DELETE | `/danh-gia/{ma_danh_gia}` | `reviews.destroy` | Author/Admin/Mod | Xóa mềm bài đánh giá |
| POST | `/danh-gia/{ma_danh_gia}/huu-ich` | `reviews.helpful` | Auth | Bình chọn đánh giá hữu ích (chặn tự bình chọn) |
| POST | `/danh-gia/{ma_danh_gia}/bao-cao` | `reviews.report` | Auth | Báo cáo đánh giá vi phạm tiêu chuẩn cộng đồng |

---

#### G. Phân hệ Săn thưởng & Ví điểm (Bounties Module)
* **Thư mục:** `app/Modules/Bounties/`
* **Service Provider:** `BountiesServiceProvider`
* **Chi tiết 4 tầng:**
  * **Presentation:**
    * `WishlistBountyController`: Danh sách yêu cầu (Wishlist), xem chi tiết, tạo yêu cầu, nộp tài liệu giải quyết, xác nhận giải ngân, hủy yêu cầu.
    * `WalletController`: Hiển thị ví điểm cá nhân và lịch sử giao dịch điểm (`Wallet/Index`).
    * Form Requests: `StoreBountyRequest`, `FulfillBountyRequest`, `ConfirmBountyRequest`.
  * **Application:**
    * Actions: `CreateBountyRequestAction`, `FulfillBountyRequestAction`, `ConfirmBountyFulfillmentAction`, `CancelBountyRequestAction`.
    * Queries: `GetWishlistBountyListQuery`, `GetWishlistBountyDetailQuery`.
  * **Domain:**
    * `ReputationEvaluator`: Khóa dòng `ViDiem` bằng `lockForUpdate`, cơ chế Idempotency với khóa chống lặp (`khoa_chong_trung`), đóng băng điểm (`TAM_GIU`), giải ngân điểm (`GIAI_PHONG` + `CONG_DIEM` + tăng uy tín), hoàn điểm khi hủy (`HOAN_TRA`).
    * `BountyRuleEvaluator`: Chặn người tạo tự nộp tài liệu vào yêu cầu của mình, phân quyền xác nhận và hủy yêu cầu.
  * **Infrastructure:**
    * `WishlistBountyRepository`: Truy vấn Wishlist theo 4 tiêu chí lọc, kiểm tra môn học thuộc Khoa đang hiệu lực, cập nhật trạng thái trong transaction.

| Phương thức | Endpoint | Route Name | Phân quyền | Mô tả chức năng |
|:---|:---|:---|:---|:---|
| GET | `/yeu-cau-tai-lieu` | `document-requests.index` | Auth | Danh sách các yêu cầu tìm tài liệu đang mở (Wishlist) |
| POST | `/yeu-cau-tai-lieu` | `document-requests.store` | Auth | Tạo yêu cầu mới (đóng băng điểm thưởng từ ví) |
| GET | `/yeu-cau-tai-lieu/{ma_yeu_cau}` | `document-requests.show` | Auth | Xem chi tiết yêu cầu và danh sách bài nộp phản hồi |
| POST | `/yeu-cau-tai-lieu/{ma_yeu_cau}/dong-gop` | `document-requests.fulfill` | Auth | Đóng góp tài liệu giải quyết yêu cầu (chặn người tạo) |
| POST | `/yeu-cau-tai-lieu/{ma_yeu_cau}/xac-nhan` | `document-requests.confirm` | Creator/Mod | Xác nhận tài liệu hợp lệ -> Giải ngân Bounty Points |
| DELETE | `/yeu-cau-tai-lieu/{ma_yeu_cau}` | `document-requests.destroy` | Creator/Admin | Hủy yêu cầu và hoàn lại điểm thưởng đã đóng băng vào ví |
| GET | `/vi-diem` | `wallet.index` | Auth | Xem số dư ví điểm cá nhân và lịch sử biến động điểm |

---

### 3. Tương thích Đa CSDL & Chiến lược Bộ nhớ đệm (Caching)

1. **Tương thích Đa CSDL (MySQL 8.0 & SQLite in-memory):**
   * Trong các truy vấn thống kê nhóm theo thời gian (ví dụ: tốc độ tăng trưởng lưu trữ 6 tháng qua), hệ thống luôn kiểm tra driver CSDL:
     ```php
     $isSqlite = DB::getDriverName() === 'sqlite';
     $dateFormat = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
     ```
   * Bảo đảm mã nguồn chạy mượt mà trên môi trường Production (MySQL 8.0) và đồng thời vượt qua 100% các bài test tự động trên SQLite in-memory mà không phát sinh lỗi cú pháp.

2. **Chiến lược Bộ nhớ đệm (Caching & Invalidation):**
   * Các chỉ số tổng hợp nặng (như thống kê dung lượng ổ đĩa `admin_storage_overview`) được bọc qua bộ nhớ đệm `Cache::remember('admin_storage_overview', 900, ...)`.
   * Tự động xóa cache ngay lập tức (`Cache::forget('admin_storage_overview')`) sau khi quản trị viên thực hiện hành động thay đổi trạng thái (dọn dẹp thùng rác hết hạn, xóa vĩnh viễn tệp).
   * Từ điển từ cấm (`system_prohibited_words_patterns`) được biên dịch sẵn regex và cache 24 giờ (`1440 phút`), tự động làm mới khi ban quản trị cập nhật bảng từ cấm.

3. **Bảo mật Dữ liệu Riêng tư (PII Protection):**
   * Minh chứng tốt nghiệp KYC cựu sinh viên tuyệt đối không được cấp đường dẫn tĩnh ra client; chỉ được truy xuất qua `Temporary Signed URL` sống tối đa 30 phút.
   * File PDF học thuật được phân phối qua luồng nhị phân trực tiếp (`Storage::disk('local')->response(...)`) kết hợp Content-Disposition inline, không để lộ đường dẫn tệp thực tế trên máy chủ.
   * Danh tính người đánh giá môn học áp dụng nguyên tắc **Ẩn danh 2 lớp**: Tầng hiển thị chỉ xuất `'Sinh viên ẩn danh'`, không xuất `mssv` hay `ma_nguoi_dung` ra props JSON của Inertia.

---

### 4. Kết quả Kiểm thử & Đảm bảo Chất lượng

- **Backend PHPUnit Tests:** **21 Test Suites / 112 bài kiểm thử (558 assertions) đạt tỷ lệ Pass 100% (0 errors, 0 failures)**.
- **Frontend Production Build:** `npm run build` hoàn tất trong **19.79s (2600 modules, 0 errors)**.
- **Quy chuẩn Comment:** Toàn bộ mã nguồn mới tuân thủ 100% **chỉ sử dụng comment dòng `//`**, không chứa bất kỳ khối comment `/*` hay `/**` nào.
