# PHASE 2: WALLET & BOUNTY (VÍ ĐIỂM VÀ THỊ TRƯỜNG SĂN THƯỞNG)
## AGU Academic Hub — Implementation Phase 2 Specification

---

| Thông Tin Phase | Chi Tiết |
| :--- | :--- |
| **Giai Đoạn** | Phase 2: Wallet & Bounty |
| **Mục Tiêu** | Hoàn thiện bảng lịch sử giao dịch điểm ví, kết nối shortcut từ Dashboard, chuẩn hóa phân trang Wishlist và tối ưu hóa trải nghiệm nộp tài liệu Bounty |
| **Số Lượng Task** | 5 Tasks (**TSK-201**, **TSK-202**, **TSK-203**, **TSK-204**, **TSK-205**) |
| **Tổng Thời Gian Ước Lượng** | **13.5 Giờ** |
| **Trọng Tâm** | Giao dịch điểm, Săn thưởng tài liệu, Đóng băng / Giải ngân điểm ví |

---

### TASK TSK-201: Xây Dựng Query Lịch Sử Giao Dịch Điểm Ví

1. **Task ID:** `TSK-201`
2. **Module:** M07 / Backend (Wallet / Bounties)
3. **Mục tiêu:** Tạo Application Query `GetWalletDataQuery` cung cấp dữ liệu số dư ví chi tiết và danh sách lịch sử biến động điểm phân trang từ bảng `giao_dich_diem` truyền sang `WalletController@index`.
4. **Hiện trạng:**
   - `WalletController.php` hiện chỉ có 1 dòng: `return Inertia::render('Wallet/Index');` hoàn toàn không có dữ liệu props nào được truyền sang.
   - Bảng `giao_dich_diem` trong CSDL đã có đầy đủ các cột: `ma_giao_dich`, `ma_vi`, `loai_giao_dich`, `so_diem`, `so_du_truoc`, `so_du_sau`, `mo_ta`, `created_at`.
5. **File cần đọc:**
   - `app/Modules/Bounties/Presentation/Http/Controllers/WalletController.php`
   - `app/Models/ViDiem.php`
   - `app/Models/GiaoDichDiem.php`
   - `app/Enums/LoaiGiaoDichDiem.php`
6. **File cần sửa hoặc tạo:**
   - Tạo mới: `app/Modules/Bounties/Application/Queries/GetWalletDataQuery.php`
   - Sửa: `app/Modules/Bounties/Presentation/Http/Controllers/WalletController.php`
7. **Backend dependency:** Bảng `vi_diem` và `giao_dich_diem`.
8. **Data Contract:**
   - *Props truyền sang `Wallet/Index`:*
     ```json
     {
       "wallet": {
         "diem_kha_dung": 120,
         "diem_tam_giu": 30,
         "tong_diem": 150,
         "diem_uy_tin": 110
       },
       "transactions": {
         "data": [
           {
             "ma_giao_dich": 1,
             "loai_giao_dich": "TRA_THUONG_BOUNTY",
             "ten_loai_giao_dich": "Nhận thưởng Bounty",
             "so_diem": 50,
             "la_tang_diem": true,
             "so_du_sau": 120,
             "mo_ta": "Nhận thưởng đóng góp tài liệu môn COS101",
             "created_at": "2024-10-20T14:20:00Z"
           }
         ],
         "links": [ ... ]
       },
       "filters": {
         "type": "ALL"
       }
     }
     ```
9. **Các bước triển khai:**
   - *Bước 1:* Tạo `app/Modules/Bounties/Application/Queries/GetWalletDataQuery.php`.
   - *Bước 2:* Viết logic query: Lấy ví điểm của `$user`, eager load quan hệ `giaoDichDiems` có lọc theo loại giao dịch nếu có tham số `type`, sắp xếp `latest('created_at')`, phân trang 15 bản ghi/trang.
   - *Bước 3:* Trong `WalletController.php`, inject query và truyền dữ liệu sang view:
     ```php
     public function index(Request $request, GetWalletDataQuery $query): Response
     {
         return Inertia::render('Wallet/Index', $query->execute($request->user(), $request->all()));
     }
     ```
