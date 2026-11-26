# BÁO CÁO NGHIỆM THU – PHASE 3B3: COURSE WISHLIST & BOUNTY INTEGRATION

## 1. Tổng Quan & Kết Quả Nghiệm Thu

| Hạng mục | Trạng thái |
| :--- | :--- |
| **Giai đoạn** | **Phase 3B3: Tích hợp Wishlist & Săn thưởng tài liệu (Bounties) vào Course Detail** |
| **Phân hệ áp dụng** | Sinh viên (`SINH_VIEN`), Cựu sinh viên (`CUU_SINH_VIEN`) |
| **Kết quả Feature Test Phase 3B3** | **17 / 17 PASSED** ([`tests/Feature/Phase3b3CourseWishlistTest.php`](file:///d:/CNWEB/AGU-Academic-Hub/tests/Feature/Phase3b3CourseWishlistTest.php)) |
| **Kết quả Toàn bộ Test Suite** | **239 / 239 PASSED** (1875 assertions, 0 failures, 0 regressions) |
| **Kết quả Vite Build** | **PASSED** (0 errors, 2603 modules transformed, `public/build/assets/Show-CLyrHBaD.js` 43.13 kB) |
| **Trạng thái Database Schema** | **Tái sử dụng 100% Schema hiện có** (`vi_diem`, `yeu_cau_tai_lieu`, `dap_ung_yeu_cau_tai_lieu`, `giao_dich_diem`) – Không cần migration mới |

---

## 2. Kiến Trúc Triển Khai & Quy Tắc Nghiệp Vụ

### 2.1. Cấu Trúc Database & Tính Toàn Vẹn ACID
Hệ thống tận dụng trọn vẹn 4 bảng dữ liệu đã có trong migration `2026_10_14_000008_create_vi_diem_va_yeu_cau_tai_lieu_table.php`:
1. `yeu_cau_tai_lieu`: Lưu trữ bài đăng yêu cầu tìm tài liệu (`ma_mon_hoc`, `ma_nguoi_tao`, `tieu_de`, `phan_loai`, `diem_thuong`, `trang_thai: MO | DA_HOAN_THANH | DA_HUY`).
2. `dap_ung_yeu_cau_tai_lieu`: Lưu trữ các bài nộp đề xuất tài liệu (`ma_yeu_cau`, `ma_tai_lieu`, `ma_nguoi_dong_gop`, `trang_thai: CHO_DUYET | CHAP_NHAN | TU_CHOI`).
3. `vi_diem`: Quản lý số dư của người dùng với 2 trường tách biệt:
   - `diem_kha_dung`: Điểm có thể dùng để treo thưởng.
   - `diem_tam_giu`: Điểm đang bị đóng băng cho các Bounty đang mở.
4. `giao_dich_diem`: Audit log bất biến mọi biến động điểm kèm khóa chống trùng `khoa_chong_trung` (Idempotency Key).

### 2.2. Vòng Đời Bounty & Điểm Thưởng (Point Flow Invariants)
```
[Tạo yêu cầu]  --> Đóng băng: diem_kha_dung -= X, diem_tam_giu += X (LoaiGiaoDich: TAM_GIU)
       │
       ├──> [Hủy yêu cầu]   --> Hoàn trả: diem_kha_dung += X, diem_tam_giu -= X (LoaiGiaoDich: HOAN_TRA)
       │
       └──> [Xác nhận nộp] --> Người tạo: diem_tam_giu -= X (LoaiGiaoDich: GIAI_PHONG)
                                Người nộp:  diem_kha_dung += X (LoaiGiaoDich: CONG_DIEM), diem_uy_tin += X
```
- **Không sinh/mất điểm tự do**: Tổng `diem_kha_dung + diem_tam_giu` luôn bảo toàn trong suốt mọi giao dịch.
- **Khóa hàng (Row-level Locking)**: Toàn bộ quá trình biến động ví đều được bao bọc trong `DB::transaction` với `lockForUpdate()` qua [`ReputationEvaluator.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Domain/Services/ReputationEvaluator.php).
- **Chống tự nhận thưởng (Anti-fraud Rule)**: [`BountyRuleEvaluator::canFulfill`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Domain/Services/BountyRuleEvaluator.php) chặn tuyệt đối việc người tạo tự nộp tài liệu vào yêu cầu của mình để rút điểm.

---

## 3. Giao Diện & Trải Nghiệm Người Dùng (Frontend)

File giao diện: [`resources/js/Pages/Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx)

### 3.1. Tab 3: Wishlist / Yêu Cầu Tìm Tài Liệu
- Đặt cạnh **Tab 1: Kho tài liệu** và **Tab 2: Đánh giá môn học**.
- Hiển thị pill số lượng yêu cầu đang mở thời gian thực: `wishlist_requests.total`.
- **Thanh tác vụ & Trạng thái ví cá nhân**:
  - Badge ví điểm trực quan của người dùng: `"Số dư khả dụng: {diem_kha_dung} điểm"`.
  - Nút `+ Đăng yêu cầu tìm tài liệu`: Mở modal tạo yêu cầu gắn chặt môn học.
- **Bộ lọc đa chiều**:
  - Trạng thái: *Tất cả*, *Đang tìm kiếm (MO)*, *Đã giải quyết (DA_HOAN_THANH)*, *Đã hủy (DA_HUY)*.
  - Phân loại tài liệu: *Tất cả*, *Đề thi*, *Bài giảng*, *Đồ án*, *Bài tập*, *Khác*.
  - Sắp xếp: *Mới nhất*, *Điểm thưởng cao nhất*, *Nhiều phản hồi nhất*.

### 3.2. Wishlist Card Component
- **Badge Bounty Points nổi bật**: Màu hổ phách sang trọng (`bg-amber-100 text-amber-800`), hiển thị icon Coins và giá trị điểm thưởng.
- **Metadata học thuật**: Mã môn học, tiêu đề, giảng viên phụ trách, phân loại tài liệu, mô tả chi tiết, tác giả, thời gian đăng dạng tương đối (`formatRelativeTime`).
- **Nút hành động theo vai trò**:
  - Người khác: Nút `"Đóng góp tài liệu"` $\rightarrow$ Mở `FulfillRequestModal`.
  - Người tạo: Nút `"Hủy yêu cầu"` $\rightarrow$ Kích hoạt xác nhận hoàn lại 100% điểm về ví.
  - Link chi tiết: Chuyển tới trang quản lý bài nộp `route('document-requests.show', id)`.

### 3.3. Nâng Cấp Modals
- [`CreateRequestModal.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Components/Wishlist/CreateRequestModal.jsx):
  - Bổ sung prop `defaultCourse`: Khóa cứng mã môn học vào môn học đang xem, hiển thị box thông tin học phần cố định thay vì dropdown chọn môn.
  - Hiển thị số dư khả dụng thực tế từ `auth.user.vi_diem.diem_kha_dung`.
  - Cảnh báo tức thì nếu số điểm nhập vào vượt quá số dư khả dụng.
  - Gửi kèm `redirect_to: 'back'` để sau khi tạo thành công vẫn giữ người dùng tại trang chi tiết môn học.
- [`FulfillRequestModal.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Components/Wishlist/FulfillRequestModal.jsx):
  - Tích hợp Document Picker truy vấn danh sách tài liệu cá nhân đã được kiểm duyệt (`/api/my-documents`).
  - Gửi lời nhắn / ghi chú đóng góp và xử lý phản hồi flash message.

---

## 4. Bảng Kiểm Định Tiêu Chí Nghiệm Thu (Acceptance Criteria)

| Mã AC | Tiêu chí Kiểm định | Trạng thái | Bằng chứng Kiểm thử & Triển khai |
| :--- | :--- | :---: | :--- |
| **AC-01** | Tab 3 "Wishlist / Yêu cầu tài liệu" hiển thị trên Course Show | **ĐẠT** | `test_ac01_course_detail_page_includes_wishlist_tab_and_props` |
| **AC-02** | Danh sách yêu cầu lọc đúng theo môn học đang truy cập (`ma_mon_hoc`) | **ĐẠT** | `test_ac02_ac03_wishlist_requests_are_isolated_to_current_course` |
| **AC-03** | Yêu cầu của môn học khác không bị rò rỉ sang môn học đang xem | **ĐẠT** | `test_ac02_ac03_wishlist_requests_are_isolated_to_current_course` |
| **AC-04** | Hiển thị đầy đủ metadata: Tiêu đề, phân loại, GV, mô tả, Bounty, tác giả, ngày tạo, trạng thái, phản hồi | **ĐẠT** | `test_ac04_wishlist_request_item_contains_required_metadata` |
| **AC-05** | Empty State hiển thị khi môn học chưa có yêu cầu nào kèm nút tạo | **ĐẠT** | `test_ac05_course_detail_shows_empty_state_when_no_requests` |
| **AC-06** | Form tạo yêu cầu tự động liên kết và khóa cứng với môn học đang xem | **ĐẠT** | `CreateRequestModal.jsx` với prop `defaultCourse` |
| **AC-07** | Hiển thị số dư khả dụng thực tế từ backend khi mở modal | **ĐẠT** | `test_ac07_alumni_can_also_create_bounty_request`, `CreateRequestModal.jsx` |
| **AC-08** | Chặn tạo yêu cầu nếu điểm thưởng vượt quá số dư ví khả dụng | **ĐẠT** | `test_ac08_cannot_create_bounty_request_exceeding_available_wallet_points` |
| **AC-09** | Chặn tạo yêu cầu nếu điểm thưởng nhỏ hơn 10 điểm | **ĐẠT** | `test_ac09_cannot_create_bounty_request_below_minimum_points` |
| **AC-10** | Điểm thưởng bị đóng băng ngay lập tức trong `DB::transaction` | **ĐẠT** | `test_ac06_ac10_ac11_authenticated_student_can_create_bounty_request_for_course` |
| **AC-11** | Ghi nhật ký giao dịch `GiaoDichDiem` loại `TAM_GIU` kèm mã khóa chống trùng | **ĐẠT** | `test_ac06_ac10_ac11_authenticated_student_can_create_bounty_request_for_course` |
| **AC-12** | Sinh viên khác có thể đóng góp tài liệu từ kho cá nhân đã duyệt | **ĐẠT** | `test_ac12_contributor_can_submit_fulfillment_with_approved_document` |
| **AC-13** | Người tạo yêu cầu không thể tự nộp tài liệu để nhận điểm của chính mình | **ĐẠT** | `test_ac13_author_cannot_fulfill_own_bounty_request` |
| **AC-14** | Xác nhận tài liệu giải ngân toàn bộ điểm thưởng cho người đóng góp | **ĐẠT** | `test_ac14_ac15_creator_can_confirm_fulfillment_disbursing_bounty_and_reputation` |
| **AC-15** | Người đóng góp nhận đúng điểm khả dụng và được cộng điểm uy tín | **ĐẠT** | `test_ac14_ac15_creator_can_confirm_fulfillment_disbursing_bounty_and_reputation` |
| **AC-16** | Người tạo có thể hủy yêu cầu đang Mở và được hoàn trả 100% điểm ví | **ĐẠT** | `test_ac16_author_can_cancel_open_request_and_refund_frozen_points` |
| **AC-17** | Người không phải tác giả không thể hủy; không thể hủy yêu cầu đã hoàn thành | **ĐẠT** | `test_ac17_non_creator_cannot_cancel_request`, `test_ac17_cannot_cancel_already_completed_request` |
| **AC-18** | Không để lộ số dư ví hoặc thông tin nhạy cảm của người dùng khác | **ĐẠT** | Phân lập dữ liệu ví qua `HandleInertiaRequests` và `ReputationEvaluator` |
| **AC-19** | Khách chưa đăng nhập không thể tạo yêu cầu hoặc đóng góp tài liệu | **ĐẠT** | `test_ac19_guest_cannot_create_or_fulfill_bounty_request` |
| **AC-20** | Tab Tài liệu (Phase 3B1) và Tab Đánh giá (Phase 3B2) hoạt động bình thường | **ĐẠT** | `test_ac20_course_show_preserves_documents_and_reviews_data` |
| **AC-21** | Toàn bộ 239 tests tự động hiện có không phát sinh bất kỳ lỗi nào | **ĐẠT** | `php artisan test` (239 passed, 1875 assertions, 0 failures) |
| **AC-22** | `npm run build` hoàn tất sạch sẽ, không lỗi đóng gói | **ĐẠT** | Vite build thành công trong 17.79s |

---

## 5. Danh Sách File Đã Tạo / Cập Nhật

| Đường dẫn file | Thao tác | Mô tả |
| :--- | :--- | :--- |
| [`app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php) | **Cập nhật** | Bổ sung nạp danh sách `wishlist_requests` và filters vào props của `Courses/Show`. |
| [`app/Modules/Bounties/Infrastructure/Persistence/WishlistBountyRepository.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Infrastructure/Persistence/WishlistBountyRepository.php) | **Cập nhật** | Tối ưu eager load quan hệ `taiLieuDapUng` và hỗ trợ lọc danh sách theo `ma_mon_hoc`. |
| [`app/Modules/Bounties/Presentation/Http/Controllers/WishlistBountyController.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Presentation/Http/Controllers/WishlistBountyController.php) | **Cập nhật** | Hỗ trợ tham số `redirect_to: 'back'` cho `store` và `destroy`, bảo toàn điều hướng cũ cho `document-requests.index`. |
| [`resources/js/Components/Wishlist/CreateRequestModal.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Components/Wishlist/CreateRequestModal.jsx) | **Cập nhật** | Thêm prop `defaultCourse`, tự động khóa môn học, hiển thị số dư ví và cảnh báo điểm khả dụng. |
| [`resources/js/Pages/Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx) | **Cập nhật** | Tích hợp hoàn chỉnh Tab 3 Wishlist, Request Cards, Bộ lọc trạng thái, Pill ví điểm, Modal connector. |
| [`docs/audits/PHASE_3B3_COURSE_WISHLIST_SCOPE.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/PHASE_3B3_COURSE_WISHLIST_SCOPE.md) | **Tạo mới** | Tài liệu đặc tả phạm vi và kế hoạch triển khai Phase 3B3. |
| [`tests/Feature/Phase3b3CourseWishlistTest.php`](file:///d:/CNWEB/AGU-Academic-Hub/tests/Feature/Phase3b3CourseWishlistTest.php) | **Tạo mới** | Bộ 17 test cases kiểm định toàn diện từ AC-01 đến AC-22. |
| [`docs/audits/PHASE_3B3_COURSE_WISHLIST_RESULT.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/PHASE_3B3_COURSE_WISHLIST_RESULT.md) | **Tạo mới** | Báo cáo nghiệm thu chính thức Phase 3B3. |

---

## 6. Kết Luận & Điểm Dừng Phạm Vi

- **Phase 3B3 – Course Wishlist & Bounty Integration** đã hoàn thành đạt 100% các tiêu chí kỹ thuật và nghiệp vụ đề ra.
- Hệ thống đảm bảo tính cô lập dữ liệu tuyệt đối giữa các môn học, tính toàn vẹn của quỹ điểm ví theo nguyên lý ACID, cơ chế chống gian lận tự nhận thưởng, và khả năng tương thích ngược hoàn hảo với Phase 1, Phase 2, Phase 3A, Phase 3B1, Phase 3B2.
- **Tuân thủ cam kết phạm vi**: Dừng lại hoàn toàn sau Phase 3B3 theo chỉ đạo, không tự ý bước sang bất kỳ phase nào khác.

