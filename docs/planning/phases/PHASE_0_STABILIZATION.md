# PHASE 0: STABILIZATION (NỀN TẢNG VÀ ỔN ĐỊNH HỆ THỐNG)
## AGU Academic Hub — Implementation Phase 0 Specification

---

| Thông Tin Phase | Chi Tiết |
| :--- | :--- |
| **Giai Đoạn** | Phase 0: Stabilization |
| **Mục Tiêu** | Khắc phục triệt để lỗi Build Vite P0, chặn đứng nguy cơ Crash Inertia Runtime 404 và đồng bộ Data Contract người dùng cốt lõi |
| **Số Lượng Task** | 3 Tasks (**TSK-001**, **TSK-002**, **TSK-003**) |
| **Tổng Thời Gian Ước Lượng** | **5.0 Giờ** |
| **Trọng Tâm** | Hạ tầng Frontend, Tính toàn vẹn Route, Rào chắn lỗi Runtime |

---

### TASK TSK-001: Khắc Phục Lỗi Build Vite Dependency (`canvas-confetti`)

1. **Task ID:** `TSK-001`
2. **Module:** M06 (Bounty / Infrastructure)
3. **Mục tiêu:** Đảm bảo lệnh `npm run build` chạy thành công 100%, không bị Rollup chặn đứng bởi lỗi missing module `canvas-confetti` trong file `Wishlist/Show.jsx`.
4. **Hiện trạng:**
   - File `resources/js/Pages/Wishlist/Show.jsx:5` gọi lệnh `import confetti from 'canvas-confetti';`.
   - File `package.json` có dòng `"canvas-confetti": "^1.9.4"`.
   - Tuy nhiên thư mục `node_modules/canvas-confetti` không tồn tại trên ổ đĩa.
   - Khi chạy `npm run build`, Rollup báo lỗi: `Rollup failed to resolve import "canvas-confetti" from "D:/CNWEB/AGU-Academic-Hub/resources/js/Pages/Wishlist/Show.jsx"`.