10. **Acceptance Criteria (AC):**
    - [ ] `WalletController@index` truyền đầy đủ props `wallet`, `transactions`, `filters`.
    - [ ] Các loại giao dịch được format nhãn tiếng Việt rõ ràng (`DONG_BANG_BOUNTY` $\to$ "Treo thưởng Bounty", `TRA_THUONG_BOUNTY` $\to$ "Nhận thưởng",...).
11. **Test Cases:**
    - `TC-WAL-02`: Đăng nhập sinh viên có giao dịch $\to$ Controller trả về đầy đủ mảng transactions.
12. **Priority:** **`P1` (High)**
13. **Dependency Task:** `TSK-002`.
14. **Estimated Effort:** **3.0 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Sinh viên mới chưa có giao dịch nào thì `transactions.data` là mảng rỗng `[]`.
    - *Giải pháp:* Đảm bảo LengthAwarePaginator trả về mảng rỗng an toàn, không gây crash frontend.

---

### TASK TSK-202: Hoàn Thiện Giao Diện Ví Điểm Cá Nhân & Bộ Lọc Giao Dịch

1. **Task ID:** `TSK-202`
2. **Module:** M07 (Wallet)
3. **Mục tiêu:** Nâng cấp file `resources/js/Pages/Wallet/Index.jsx` từ Shell cơ bản (ở Phase 0) thành trang quản lý ví điểm hoàn chỉnh, có bộ lọc giao dịch và bảng biến động điểm trực quan.
4. **Hiện trạng:**
   - Ở Task TSK-002, `Wallet/Index.jsx` mới chỉ là khung Shell hiển thị số dư tĩnh.
   - Chưa hiển thị bảng lịch sử biến động, chưa có màu sắc phân biệt điểm cộng/trừ, chưa có bộ lọc loại giao dịch.
5. **File cần đọc:**
   - `resources/js/Pages/Wallet/Index.jsx`
   - `resources/js/Components/Common/Pagination.jsx`
   - `resources/js/Layouts/StudentLayout.jsx`
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Wallet/Index.jsx`
7. **Backend dependency:** `TSK-201` (`GetWalletDataQuery`).
8. **Data Contract:** Nhận props `wallet`, `transactions`, `filters` từ `TSK-201`.
9. **Các bước triển khai:**
   - *Bước 1:* Cập nhật component `Wallet/Index.jsx` nhận các props `wallet`, `transactions = { data: [] }`, `filters = {}`.
   - *Bước 2:* Dựng thanh tab bộ lọc: "Tất cả", "Nhận thưởng (+)", "Đóng băng / Chi tiêu (-)". Bấm tab nào sẽ gọi `router.get(route('wallet.index'), { type: newType }, { preserveState: true })`.
   - *Bước 3:* Dựng Bảng Lịch sử biến động:
     - Cột 1: Thời gian giao dịch (định dạng ngày giờ Việt Nam).
     - Cột 2: Loại giao dịch & Mô tả chi tiết.
     - Cột 3: Biến động điểm: Màu xanh lá đậm kèm dấu `+` nếu nhận thưởng; Màu đỏ kèm dấu `-` nếu đóng băng/chi tiêu.
     - Cột 4: Số dư sau giao dịch.
   - *Bước 4:* Nếu `transactions.data` rỗng: Hiển thị Empty State với icon ví điểm và thông báo "Chưa có biến động điểm nào phát sinh".
   - *Bước 5:* Tích hợp component `Pagination` vào cuối bảng.
10. **Acceptance Criteria (AC):**
    - [ ] Trang ví điểm hiển thị trực quan các thẻ số dư và bảng giao dịch rõ ràng.
    - [ ] Lọc theo loại biến động phản hồi mượt mà, phân trang chuyển trang chuẩn xác.
    - [ ] Số điểm cộng/trừ có màu sắc và ký hiệu phân biệt trực quan.
11. **Test Cases:**
    - `TC-WAL-03`: Kiểm tra bảng hiển thị đúng các dòng giao dịch từ CSDL.
    - `TC-WAL-04`: Bấm lọc "Nhận thưởng" $\to$ chỉ hiển thị các giao dịch cộng điểm.
12. **Priority:** **`P1` (High)**
13. **Dependency Task:** `TSK-201`.
14. **Estimated Effort:** **4.0 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Bảng giao dịch bị tràn ngang trên màn hình điện thoại.
    - *Giải pháp:* Thiết kế dạng thẻ danh sách rút gọn trên mobile (`sm:hidden`) và dạng bảng đầy đủ trên desktop (`hidden sm:table`).

---

### TASK TSK-203: Chuẩn Hóa Link Shortcut Ví Điểm Trên Dashboard

1. **Task ID:** `TSK-203`
2. **Module:** M01 (Dashboard)
3. **Mục tiêu:** Khắc phục lỗi sai đường dẫn tại thẻ hiển thị ví điểm trên Dashboard, dẫn người dùng đến đúng trang `/vi-diem`.
4. **Hiện trạng:**
   - Trong `resources/js/Pages/Dashboard.jsx` dòng 218-232:
     ```jsx
     {user?.vi_diem && (
         <Link
             href={route("document-requests.index")}
             className="..."
         >
             <span>Ví khả dụng:</span>
             <span>{user.vi_diem.diem_kha_dung || 0} pts &rarr;</span>
         </Link>
     )}
     ```
   - Thẻ hiển thị số dư ví nhưng khi click vào lại dẫn sang thị trường săn thưởng `/yeu-cau-tai-lieu` thay vì trang ví cá nhân.
5. **File cần đọc:**
   - `resources/js/Pages/Dashboard.jsx:218-232`
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Dashboard.jsx`
7. **Backend dependency:** Tuyến `wallet.index` (`GET /vi-diem`).
8. **Data Contract:** Không thay đổi data contract.
9. **Các bước triển khai:**
   - *Bước 1:* Trong `Dashboard.jsx`, sửa thuộc tính `href` của thẻ shortcut từ:
     `route("document-requests.index")` $\to$ `route("wallet.index")`.
   - *Bước 2:* Thêm title tooltip mô tả: "Xem chi tiết số dư và lịch sử biến động ví điểm".
