# BÁO CÁO KIỂM TOÁN TÍCH HỢP TOÀN DIỆN – PHASE 3C: COURSE DETAIL INTEGRATION AUDIT

---

## 1. Executive Summary

Báo cáo này là kết quả kiểm toán kỹ thuật toàn diện (Comprehensive Technical Audit) độc lập trên trang Chi tiết môn học (`resources/js/Pages/Courses/Show.jsx`) thuộc dự án **AGU Academic Hub**. 

Trang chi tiết môn học hiện đóng vai trò hạt nhân tích hợp ba phân hệ chính dành cho đối tượng **Sinh viên (`SINH_VIEN`)** và **Cựu sinh viên (`CUU_SINH_VIEN`)**:
1. **Kho tài liệu học thuật theo môn (Documents Module - Phase 3B1)**
2. **Hệ thống đánh giá môn học & Bình chọn cộng đồng (Course Reviews & Community Voting - Phase 3B2)**
3. **Danh sách mong muốn & Săn thưởng tài liệu (Course Wishlist & Bounty Integration - Phase 3B3)**

### Chỉ số kiểm toán tổng thể
- **Trạng thái Build Frontend:** **PASSED** (Vite v7.3.6, bundle sạch, 0 lỗi đóng gói, `public/build/assets/Show-CLyrHBaD.js` 43.13 kB).
- **Trạng thái Kiểm thử tự động:** **239 / 239 tests PASSED** (1,875 assertions, 0 failure, 0 error).
- **Phân loại phát hiện kỹ thuật:**
  - **CRITICAL (Nghiêm trọng):** 0
  - **HIGH (Cao):** 2 (Lỗ hổng logic kiểm tra quyền sở hữu tài liệu khi nộp Bounty; Thiếu kiểm tra trạng thái duyệt khi tải file trực tiếp)
  - **MEDIUM (Trung bình):** 3 (Xung đột tham số phân trang `page` giữa 3 tabs; Chuyển tab trên UI chưa đồng bộ URL; Tải dữ liệu tích lũy không dùng lazy loading)
  - **LOW (Thấp):** 3 (Thiếu try-catch bắt ngoại lệ trùng vote đồng thời; Silent fail khi khách bấm like tài liệu; Tải toàn bộ collection votes vào RAM)
  - **PASS (Đạt chuẩn):** 7 (Phân lập dữ liệu môn học, Ẩn danh 2 lớp PII, Giao dịch ACID điểm ví, Chống tự vote, Chống tự nhận Bounty, Error Boundary, Chống double submission)

### Kết luận kiểm toán
**READY WITH CONDITIONS (Sẵn sàng với điều kiện khắc phục các vấn đề HIGH và MEDIUM trước khi bàn giao đưa vào khai thác thực tế).**

---

## 2. Kiến Trúc Tích Hợp Thực Tế

Trang chi tiết môn học được tổ chức theo mô hình **Modular Monolith** kết hợp giao thức **Inertia.js / React 18**. Luồng điều phối và nạp dữ liệu diễn ra như sau:

```mermaid
flowchart TD
    Client["Trình duyệt Client<br>(React 18 / Inertia.js)"]
    Controller["CourseController@show<br>(AcademicCatalog Module)"]
    
    subgraph DataOrchestration ["Điều phối dữ liệu tích hợp"]
        CatalogQuery["GetPublicCoursesQuery<br>• findActiveCourseByCode<br>• getPublicFaculties"]
        DocRepo["DocumentRepository<br>• paginatePublicDocuments (8 docs/page)"]
        ReviewsQuery["GetCourseReviewsQuery<br>• getCourseReviewStats<br>• getPaginatedReviews (10 revs/page)"]
        WishlistRepo["WishlistBountyRepository<br>• getPaginatedWishlist (8 reqs/page)"]
    end

    subgraph SecurityShield ["Các lớp bảo vệ & Invariant"]
        RepEvaluator["ReputationEvaluator<br>(DB Transaction + lockForUpdate)"]
        ReviewRule["CourseReviewRuleEvaluator<br>(Anti Self-Vote)"]
        BountyRule["BountyRuleEvaluator<br>(Anti Self-Fulfill)"]
        ResourceMask["CourseReviewResource<br>(2-Layer Anonymity Mask)"]
    end

    subgraph UIComponents ["Giao diện Courses/Show.jsx"]
        Tab1["Tab 1: Kho tài liệu<br>(DocumentCard + Filter)"]
        Tab2["Tab 2: Đánh giá môn học<br>(ReviewCard + Voting Widget)"]
        Tab3["Tab 3: Wishlist & Bounty<br>(WishlistCard + Bounty Badge)"]
    end

    Client -->|GET /mon-hoc/{code}| Controller
    Controller --> CatalogQuery
    Controller --> DocRepo
    Controller --> ReviewsQuery
    Controller --> WishlistRepo
    
    DocRepo -.-> SecurityShield
    ReviewsQuery -.-> ResourceMask
    WishlistRepo -.-> BountyRule
    
    Controller -->|Inertia::render('Courses/Show')| UIComponents
```