5. **File cần đọc:**
   - `package.json`
   - `vite.config.js`
   - `resources/js/Pages/Wishlist/Show.jsx`
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Wishlist/Show.jsx` (bọc dynamic import hoặc fallback an toàn)
   - Hoặc cài đặt package qua `npm install canvas-confetti` (khi bắt đầu bước triển khai)
7. **Backend dependency:** Không có (Backend API bounty confirm đã hoàn thiện).
8. **Data Contract:** Không ảnh hưởng đến dữ liệu props của `Wishlist/Show.jsx`.
9. **Các bước triển khai:**
   - *Bước 1:* Chạy lệnh `npm install canvas-confetti` trong môi trường node để đưa module vào `node_modules/`.
   - *Bước 2:* Trong `resources/js/Pages/Wishlist/Show.jsx`, kiểm tra hàm `triggerConfettiEffect`. Nếu muốn tăng tính bền vững, thay thế bằng hàm dynamic import:
     ```javascript
     const triggerConfettiEffect = async () => {
         try {
             const confettiModule = await import('canvas-confetti');
             const confetti = confettiModule.default || confettiModule;
             confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
         } catch (e) {
             console.warn('Confetti effect could not be loaded:', e);
         }
     };
     ```
   - *Bước 3:* Chạy kiểm tra lệnh `npm run build` trên terminal.
10. **Acceptance Criteria (AC):**
    - [ ] `npm run build` hoàn thành với mã thoát 0 (Exit Code 0).
    - [ ] Thư mục `public/build/assets/` sinh ra đầy đủ file `.js` và `.css`.
    - [ ] Trang `Wishlist/Show.jsx` tải bình thường trên trình duyệt mà không ném exception.
11. **Test Cases:**
    - `TC-BLD-01`: Thực thi `npm run build`, kết quả không có lỗi Rollup resolution.
    - `TC-BNT-05`: Bấm xác nhận nghiệm thu Bounty trên `Wishlist/Show`, hiệu ứng pháo hoa hiển thị hoặc fallback im lặng mà không làm sập trang.
12. **Priority:** **`P0` (Blocker)**
13. **Dependency Task:** Không có (Task độc lập, mở đường cho toàn bộ dự án).
14. **Estimated Effort:** **1.0 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Môi trường CI/CD không có quyền kết nối Internet để tải package npm.
    - *Giải pháp:* Sử dụng phương án dynamic import có `try...catch` an toàn hoặc tự viết hàm canvas bắn pháo hoa thủ công 20 dòng không phụ thuộc thư viện ngoài.

---

### TASK TSK-002: Khởi Tạo Shell Giao Diện Ví Điểm (`Wallet/Index.jsx`)

1. **Task ID:** `TSK-002`
2. **Module:** M07 (Wallet)
3. **Mục tiêu:** Tạo component `resources/js/Pages/Wallet/Index.jsx` đạt chuẩn giao diện `StudentLayout`, loại bỏ hoàn toàn lỗi Fatal Crash 404 khi người dùng truy cập route `/vi-diem`.
4. **Hiện trạng:**
   - File `app/Modules/Bounties/Presentation/Http/Controllers/WalletController.php` dòng 14 thực hiện lệnh:
     `return Inertia::render('Wallet/Index');`
   - Thư mục `resources/js/Pages/Wallet/` và file `Index.jsx` chưa từng được tạo.
   - Bất kỳ click nào vào link Ví điểm trên Dashboard hoặc Header đều làm sập ứng dụng React.
5. **File cần đọc:**
   - `app/Modules/Bounties/Presentation/Http/Controllers/WalletController.php`
   - `resources/js/Layouts/StudentLayout.jsx`
   - `resources/js/Pages/Dashboard.jsx`
6. **File cần sửa hoặc tạo:**
   - Tạo mới: `resources/js/Pages/Wallet/Index.jsx`
7. **Backend dependency:**
   - Tạm thời sử dụng dữ liệu ví sẵn có từ global prop `auth.user.vi_diem` do `HandleInertiaRequests` chia sẻ.
8. **Data Contract:**
   - *Input props:* Nhận dữ liệu user từ `usePage().props.auth.user`:
     ```json
     {
       "vi_diem": {
         "diem_kha_dung": 100,
         "diem_tam_giu": 0
       },
       "diem_uy_tin": 100
     }
     ```
   - *Trạng thái:*
     - Khả dụng: Điểm sẵn sàng đặt cọc Bounty.
     - Tạm giữ: Điểm đang bị phong tỏa trong các bài đăng tìm tài liệu chưa nghiệm thu.
9. **Các bước triển khai:**
   - *Bước 1:* Tạo thư mục `resources/js/Pages/Wallet/` và file `Index.jsx`.
   - *Bước 2:* Xây dựng cấu trúc component bao bọc bởi `<StudentLayout title="Ví điểm cá nhân">`.
   - *Bước 3:* Thiết kế 3 thẻ thống kê chính:
     - Thẻ 1: **Điểm khả dụng** (Màu xanh thương hiệu AGU `#005baa`, icon Coins).
     - Thẻ 2: **Điểm đang tạm giữ (Escrow)** (Màu hổ phách `amber-600`, icon Clock / Lock).
     - Thẻ 3: **Điểm uy tín học thuật** (Màu xanh lá `emerald-600`, icon ShieldCheck).
   - *Bước 4:* Thiết kế khung Shell sẵn sàng cho bảng lịch sử giao dịch (sẽ hoàn thiện dữ liệu ở Phase 2).
   - *Bước 5:* Kiểm tra điều hướng từ Header và truy cập trực tiếp URL `/vi-diem`.
10. **Acceptance Criteria (AC):**
    - [ ] Truy cập `GET /vi-diem` trả về mã HTTP 200, hiển thị giao diện Ví Điểm với nhận diện thương hiệu AGU.
    - [ ] Không phát sinh lỗi Inertia Page Not Found.
    - [ ] Hiển thị chính xác số dư `diem_kha_dung` và `diem_tam_giu` của tài khoản hiện tại.
11. **Test Cases:**
    - `TC-WAL-01`: Sinh viên đăng nhập, click vào số dư ví ở góc Header $\to$ chuyển hướng mượt mà vào trang `/vi-diem`.
    - `TC-WAL-02`: Đối chiếu số dư trên thẻ với bản ghi trong CSDL bảng `vi_diem`.