10. **Acceptance Criteria (AC):**
    - [ ] Click vào thẻ số dư ví điểm trên Dashboard chuyển hướng ngay lập tức sang `/vi-diem`.
11. **Test Cases:**
    - `TC-WAL-01`: Click shortcut trên Dashboard $\to$ mở đúng trang `Wallet/Index`.
12. **Priority:** **`P2` (Medium)**
13. **Dependency Task:** `TSK-202`.
14. **Estimated Effort:** **1.0 Giờ**
15. **Risk & Mitigation:** Không có rủi ro kỹ thuật.

---

### TASK TSK-204: Tái Sử Dụng Component Pagination Trong `Wishlist/Index`

1. **Task ID:** `TSK-204`
2. **Module:** M05 (Wishlist)
3. **Mục tiêu:** Xóa bỏ khối mã nguồn JSX phân trang tự viết lặp lại trong `resources/js/Pages/Wishlist/Index.jsx`, thay thế bằng component chuẩn dùng chung `resources/js/Components/Common/Pagination.jsx`.
4. **Hiện trạng:**
   - Trong `resources/js/Pages/Wishlist/Index.jsx` dòng 291-325 có hơn 30 dòng code lặp lại việc map mảng `paginationLinks` và gán style thủ công.
   - Hệ thống đã có sẵn component `Pagination.jsx` chuẩn hóa responsive và icon Lucide.