### Các lớp kiến trúc tham gia:
1. **Presentation Layer:**
   - [`CourseController.php:33-76`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php#L33-L76): Controller trung tâm gộp dữ liệu từ 4 phân hệ.
   - [`CourseReviewController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Presentation/Http/Controllers/CourseReviewController.php): Tiếp nhận đăng bài, xóa mềm, bình chọn Up/Downvote, báo cáo vi phạm.
   - [`WishlistBountyController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Presentation/Http/Controllers/WishlistBountyController.php): Tiếp nhận tạo yêu cầu cọc điểm, nộp tài liệu đáp ứng, xác nhận giải ngân và hủy yêu cầu.
   - [`DocumentController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php): Điều phối tải file về máy, xem trước PDF, bình chọn tài liệu.
2. **Application Layer:**
   - [`GetPublicCoursesQuery.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Application/Queries/GetPublicCoursesQuery.php)
   - [`GetCourseReviewsQuery.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Application/Queries/GetCourseReviewsQuery.php)
   - Các Command Actions: `StoreCourseReviewAction`, `ToggleHelpfulVoteAction`, `CreateBountyRequestAction`, `FulfillBountyRequestAction`, `ConfirmBountyFulfillmentAction`, `CancelBountyRequestAction`.
3. **Domain Layer:**
   - [`ReputationEvaluator.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Domain/Services/ReputationEvaluator.php): Khóa hàng (`lockForUpdate`), ghi nhật ký bất biến `giao_dich_diem`, bảo toàn quỹ điểm ví.
   - [`CourseReviewRuleEvaluator.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Domain/Services/CourseReviewRuleEvaluator.php): Chống tự bình chọn đánh giá.
   - [`BountyRuleEvaluator.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Domain/Services/BountyRuleEvaluator.php): Chống tự nộp tài liệu vào yêu cầu của chính mình.
4. **Infrastructure Layer:**
   - `AcademicCatalogRepository`, `DocumentRepository`, `CourseReviewRepository`, `WishlistBountyRepository`.
5. **Frontend Layer:**
   - [`Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx): Giao diện đa tab tích hợp Error Boundary và Modal Connectors.

---

## 3. Ma Trận Kiểm Toán Ba Phân Hệ

| Phân hệ | Chức năng kiểm tra | Kênh kiểm chứng (Code / Route) | Kết quả kiểm toán | Ghi chú & Rủi ro |
| :--- | :--- | :--- | :---: | :--- |
| **Documents** | Danh sách tài liệu đúng môn học | `CourseController.php:40`, `DocumentRepository.php:23` | **PASS** | Lọc chuẩn xác theo `ma_mon_hoc`, chỉ lấy tài liệu `DA_DUYET`. |
| **Documents** | Tìm kiếm theo tiêu đề | `DocumentRepository.php:16` | **PASS** | Tìm kiếm LIKE `%keyword%`, debounced 350ms trên frontend. |
| **Documents** | Lọc theo danh mục tài liệu | `DocumentRepository.php:20` | **PASS** | Phân loại `DE_THI`, `BAI_GIANG`, `DO_AN`, `BAI_TAP`, `GIAO_TRINH`. |
| **Documents** | Sắp xếp (Mới nhất, Lượt tải, Upvote) | `DocumentRepository.php:27-32` | **PASS** | Hoạt động chính xác theo order column tương ứng. |
| **Documents** | Phân trang kho tài liệu | `DocumentRepository.php:34` | **CONDITION** | Phân trang 8 mục/trang, nhưng dùng chung query param `page` với các tab khác (**AUD-M01**). |
| **Documents** | Xem trước PDF trực tuyến | `DocumentController.php:116`, `documents.preview` | **PASS** | Đọc stream PDF trực tiếp trong iframe/tab mới. |
| **Documents** | Tải tài liệu & tăng lượt tải | `DownloadDocumentAction.php:24`, `documents.download` | **CONDITION** | Tự động tăng `luot_tai`, nhưng thiếu kiểm tra trạng thái duyệt `DA_DUYET` (**AUD-H02**). |
| **Documents** | Quyền truy cập tài liệu | `app/Modules/Documents/routes.php:9` | **PASS** | Yêu cầu `auth` + `verified`. Khách bấm tải được chuyển hướng về trang đăng nhập. |
| **Reviews** | Đăng bài đánh giá mới | `StoreCourseReviewAction.php:39`, `courses.reviews.store` | **PASS** | Điểm 1-5 sao, format học kỳ chuẩn `YYYY-[1-3]`, chống trùng bài đánh giá cùng học kỳ. |
| **Reviews** | Bộ lọc ngôn từ thô tục (Profanity) | `StoreCourseReviewRequest.php:107`, `ProfanityFilterService` | **PASS** | Kiểm duyệt 2 lớp (FormRequest validator + Action evaluator), chặn đứng từ cấm. |
| **Reviews** | Cơ chế Ẩn danh & Che giấu PII | `CourseReviewResource.php:104,113` | **PASS** | Tuyệt đối không xuất `ma_nguoi_dung`, email hay MSSV ra response frontend. |
| **Reviews** | Community Upvote / Downvote | `CourseReviewRepository.php:101`, `reviews.vote` | **PASS** | Ghi nhận +1, -1, chuyển đổi trạng thái và hủy vote chuẩn xác. |
| **Reviews** | Chống tự bình chọn bài của mình | `ToggleHelpfulVoteAction.php:38`, `Show.jsx:245` | **PASS** | Khóa hai đầu: Frontend làm mờ nút + Backend chặn 403 / warning. |
| **Reviews** | Cô lập điểm số (Point Isolation) | `test_voting_does_not_modify_user_reputation` | **PASS** | Vote chỉ ảnh hưởng `review_score`, không cộng trừ điểm ví và điểm uy tín. |
| **Reviews** | Xóa mềm & Báo cáo vi phạm | `CourseReviewController.php:61,84`, `reviews.destroy` | **PASS** | Chỉ tác giả mới xóa được bài; Người dùng gửi báo cáo vi phạm tới Ban kiểm duyệt. |
| **Wishlist** | Đăng yêu cầu tìm tài liệu | `WishlistBountyController.php:59`, `document-requests.store` | **PASS** | Tự động khóa môn học (`defaultCourse`), tối thiểu 10 điểm, không vượt quá số dư ví. |
| **Wishlist** | Đóng băng điểm thưởng (Frozen Points) | `ReputationEvaluator.php:30-70` | **PASS** | `diem_kha_dung -= X`, `diem_tam_giu += X`, ghi nhật ký `TAM_GIU` trong transaction. |
| **Wishlist** | Hủy yêu cầu & Hoàn 100% điểm | `CancelBountyRequestAction.php:21` | **PASS** | Chỉ tác giả hủy được yêu cầu đang Mở (`MO`), điểm hoàn trả 100% trong transaction. |
| **Wishlist** | Đóng góp tài liệu giải quyết Bounty | `FulfillBountyRequestAction.php:18` | **CONDITION** | Modal lấy tài liệu đã duyệt của mình, nhưng backend thiếu kiểm tra quyền sở hữu (**AUD-H01**). |
| **Wishlist** | Chống tự nhận thưởng (Anti-fraud) | `BountyRuleEvaluator.php:24` | **PASS** | Chặn tác giả tự nộp tài liệu vào yêu cầu của mình. |
| **Wishlist** | Xác nhận giải ngân & Tăng uy tín | `ConfirmBountyFulfillmentAction.php:21` | **PASS** | Điểm tạm giữ chuyển sang ví người đóng góp, cộng điểm uy tín, cập nhật trạng thái `CHAP_NHAN`. |
| **Wishlist** | An toàn giao dịch & Idempotency | `GiaoDichDiem.khoa_chong_trung` UNIQUE | **PASS** | Khóa hàng `lockForUpdate`, khóa chống trùng chặn đứng giải ngân 2 lần. |

---

## 4. Ma Trận Phân Quyền (RBAC & Guest Matrix)

| Hành động trên trang Chi tiết môn học | Khách (Guest) | Sinh viên (`SINH_VIEN`) | Cựu sinh viên (`CUU_SINH_VIEN`) | Cơ chế kiểm soát Backend |
| :--- | :---: | :---: | :---: | :--- |
| **Xem thông tin môn học, khoa, chuyên ngành** | Cho phép | Cho phép | Cho phép | Tuyến `courses.show` mở công khai (`public`). |
| **Xem danh sách tài liệu môn học** | Cho phép | Cho phép | Cho phép | Nạp qua `GetPublicCoursesQuery`. |
| **Tải về hoặc xem trước tài liệu** | Chuyển hướng Login | Cho phép | Cho phép | Middleware `['auth', 'verified']` trên `app/Modules/Documents/routes.php:9`. |
| **Bình chọn tài liệu (Document Like)** | Chuyển hướng / 401 | Cho phép | Cho phép | Tuyến `documents.upvote` yêu cầu xác thực. |
| **Xem thống kê & danh sách đánh giá** | Cho phép | Cho phép | Cho phép | Dữ liệu public qua `CourseReviewResource`. |
| **Viết đánh giá môn học mới** | Chuyển hướng Login | Cho phép | Cho phép | `StoreCourseReviewRequest::authorize()` kiểm tra `SINH_VIEN` / `CUU_SINH_VIEN` & hoạt động. |
| **Bình chọn đánh giá (Community Vote)** | Chuyển hướng Login | Cho phép | Cho phép | Tuyến `reviews.vote` nằm trong nhóm middleware `auth`. |
| **Xóa bài đánh giá của chính mình** | Bị từ chối (401) | Cho phép | Cho phép | `DeleteCourseReviewAction` xác thực `ma_nguoi_dung === user->getKey()`. |
| **Xem danh sách yêu cầu tài liệu (Wishlist)** | Cho phép | Cho phép | Cho phép | Nạp công khai qua `WishlistBountyRepository`. |
| **Tạo yêu cầu săn thưởng (Bounty)** | Chuyển hướng Login | Cho phép | Cho phép | Tuyến `document-requests.store` nằm trong nhóm middleware `auth`. |
| **Nộp tài liệu đáp ứng Bounty** | Chuyển hướng Login | Cho phép | Cho phép | Tuyến `document-requests.fulfill` nằm trong nhóm middleware `auth`. |
| **Xác nhận giải ngân Bounty** | Bị từ chối (401) | Cho phép (Chính chủ) | Cho phép (Chính chủ) | `BountyRuleEvaluator::canConfirm` kiểm tra quyền tác giả hoặc Admin/Mod. |
| **Hủy yêu cầu & Hoàn điểm ví** | Bị từ chối (401) | Cho phép (Chính chủ) | Cho phép (Chính chủ) | `BountyRuleEvaluator::canCancel` chỉ cho phép chính chủ hoặc Admin. |

---

## 5. Kiểm Toán Database Integrity & Constraints

### 5.1. Khóa ngoại & Hành vi Cascade
- `danh_gia_mon_hoc`: Khóa ngoại liên kết `mon_hoc` và `nguoi_dung` sử dụng `restrictOnDelete()`. Đảm bảo môn học hoặc tài khoản có bài đánh giá không thể bị xóa cứng làm mồ côi dữ liệu.
- `binh_chon_danh_gia`: Khóa ngoại liên kết `danh_gia_mon_hoc` và `nguoi_dung` sử dụng `cascadeOnDelete()`. Khi bài đánh giá hoặc người dùng bị xóa, toàn bộ bình chọn liên quan được dọn dẹp sạch sẽ.
- `yeu_cau_tai_lieu`: Khóa ngoại liên kết `mon_hoc` và `nguoi_dung` dùng `restrictOnDelete()`. Tài liệu đáp ứng `ma_tai_lieu_dap_ung` dùng `nullOnDelete()`.
- `dap_ung_yeu_cau_tai_lieu`: Khóa ngoại liên kết `yeu_cau_tai_lieu`, `tai_lieu` và `nguoi_dung` đều dùng `restrictOnDelete()`.

### 5.2. Ràng buộc duy nhất (Unique Constraints)
- **Đánh giá môn học:** `UNIQUE(ma_mon_hoc, ma_nguoi_dung, hoc_ky)` tại [`database/migrations/2026_10_14_000007_create_danh_gia_mon_hoc_table.php:24`](file:///d:/CNWEB/AGU-Academic-Hub/database/migrations/2026_10_14_000007_create_danh_gia_mon_hoc_table.php#L24) $\rightarrow$ Ngăn chặn sinh viên spam nhiều review cho cùng một môn trong một kỳ.
- **Bình chọn đánh giá:** `UNIQUE(ma_danh_gia, ma_nguoi_dung)` tại dòng 45 $\rightarrow$ Ngăn chặn một người dùng bình chọn 2 lần cho 1 bài review.
- **Bản nộp Bounty:** `UNIQUE(ma_yeu_cau, ma_tai_lieu)` tại [`database/migrations/2026_10_14_000008_create_vi_diem_va_yeu_cau_tai_lieu_table.php:60`](file:///d:/CNWEB/AGU-Academic-Hub/database/migrations/2026_10_14_000008_create_vi_diem_va_yeu_cau_tai_lieu_table.php#L60) $\rightarrow$ Ngăn chặn gửi trùng lặp cùng 1 tài liệu cho 1 yêu cầu.
- **Khóa chống trùng giao dịch:** `giao_dich_diem.khoa_chong_trung` UNIQUE tại dòng 72 $\rightarrow$ Đảm bảo tính Idempotent tuyệt đối cho ví điểm.

### 5.3. Ràng buộc logic Check Constraints & Soft Delete
- CSDL áp dụng `CHECK (diem_kha_dung >= 0)` và `CHECK (diem_tam_giu >= 0)` trên bảng `vi_diem`, ngăn chặn hoàn toàn lỗi âm ví.
- Bảng `mon_hoc`, `tai_lieu`, `danh_gia_mon_hoc`, `yeu_cau_tai_lieu` đều tích hợp `SoftDeletes` (`ngay_xoa_tam`), bảo toàn lịch sử dữ liệu và tuân thủ nguyên tắc lưu trữ học thuật.

---

## 6. Security, Privacy & Invariant Checks

### 6.1. Bảo vệ quyền riêng tư & Nguyên tắc Ẩn danh 2 Lớp (2-Layer Anonymity)
- **Lớp 1 (Transform Layer):** [`CourseReviewResource.php:104,113`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Presentation/Http/Resources/CourseReviewResource.php#L104) triệt tiêu hoàn toàn các trường nhạy cảm: `ma_nguoi_dung`, `email`, `mssv`.
- **Lớp 2 (Client Visibility):** Khi `an_danh = true`, tên hiển thị bắt buộc là `"Sinh viên ẩn danh"`, ảnh đại diện là `null`. Chỉ duy nhất chính tác giả xem lại bài của mình mới thấy nhãn `"(Bài viết của bạn)"`.
- **Đánh giá rò rỉ PII:** **KHÔNG CÓ RÒ RỈ PII TRONG RESPONSE REVIEW**. Đã được kiểm thử tự động tại `Phase3b2CourseReviewsTest::test_public_response_preserves_strict_anonymity`.

### 6.2. Bảo toàn điểm số & Chống gian lận (Anti-fraud & Conservation Invariants)
- **Bảo toàn điểm ví:** Mọi biến động ví điểm đều diễn ra trong `DB::transaction` với `lockForUpdate()`. Tổng số điểm không tự sinh ra hoặc biến mất.
- **Chống tự bình chọn review:** Phía client ẩn nút và vô hiệu hóa click; phía backend `CourseReviewRuleEvaluator::canVoteHelpful` từ chối nếu tác giả cố tình gửi request can thiệp API.
- **Chống tự nhận thưởng Bounty:** Phía backend `BountyRuleEvaluator::canFulfill` kiểm tra `$yeuCau->ma_nguoi_tao === $user->ma_nguoi_dung` và lập tức từ chối bài nộp của chính tác giả.

---

## 7. Hiệu Năng & Tối Ưu Truy Vấn (Performance Audit)

### 7.1. Phân tích N+1 Queries
- **Kho tài liệu:** `DocumentRepository.php:14` thực hiện eager load `['nguoiDang:ma_nguoi_dung,ho_ten', 'monHoc:ma_mon_hoc,ten_mon_hoc,ma_hoc_phan']` $\rightarrow$ **Không có N+1 query**.
- **Danh sách Đánh giá:** `CourseReviewRepository.php:67` thực hiện eager load `['nguoiDung.khoa', 'binhChons']` và `withCount` $\rightarrow$ **Không có N+1 query**.
- **Danh sách Wishlist:** `WishlistBountyRepository.php:18` thực hiện eager load `['monHoc', 'nguoiTao', 'taiLieuDapUng']` và `withCount('dapUngs')` $\rightarrow$ **Không có N+1 query**.

### 7.2. Tải dữ liệu tích lũy trên `CourseController@show` (Vấn đề AUD-M03)
Khi người dùng truy cập bất kỳ trang chi tiết môn học nào, `CourseController.php` hiện đang thực thi đồng thời cả 4 tác vụ truy vấn dữ liệu dù người dùng có thể chỉ muốn xem một tab duy nhất:
1. Truy vấn môn học và các khoa liên quan (2 queries).
2. Đếm và nạp 8 tài liệu (2 queries).
3. Tính toán thống kê điểm 3 tiêu chí + đếm và nạp 10 đánh giá kèm bình chọn (4 queries).
4. Đếm và nạp 8 yêu cầu tài liệu săn thưởng (2 queries).
- **Tổng số queries:** Khoảng **12 đến 15 queries** cho mỗi lượt truy cập trang chi tiết môn học.
- **Hạn chế:** Các props chưa được bao bọc trong `Inertia::lazy(fn() => ...)`, dẫn đến việc Inertia Partial Reloading không thể bỏ qua các câu truy vấn nặng trên server khi chỉ cần tải lại dữ liệu của một tab.

---

## 8. Frontend UX & Tương Tác Giao Diện

### 8.1. Trải nghiệm Chuyển Tab & Đồng bộ URL (Vấn đề AUD-M02)
- Tại [`Courses/Show.jsx:609,628,647`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx#L609):
  ```javascript
  onClick={() => setActiveTab("documents")}
  onClick={() => setActiveTab("reviews")}
  onClick={() => setActiveTab("wishlist")}
  ```
- **Hành vi thực tế:** Khi người dùng bấm chuyển tab, hệ thống chỉ cập nhật React State `activeTab` trên client mà **không cập nhật URL trình duyệt** (`?tab=reviews` hoặc `?tab=wishlist`).
- **Hệ quả UX:** Nếu người dùng đang xem Tab 2 hoặc Tab 3 và nhấn F5 (Reload trang), trang sẽ tự động tải lại từ đầu với URL gốc và đưa người dùng quay ngược về Tab 1 (Documents).

### 8.2. Xung đột Phân trang Đa Tab (Cross-tab Pagination Collision - AUD-M01)
- Cả ba kho dữ liệu (`documents`, `reviews`, `wishlist_requests`) đều sử dụng chung tham số truy vấn phân trang mặc định của Laravel là `page`.
- Khi người dùng ở Tab 2 và bấm sang Trang 2 của đánh giá, URL chuyển thành `?page=2`.
- Khi gửi request lên server:
  - Repository Documents nhận `page=2` $\rightarrow$ phân trang tài liệu sang trang 2.
  - Repository Wishlist nhận `page=2` $\rightarrow$ phân trang wishlist sang trang 2.
- **Hệ quả UX:** Nếu phân hệ Wishlist chỉ có 3 mục (chỉ có trang 1), khi nhận `page=2`, nó sẽ trả về kết quả rỗng. Khi người dùng chuyển sang Tab Wishlist, họ sẽ thấy thông báo "Không tìm thấy yêu cầu nào" mặc dù trang 1 có dữ liệu.

### 8.3. Khả năng tương thích Thiết bị & Trạng thái Form
- **Responsive:** Bố cục giao diện hiển thị linh hoạt trên Desktop, Tablet và Mobile. Thanh tabs hỗ trợ trượt ngang (`overflow-x-auto`).
- **Trạng thái Pending & Chống Double-click:**
  - Cả 3 Modals (`CreateReviewModal`, `CreateRequestModal`, `FulfillRequestModal`) đều áp dụng thuộc tính `processing` của Inertia `useForm`, khóa cứng nút gửi và bàn phím trong lúc đang gửi dữ liệu lên server.
  - Widget Voting có Set `votingIds` chặn spam click vào cùng một bài review.
- **Error Boundary:** Trang được bao bọc bởi `CourseDetailErrorBoundary`, ngăn chặn lỗi trắng trang (White Screen of Death) khi có dữ liệu payload bất thường.

---

## 9. Đánh Giá Bộ Test Tự Động (Regression Testing)

Hệ thống sở hữu bộ test tự động rất đồ sộ với **239 tests PASSED (1,875 assertions)**. Dưới đây là phân tích chi tiết mức độ bao phủ và các điểm mù (blind spots) của từng bộ test:

| Test Suite | Số Tests | Nội dung đã được kiểm tra nghiêm ngặt | Điểm mù chưa kiểm tra (Test Blind Spots) |
| :--- | :---: | :--- | :--- |
| **Phase3aCourseDetailTest** | 7 | Render trang, nạp khoa/chuyên ngành, 404 môn học/khoa vô hiệu hóa, truy cập Guest/SV/Alumni. | Chưa test tương tác trên thiết bị di động. |
| **Phase3b1DocumentListingTest** | 7 | Metadata tài liệu, phân lập môn học, empty collection, ẩn tài liệu chưa duyệt, tải và like file. | Chưa test kịch bản Guest bấm nút like tài liệu trên DocumentCard. |
| **Phase3b2CourseReviewsTest** | 20 | Tạo review, filter từ cấm, ẩn danh PII, Upvote/Downvote 2 chiều, chống tự vote, cô lập điểm ví/uy tín. | Chưa test gửi 2 request vote đồng thời trên cùng 1 kết nối (Concurrent Race Condition). |
| **Phase3b3CourseWishlistTest** | 17 | Phân lập wishlist theo môn, đóng băng/hoàn điểm ví, nộp tài liệu cá nhân, giải ngân điểm và uy tín. | Chưa test việc nộp mã tài liệu của người khác hoặc tài liệu chưa duyệt qua API endpoint. |
| **Các test suites toàn hệ thống** | 188 | Toàn bộ các phân hệ Admin, KYC, Roster, Dashboard, Notifications, Auth. | Bảo đảm 100% không phát sinh lỗi hồi quy (No regression). |

---

## 10. Danh Sách Lỗi & Rủi Ro Kỹ Thuật (Defect Log)

```
========================================================================================
DANH SÁCH LỖI VÀ RỦI RO KỸ THUẬT PHÁT HIỆN QUA KIỂM TOÁN TÍCH HỢP PHASE 3C
========================================================================================
```

### [HIGH] AUD-H01: Thiếu kiểm tra quyền sở hữu và tính hợp lệ của tài liệu khi nộp Bounty
- **Tệp liên quan:** [`app/Modules/Bounties/Application/Actions/FulfillBountyRequestAction.php:31`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Application/Actions/FulfillBountyRequestAction.php#L31) và [`FulfillBountyRequest.php:18`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Presentation/Http/Requests/FulfillBountyRequest.php#L18)
- **Tuyến đường:** `POST /yeu-cau-tai-lieu/{ma_yeu_cau}/dong-gop` (`document-requests.fulfill`)
- **Mô tả chi tiết:** 
  Giao diện `FulfillRequestModal.jsx` truy vấn `/api/my-documents` nên chỉ hiển thị tài liệu của sinh viên. Tuy nhiên, tại tầng API Backend, FormRequest chỉ kiểm tra `exists:tai_lieu,ma_tai_lieu`. Action không kiểm tra xem:
  1. Tài liệu có thuộc sở hữu của người đang nộp hay không (`ma_nguoi_dang === user->ma_nguoi_dung`).
  2. Tài liệu đã được Ban kiểm duyệt thông qua hay chưa (`trang_thai === 'DA_DUYET'`).
  3. Môn học của tài liệu có khớp với môn học của Bounty hay không (`ma_mon_hoc === yeuCau->ma_mon_hoc`).
- **Rủi ro:** Kẻ xấu có thể dùng công cụ như Postman để nộp ID tài liệu của người khác hoặc tài liệu từ môn học khác vào Bounty để trục lợi điểm thưởng khi được duyệt.
- **Mức độ nghiêm trọng:** **HIGH**

---

### [HIGH] AUD-H02: Thiếu kiểm tra trạng thái duyệt khi tải và xem trước tài liệu trực tiếp
- **Tệp liên quan:** [`app/Modules/Documents/Application/Queries/GetDocumentDetailQuery.php:17`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Application/Queries/GetDocumentDetailQuery.php#L17) và [`DownloadDocumentAction.php:20`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Documents/Application/Actions/DownloadDocumentAction.php#L20)
- **Tuyến đường:** `GET /tai-lieu/{id}`, `GET /tai-lieu/{id}/xem-truoc`, `GET /tai-lieu/{id}/tai-ve`
- **Mô tả chi tiết:** 
  Phương thức `findById` trong `DocumentRepository` truy vấn thẳng `findOrFail($id)` mà không ràng buộc điều kiện `trang_thai = 'DA_DUYET'`.
- **Rủi ro:** Bất kỳ người dùng nào đã đăng nhập nếu biết ID tài liệu đang chờ duyệt (`CHO_DUYET`) hoặc bị từ chối (`TU_CHOI`) đều có thể truy cập trực tiếp đường link để xem nội dung và tải file về máy trước khi được kiểm duyệt.
- **Mức độ nghiêm trọng:** **HIGH**

---

### [MEDIUM] AUD-M01: Xung đột tham số phân trang `page` giữa ba phân hệ trên cùng trang
- **Tệp liên quan:** [`CourseController.php:42,46,56`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php#L42), [`Show.jsx:437,446,455`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx#L437)
- **Tuyến đường:** `GET /mon-hoc/{course_code}` (`courses.show`)
- **Mô tả chi tiết:**
  `DocumentRepository`, `CourseReviewRepository` và `WishlistBountyRepository` đều gọi `$query->paginate($perPage)` mà không chỉ định tên tham số phân trang riêng biệt (như `doc_page`, `review_page`, `wishlist_page`).
- **Rủi ro:** Khi chuyển sang trang 2 ở một tab bất kỳ, URL nhận tham số `?page=2`, khiến cả 2 tab còn lại cũng bị ép chuyển sang trang 2. Nếu một tab có tổng số bài viết ít hơn trang 2, tab đó sẽ hiển thị Empty State giả (kết quả rỗng).
- **Mức độ nghiêm trọng:** **MEDIUM**

---

### [MEDIUM] AUD-M02: Chuyển tab trên giao diện không cập nhật URL và lịch sử duyệt web
- **Tệp liên quan:** [`resources/js/Pages/Courses/Show.jsx:609,628,647`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx#L609)
- **Mô tả chi tiết:**
  Sự kiện click vào các nút Tab chỉ thực hiện `setActiveTab(...)` ở local state. URL trên thanh địa chỉ không đổi thành `?tab=reviews` hay `?tab=wishlist` (chỉ đổi khi người dùng lọc dữ liệu).
- **Rủi ro:** Người dùng tải lại trang (F5) hoặc sao chép liên kết chia sẻ cho bạn bè sẽ luôn bị quay trở về Tab 1 (Documents), gây bất tiện trong trải nghiệm sử dụng.
- **Mức độ nghiêm trọng:** **MEDIUM**

---

### [MEDIUM] AUD-M03: Tải dữ liệu tích lũy không dùng Lazy Loading trên `CourseController`
- **Tệp liên quan:** [`CourseController.php:33-76`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php#L33-L76)
- **Mô tả chi tiết:**
  Mỗi lượt truy cập trang chi tiết môn học đều thực thi toàn bộ các câu lệnh SQL đếm và nạp cho cả 3 phân hệ (12-15 queries) mà không sử dụng `Inertia::lazy()`.
- **Rủi ro:** Tiêu tốn tài nguyên cơ sở dữ liệu khi lưu lượng truy cập cao, không tận dụng được cơ chế Partial Reloading của Inertia.js.
- **Mức độ nghiêm trọng:** **MEDIUM**

---

### [LOW] AUD-L01: Nguy cơ phát sinh ngoại lệ cơ sở dữ liệu khi gửi nhiều yêu cầu vote đồng thời
- **Tệp liên quan:** [`CourseReviewRepository.php:134`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Infrastructure/Persistence/CourseReviewRepository.php#L134)
- **Tuyến đường:** `POST /danh-gia/{ma_danh_gia}/vote`
- **Mô tả chi tiết:**
  Phương thức `castCommunityVote` kiểm tra bản ghi vote cũ rồi mới gọi `BinhChonDanhGia::create()`. Mặc dù CSDL có ràng buộc `UNIQUE(ma_danh_gia, ma_nguoi_dung)`, nhưng không có khối `try-catch` bao bọc.
- **Rủi ro:** Nếu 2 request vote từ cùng một tài khoản tới server gần như cùng một miligiây, request thứ 2 có thể gặp lỗi `QueryException` (Duplicate entry) gây lỗi HTTP 500 thay vì xử lý êm đẹp.
- **Mức độ nghiêm trọng:** **LOW**

---

### [LOW] AUD-L02: Giao diện im lặng (Silent Fail) khi khách bấm nút thích trên Document Card
- **Tệp liên quan:** [`resources/js/Components/domain/DocumentCard.jsx:88`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Components/domain/DocumentCard.jsx#L88)
- **Mô tả chi tiết:**
  Hàm `handleToggleUpvote` dùng `axios.post` gọi API upvote. Khi khách chưa đăng nhập bấm vào, backend trả về mã 401, axios nhảy vào khối `catch` và chỉ ghi `console.error` mà không hiển thị thông báo yêu cầu đăng nhập.
- **Rủi ro:** Khách bấm nút không thấy phản hồi nào trên giao diện, tạo cảm giác nút bị liệt.
- **Mức độ nghiêm trọng:** **LOW**

---

### [LOW] AUD-L03: Nạp toàn bộ collection bình chọn vào bộ nhớ RAM khi tính điểm review
- **Tệp liên quan:** [`CourseReviewRepository.php:67`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Infrastructure/Persistence/CourseReviewRepository.php#L67), [`CourseReviewResource.php:32`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Reviews/Presentation/Http/Resources/CourseReviewResource.php#L32)
- **Mô tả chi tiết:**
  Hệ thống eager load toàn bộ quan hệ `binhChons` của bài review để tính điểm trong PHP collection.
- **Rủi ro:** Khi một bài đánh giá có hàng nghìn lượt bình chọn cộng đồng, việc load toàn bộ các model `BinhChonDanhGia` vào RAM sẽ gây tiêu tốn bộ nhớ của PHP worker.
- **Mức độ nghiêm trọng:** **LOW**

---

## 11. Những Vấn Đề Chưa Đủ Bằng Chứng (Unverified Hypotheses)

1. **Hiệu năng chịu tải đồng thời (High Concurrency Stress Test):** Hiện mới chỉ kiểm thử tính đúng đắn theo chuỗi đơn luồng (Single-thread Functional Tests). Chưa có dữ liệu thực tế về độ trễ khi có 500+ sinh viên cùng truy cập và thực hiện thao tác cọc điểm/vote đồng thời trên cùng một môn học.
2. **Kích thước file tài liệu ngoại cỡ khi xem trước (PDF Preview on Mobile):** Chưa kiểm chứng trải nghiệm người dùng thực tế trên các dòng điện thoại cấu hình thấp khi render file PDF dung lượng lớn (>30MB) qua thẻ nhúng.

---

## 12. Kết Luận & Đề Xuất Thứ Tự Khắc Phục

### Kết luận tổng kết
> [!IMPORTANT]
> **KẾT LUẬN: READY WITH CONDITIONS**
> 
> Trang Chi tiết môn học đã đạt độ hoàn thiện cao về mặt kiến trúc, tích hợp thành công cả 3 phân hệ theo đúng định hướng Modular Monolith, bảo đảm an toàn dữ liệu cá nhân (Ẩn danh PII) và tính toàn vẹn của ví điểm (ACID). Toàn bộ 239 tests hiện hữu đều vượt qua mà không có bất kỳ xung đột nào.
> 
> Tuy nhiên, hệ thống **cần khắc phục các vấn đề phân quyền API và tối ưu trải nghiệm đa tab** trước khi bàn giao đưa vào vận hành sản xuất.

---

### Đề xuất 3 bước tiếp theo (Xếp theo thứ tự ưu tiên giảm dần):

#### Bước 1: Khắc phục 2 lỗ hổng bảo mật & phân quyền API (AUD-H01 & AUD-H02)
- Trong [`FulfillBountyRequestAction.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Application/Actions/FulfillBountyRequestAction.php), bổ sung kiểm tra: tài liệu phải thuộc chính người dùng (`ma_nguoi_dang === user->ma_nguoi_dung`), đã được duyệt (`DA_DUYET`) và thuộc đúng môn học (`ma_mon_hoc === yeuCau->ma_mon_hoc`).
- Trong `DocumentRepository::findById` hoặc `DownloadDocumentAction`, bổ sung điều kiện chỉ cho phép tải file khi tài liệu ở trạng thái `DA_DUYET` (hoặc người tải là chính tác giả/Ban kiểm duyệt).

#### Bước 2: Tách biệt tham số phân trang giữa 3 tabs (AUD-M01)
- Cập nhật các repository để sử dụng tên tham số phân trang độc lập:
  - Tài liệu: `pageName: 'doc_page'`
  - Đánh giá: `pageName: 'review_page'`
  - Yêu cầu Bounty: `pageName: 'wishlist_page'`
- Giúp loại bỏ hoàn toàn hiện tượng nhảy trang chéo và hiển thị rỗng giả khi duyệt dữ liệu giữa các tab.

#### Bước 3: Đồng bộ trạng thái URL khi bấm tab & Thêm nhắc nhở đăng nhập (AUD-M02 & AUD-L02)
- Trong [`Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx), khi người dùng click vào từng tab button, sử dụng `window.history.replaceState` hoặc `router.replace` để cập nhật query parameter `?tab=...` trên thanh địa chỉ mà không reload trang.
- Trong `DocumentCard.jsx`, bắt lỗi 401 khi khách like tài liệu và chuyển hướng người dùng tới trang đăng nhập thay vì để giao diện im lặng.

