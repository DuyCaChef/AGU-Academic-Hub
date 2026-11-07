# KẾ HOẠCH & PHẠM VI KHÓA – PHASE 3B3: COURSE WISHLIST & BOUNTY INTEGRATION

## 1. Mục Tiêu & Bối Cảnh

Tích hợp phân hệ **Wishlist / Yêu cầu tìm tài liệu & Săn thưởng (Bounty Points)** vào trang chi tiết môn học:
[`resources/js/Pages/Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx) (kế thừa từ Phase 3A, Phase 3B1 và Phase 3B2).

Người dùng thuộc hai phân hệ Sinh viên (`SINH_VIEN`) và Cựu sinh viên (`CUU_SINH_VIEN`) có thể:
1. Xem danh sách các yêu cầu tìm tài liệu thuộc đúng môn học đang truy cập (`ma_mon_hoc`).
2. Tạo yêu cầu tìm tài liệu mới kèm cơ chế treo thưởng điểm số (Bounty Points), trong đó môn học được tự động khóa chặt với môn học đang xem.
3. Nắm bắt thông tin yêu cầu: Tiêu đề, phân loại tài liệu, giảng viên phụ trách, mô tả chi tiết, mức điểm thưởng, người yêu cầu, thời gian tạo, trạng thái và số lượng tài liệu đã được gửi phản hồi (`dap_ungs_count`).
4. Đóng góp tài liệu giải quyết yêu cầu (Fulfillment) bằng cách chọn từ kho tài liệu đã được duyệt của cá nhân (`documents.my-documents`).
5. Xác nhận tài liệu đáp ứng hợp lệ $\rightarrow$ Hệ thống tự động giải ngân chuyển điểm thưởng Bounty cho người đóng góp trong Database Transaction.
6. Hủy yêu cầu khi còn ở trạng thái Mở (`MO`) $\rightarrow$ Hệ thống tự động hoàn trả 100% điểm đã đóng băng về ví khả dụng của người tạo trong Database Transaction.

---

## 2. Báo Cáo Khảo Sát Hiện Trạng Codebase

### 2.1. Những Gì Đã Có (Tồn tại & Hoạt động tốt)
1. **Database Schema**:
   - Bảng `vi_diem`: Quản lý ví điểm với `diem_kha_dung`, `diem_tam_giu` và ràng buộc không âm.
   - Bảng `yeu_cau_tai_lieu`: Quản lý yêu cầu với `ma_mon_hoc`, `ma_nguoi_tao`, `tieu_de`, `mo_ta`, `ten_giang_vien`, `phan_loai`, `diem_thuong`, `trang_thai` (`MO`, `DA_HOAN_THANH`, `DONG`, `DA_HUY`), `ma_tai_lieu_dap_ung`, `nguoi_xac_nhan`, `thoi_gian_xac_nhan`, Soft Deletes.
   - Bảng `dap_ung_yeu_cau_tai_lieu`: Quản lý nộp tài liệu giải quyết yêu cầu với `ma_yeu_cau`, `ma_tai_lieu`, `ma_nguoi_dong_gop`, `trang_thai` (`CHO_DUYET`, `CHAP_NHAN`, `TU_CHOI`), unique `(ma_yeu_cau, ma_tai_lieu)`.
   - Bảng `giao_dich_diem`: Sổ cái ghi log biến động với `loai_giao_dich` (`CONG_DIEM`, `TAM_GIU`, `GIAI_PHONG`, `SU_DUNG`, `HOAN_TRA`), `khoa_chong_trung` (idempotency key chống trùng giao dịch).
2. **Domain Service & Nghiệp Vụ Điểm**:
   - [`ReputationEvaluator.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Domain/Services/ReputationEvaluator.php): Khóa dòng chống race condition (`lockForUpdate`), xử lý đóng băng điểm (`dongBangDiemBounty`), giải ngân (`giaiNganDiemBounty`) và hoàn điểm (`hoanDiemBounty`).
   - [`BountyRuleEvaluator.php`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/Bounties/Domain/Services/BountyRuleEvaluator.php): Kiểm tra phân quyền tạo, nộp bài (chặn tự nộp cho bài của mình), xác nhận (chính chủ hoặc Admin/Mod), hủy bài.
3. **Application Actions & Queries**:
   - `CreateBountyRequestAction.php`, `FulfillBountyRequestAction.php`, `ConfirmBountyFulfillmentAction.php`, `CancelBountyRequestAction.php`.
   - `WishlistBountyRepository.php`: Hỗ trợ `getPaginatedWishlist` lọc theo `ma_mon_hoc` và `trang_thai`.
4. **API Endpoints**:
   - `POST /yeu-cau-tai-lieu`: Tạo yêu cầu mới.
   - `POST /yeu-cau-tai-lieu/{ma_yeu_cau}/dong-gop`: Nộp tài liệu.
   - `POST /yeu-cau-tai-lieu/{ma_yeu_cau}/xac-nhan`: Duyệt giải ngân điểm.
   - `DELETE /yeu-cau-tai-lieu/{ma_yeu_cau}`: Hủy yêu cầu hoàn điểm.
   - `GET /tai-lieu/cua-toi`: Lấy danh sách tài liệu cá nhân để nộp bounty.