5. **File cần đọc:**
   - `resources/js/Pages/Wishlist/Index.jsx:291-325`
   - `resources/js/Components/Common/Pagination.jsx`
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Wishlist/Index.jsx`
7. **Backend dependency:** `WishlistBountyController@index` (`requests.links`).
8. **Data Contract:** Prop `requests.links` tương thích 100% với `Pagination.jsx`.
9. **Các bước triển khai:**
   - *Bước 1:* Import `Pagination` từ `@/Components/Common/Pagination`.
   - *Bước 2:* Xóa toàn bộ khối render thủ công từ dòng 291 đến 325 trong `Wishlist/Index.jsx`.
   - *Bước 3:* Thay thế bằng `<Pagination links={requests.links} className="mt-6" />`.
10. **Acceptance Criteria (AC):**
    - [ ] Thanh phân trang trên `Wishlist/Index` hiển thị đồng bộ với giao diện toàn hệ thống.
    - [ ] Chuyển trang giữ nguyên trạng thái bộ lọc (Preserve state).
11. **Test Cases:**
    - `TC-CTR-05`: Click chuyển trang danh sách yêu cầu tài liệu $\to$ chuyển trang thành công.
12. **Priority:** **`P2` (Medium - Technical Debt Cleanup)**
13. **Dependency Task:** Không có.
14. **Estimated Effort:** **1.5 Giờ**
15. **Risk & Mitigation:** Không có rủi ro kỹ thuật.

---

### TASK TSK-205: Xây Dựng Document Picker Trong `FulfillRequestModal`

1. **Task ID:** `TSK-205`
2. **Module:** M06 (Bounty)
3. **Mục tiêu:** Cải tiến modal đóng góp tài liệu săn thưởng `FulfillRequestModal.jsx` cho phép người dùng chọn trực quan từ danh sách tài liệu cá nhân đã tải lên thay vì bắt buộc nhớ và nhập tay số ID tài liệu.
4. **Hiện trạng:**
   - Trong `FulfillRequestModal.jsx` dòng 42, form yêu cầu người dùng:
     `<input type="number" placeholder="Nhập mã ID tài liệu của bạn..." value={data.ma_tai_lieu} />`.
   - Sinh viên không nhớ mã số ID của tài liệu mình đã đăng, dẫn đến trải nghiệm rất bất tiện.
5. **File cần đọc:**
   - `resources/js/Components/Wishlist/FulfillRequestModal.jsx`
   - `app/Modules/Bounties/Presentation/Http/Controllers/WishlistBountyController.php`
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Components/Wishlist/FulfillRequestModal.jsx`
7. **Backend dependency:**
   - **`BLOCKED`**: Cần backend bổ sung một API endpoint gọn nhẹ `GET /api/tai-lieu-ca-nhan` hoặc truyền prop `myDocuments` trả về danh sách các tài liệu thuộc quyền sở hữu của người dùng hiện tại có trạng thái `DA_DUYET`.
8. **Data Contract:**
   - *Danh sách tài liệu cần nạp:*
     ```json
     [
       { "ma_tai_lieu": 12, "tieu_de": "Đề thi Toán cao cấp A1 2023", "ma_hoc_phan": "MAT101" }
     ]
     ```
9. **Các bước triển khai:**
   - *Bước 1 (Chờ Backend unblock):* Khi có API hoặc prop `myDocuments`:
   - *Bước 2:* Dựng dropdown / list selector: Cho phép tìm kiếm nhanh theo tiêu đề tài liệu cá nhân.
   - *Bước 3:* Khi người dùng chọn 1 tài liệu trong danh sách $\to$ tự động điền `ma_tai_lieu` vào form.
   - *Bước 4:* Vẫn giữ ô nhập tay ID tài liệu ở chế độ nâng cao (Advanced Mode) làm phương án dự phòng.
10. **Acceptance Criteria (AC):**
    - [ ] Modal hiển thị danh sách tài liệu cá nhân để người dùng bấm chọn nhanh 1-click.
    - [ ] Gửi yêu cầu đáp ứng thành công mà người dùng không cần mở tab khác để tra cứu ID tài liệu.
11. **Test Cases:**
    - `TC-BNT-04`: Mở modal đáp ứng yêu cầu, chọn tài liệu có sẵn $\to$ nộp thành công.
12. **Priority:** **`P2` (Medium)**
13. **Dependency Task:** `BLOCKED` (Chờ Backend cung cấp API `myDocuments`).
14. **Estimated Effort:** **4.0 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Người dùng chưa từng đăng tài liệu nào lên hệ thống.
    - *Giải pháp:* Hiển thị thông báo "Bạn chưa có tài liệu nào trong kho" kèm nút "Đăng tài liệu mới ngay" mở trang upload tài liệu trên tab mới.