12. **Priority:** **`P1` (High - Critical Defect)**
13. **Dependency Task:** Không có (Bắt đầu được ngay).
14. **Estimated Effort:** **2.5 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Người dùng vừa tạo tài khoản chưa có bản ghi `vi_diem` trong CSDL khiến `user.vi_diem` bị `null`.
    - *Giải pháp:* Luôn dùng cú pháp Optional Chaining kèm giá trị mặc định: `user?.vi_diem?.diem_kha_dung ?? 0`.

---

### TASK TSK-003: Đồng Bộ Data Contract Tên Người Dùng (`ho_ten` vs `name`)

1. **Task ID:** `TSK-003`
2. **Module:** M08 (Profile / Core Auth)
3. **Mục tiêu:** Đồng bộ hóa trường hiển thị tên người dùng giữa Backend Laravel (`HandleInertiaRequests`) và các view React Frontend, xóa bỏ lỗi input uncontrolled và lỗi trắng họ tên khi người dùng vào sửa hồ sơ.
4. **Hiện trạng:**
   - Backend `app/Http/Middleware/HandleInertiaRequests.php` dòng 47 cung cấp mảng `auth.user` có khóa `ho_ten`, không có khóa `name`.
   - File `resources/js/Layouts/AuthenticatedLayout.jsx` dòng 47 gọi `{user.name}`, dẫn đến giá trị `undefined`.
   - File `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.jsx` dòng 16 gọi:
     ```javascript
     const { data, setData, patch } = useForm({
         name: user.name, // undefined
         email: user.email,
     });
     ```
   - Điều này khiến input họ tên bị rỗng và phát sinh cảnh báo React Uncontrolled Component.
5. **File cần đọc:**
   - `app/Http/Middleware/HandleInertiaRequests.php`
   - `resources/js/Layouts/AuthenticatedLayout.jsx`
   - `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.jsx`
   - `app/Modules/Identity/Presentation/Http/Requests/ProfileUpdateRequest.php`
6. **File cần sửa hoặc tạo:**
   - Sửa: `app/Http/Middleware/HandleInertiaRequests.php` (bổ sung alias `name`)
   - Sửa: `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.jsx` (hỗ trợ đọc `user.ho_ten || user.name || ''`)
7. **Backend dependency:** `HandleInertiaRequests` (tương thích ngược).
8. **Data Contract:**
   - *Chuẩn hóa Data Contract của `auth.user`:*
     ```json
     {
       "id": 1,
       "ma_nguoi_dung": 1,
       "ho_ten": "Nguyễn Văn A",
       "name": "Nguyễn Văn A",
       "email": "b2001234@student.agu.edu.vn",
       "mssv": "B2001234"
     }
     ```
9. **Các bước triển khai:**
   - *Bước 1:* Trong `app/Http/Middleware/HandleInertiaRequests.php`, tại khối map `user`, thêm trường alias:
     `'name' => $user->ho_ten,`
     (giữ nguyên `'ho_ten' => $user->ho_ten` để không phá vỡ các component khác).
   - *Bước 2:* Trong `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.jsx`:
     Cập nhật khởi tạo form:
     `name: user.ho_ten || user.name || '',`
   - *Bước 3:* Kiểm tra form tại route `/ho-so`.
10. **Acceptance Criteria (AC):**
    - [ ] Khi đăng nhập, tên người dùng hiển thị đầy đủ, không bao giờ nhận giá trị `undefined`.
    - [ ] Trang `/ho-so` tải lên với ô Họ và tên được điền sẵn tên hiện tại của tài khoản.
    - [ ] Console không xuất hiện cảnh báo React: `component is changing an uncontrolled input to be controlled`.
11. **Test Cases:**
    - `TC-CTR-01`: Đăng nhập bằng tài khoản sinh viên `B2001234`, kiểm tra header hiển thị đúng họ tên.
    - `TC-CTR-02`: Truy cập `/ho-so`, kiểm tra input Họ và tên có giá trị, sửa thử thành tên mới và bấm Lưu $\to$ cập nhật thành công.
12. **Priority:** **`P1` (High)**
13. **Dependency Task:** Không có.
14. **Estimated Effort:** **1.5 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Cần bảo toàn tương thích với các view của Admin hiện đang sử dụng `ho_ten`.
    - *Giải pháp:* Cung cấp song song cả 2 trường `name` và `ho_ten` từ Backend middleware.