5. **Frontend Components Sẵn Có**:
   - [`CreateRequestModal.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Components/Wishlist/CreateRequestModal.jsx).
   - [`FulfillRequestModal.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Components/Wishlist/FulfillRequestModal.jsx).
   - Middleware `HandleInertiaRequests`: Luôn nạp sẵn `auth.user.vi_diem.diem_kha_dung` và `diem_tam_giu` cho người dùng đăng nhập.

### 2.2. Những Gì Còn Thiếu & Cần Triển Khai
1. **Tích hợp Controller**: [`CourseController::show`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php) hiện chỉ nạp `documents` và `reviews`, chưa nạp danh sách `wishlist_requests` của môn học.
2. **Tích hợp Tab 3 trên Frontend**: [`Courses/Show.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Courses/Show.jsx) hiện có 2 tab (Tài liệu, Đánh giá), cần bổ sung Tab 3: "Wishlist / Yêu cầu tài liệu" kèm badge số lượng.
3. **Khóa Môn Học Khi Tạo Yêu Cầu**: `CreateRequestModal.jsx` hiện cho chọn môn từ dropdown `courses`. Cần nâng cấp nhận prop `defaultCourse` để tự động gán và khóa cứng `ma_mon_hoc` theo học phần đang xem, ngăn chặn client gửi sang môn học khác.
4. **Điều Hướng Phản Hồi**: Trong `WishlistBountyController@store` và `@destroy`, hiện đang hardcode `redirect()->route('document-requests.index')`. Cần hỗ trợ chuyển hướng linh hoạt (hoặc `redirect_to: 'back'`) để khi thực hiện từ trang chi tiết môn học, người dùng được giữ lại đúng trang môn học kèm flash toast message mà không phá vỡ test cũ.

### 2.3. Kết Luận Về Database
- **KHÔNG CẦN TẠO HOẶC SỬA BẤT KỲ MIGRATION NÀO**.
- Cấu trúc database hiện tại đã hoàn toàn đầy đủ, chuẩn hóa và đáp ứng 100% nghiệp vụ.

---

## 3. Nguyên Tắc Bất Biến Về Điểm Số & Giao Dịch (Core Invariants)

1. **Giao Dịch Nguyên Khối (ACID Transactions)**:
   - Mọi thao tác đóng băng, giải ngân, hoàn điểm đều chạy trong `DB::transaction`. Nếu tạo yêu cầu hoặc cập nhật trạng thái lỗi, điểm phải được rollback ngay lập tức.
2. **Khóa Dòng Chống Race Condition**:
   - Sử dụng `ViDiem::lockForUpdate()` ngăn chặn người dùng gửi nhiều request đồng thời để tiêu quá số dư khả dụng.
3. **Chống Trùng Giao Dịch (Idempotency Key)**:
   - Mọi bản ghi `giao_dich_diem` đều có `khoa_chong_trung` duy nhất. Không thể giải ngân hay hoàn điểm hai lần cho cùng một yêu cầu.
4. **Không Thao Túng Số Dư Phía Frontend**:
   - Số dư khả dụng và số điểm tạm giữ được backend kiểm soát và tính toán độc lập. Frontend chỉ hiển thị số dư nhận từ `auth.user.vi_diem`.
5. **Chặn Gian Lận Điểm Thưởng**:
   - Tác giả yêu cầu không được phép nộp tài liệu vào chính bài của mình (`canFulfill` trả về `allowed: false`).

---

## 4. Kế Hoạch Triển Khai Chi Tiết

### Bước 1: Cập nhật Backend Controller & Repository
- Bổ sung nạp `wishlist_requests` trong [`CourseController::show`](file:///d:/CNWEB/AGU-Academic-Hub/app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php) lọc theo `ma_mon_hoc = $course->ma_mon_hoc`.
- Bổ sung tham số `redirect_to` cho `WishlistBountyController@store` và `@destroy` để tương thích cả `document-requests.index` và `courses.show`.

### Bước 2: Nâng cấp Modal Tạo Yêu Cầu
- Cập nhật [`CreateRequestModal.jsx`](file:///d:/CNWEB/AGU-Academic-Hub/resources/js/Components/Wishlist/CreateRequestModal.jsx):
  - Nhận prop `defaultCourse` (nếu có): tự động khóa `ma_mon_hoc`, hiển thị thông tin học phần cố định.
  - Hiển thị ví điểm khả dụng, cảnh báo nếu số dư không đủ.

### Bước 3: Tích hợp Giao diện Tab 3 trong `Courses/Show.jsx`
- Bổ sung Tab `Wishlist / Yêu cầu tài liệu` cạnh Tab `Tài liệu` và Tab `Đánh giá`.
- Hiển thị danh sách card yêu cầu:
  - Tên yêu cầu, phân loại, giảng viên, mô tả.
  - Badge điểm thưởng Bounty nổi bật.
  - Tác giả, thời gian tạo, trạng thái (`MO`, `DA_HOAN_THANH`).
  - Số lượng bài nộp phản hồi (`dap_ungs_count`).
  - Nút "Đóng góp tài liệu" (cho người khác) $\rightarrow$ Mở `FulfillRequestModal`.
  - Nút "Hủy yêu cầu" (cho người tạo) $\rightarrow$ Hoàn điểm.
  - Link chi tiết `route('document-requests.show', id)`.
- Bộ lọc trạng thái (Mở / Đã hoàn thành / Tất cả).
- Empty State thân thiện khi chưa có yêu cầu nào kèm nút `+ Tạo yêu cầu tài liệu`.

### Bước 4: Viết Bộ Test Tự Động Toàn Diện
- Tạo file [`tests/Feature/Phase3b3CourseWishlistTest.php`](file:///d:/CNWEB/AGU-Academic-Hub/tests/Feature/Phase3b3CourseWishlistTest.php) bao phủ đầy đủ 5 nhóm:
  - Nhóm A: Danh sách & Hiển thị đúng môn học.
  - Nhóm B: Tạo yêu cầu & validation & khóa môn học.
  - Nhóm C: Giữ điểm, hoàn điểm, giải ngân trong transaction.
  - Nhóm D: Fulfillment & chống tự nhận thưởng.
  - Nhóm E: Regression & bảo toàn các phase trước.

---

## 5. Danh Mục Tiêu Chí Nghiệm Thu (Acceptance Criteria)

| Mã AC | Nhóm | Tiêu chí Kiểm định |
| :--- | :--- | :--- |
| **AC-01** | UI | Trang chi tiết môn học hiển thị đầy đủ Tab 3: "Yêu cầu tài liệu (Wishlist)" cạnh 2 tab cũ. |
| **AC-02** | Data | Danh sách chỉ hiển thị yêu cầu thuộc đúng môn học đang truy cập (`ma_mon_hoc`). |
| **AC-03** | Data | Không hiển thị yêu cầu của môn học khác trong danh sách. |
| **AC-04** | UI | Hiển thị đầy đủ metadata: Tiêu đề, phân loại, giảng viên, mô tả, Bounty points, tác giả, ngày tạo, trạng thái, số lượt phản hồi. |
| **AC-05** | UI | Hiển thị Empty State rõ ràng khi môn học chưa có yêu cầu nào kèm nút tạo yêu cầu. |
| **AC-06** | Form | Form tạo yêu cầu tự động liên kết và khóa chặt với môn học đang xem. |
| **AC-07** | Wallet | Hiển thị số dư khả dụng thực tế của người dùng từ backend khi mở modal tạo yêu cầu. |
| **AC-08** | Validation | Chặn tạo yêu cầu nếu điểm thưởng lớn hơn số dư khả dụng trong ví. |
| **AC-09** | Validation | Chặn tạo yêu cầu nếu điểm thưởng nhỏ hơn mức tối thiểu (10 điểm). |
| **AC-10** | Transaction | Điểm thưởng bị đóng băng ngay lập tức trong `DB::transaction` khi tạo yêu cầu thành công. |
| **AC-11** | Idempotency | Ghi nhật ký giao dịch `GiaoDichDiem` loại `TAM_GIU` kèm mã khóa chống trùng. |
| **AC-12** | Fulfillment | Sinh viên khác có thể đóng góp tài liệu từ kho cá nhân để giải quyết yêu cầu. |
| **AC-13** | Rule | Người tạo yêu cầu không thể tự nộp tài liệu để nhận điểm thưởng của chính mình. |
| **AC-14** | Resolution | Xác nhận tài liệu đáp ứng giải ngân toàn bộ điểm thưởng cho người đóng góp trong transaction. |
| **AC-15** | Resolution | Người đóng góp nhận đúng số điểm khả dụng và được cộng điểm uy tín tương ứng. |
| **AC-16** | Cancel | Người tạo có thể hủy yêu cầu đang Mở và được hoàn trả 100% điểm đã đóng băng vào ví. |
| **AC-17** | Idempotency | Không thể hoàn điểm hoặc giải ngân hai lần cho cùng một yêu cầu. |
| **AC-18** | Privacy | Không để lộ số dư ví hoặc thông tin nhạy cảm của người dùng khác. |
| **AC-19** | Auth | Khách chưa đăng nhập không thể tạo yêu cầu hoặc đóng góp tài liệu. |
| **AC-20** | Regression | Tab Tài liệu (Phase 3B1) và Tab Đánh giá (Phase 3B2) hoạt động bình thường. |
| **AC-21** | Regression | Toàn bộ 222 tests tự động hiện có không phát sinh bất kỳ lỗi nào. |
| **AC-22** | Build | `npm run build` hoàn tất sạch sẽ, không lỗi đóng gói. |

