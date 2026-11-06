# PHASE 4: POLISH & INTEGRATION TESTING (HOÀN THIỆN VÀ KIỂM THỬ TÍCH HỢP)
## AGU Academic Hub — Implementation Phase 4 Specification

---

| Thông Tin Phase | Chi Tiết |
| :--- | :--- |
| **Giai Đoạn** | Phase 4: Polish & Integration Testing |
| **Mục Tiêu** | Dọn dẹp sạch sẽ nợ kỹ thuật mồ côi, làm mới trang chủ chào đón AGU Academic Hub và chạy toàn diện bộ kiểm thử nghiệm thu E2E |
| **Số Lượng Task** | 3 Tasks (**TSK-401**, **TSK-402**, **TSK-403**) |
| **Tổng Thời Gian Ước Lượng** | **12.0 Giờ** |
| **Trọng Tâm** | Dọn dẹp nợ kỹ thuật, Trải nghiệm Landing Page, Nghiệm thu chất lượng (QA Sign-off) |

---

### TASK TSK-401: Dọn Dẹp An Toàn Các File Mồ Côi (`Courses/Index`, `Home`, `AppLayout`)

1. **Task ID:** `TSK-401`
2. **Module:** M10 / Clean (Technical Debt Removal)
3. **Mục tiêu:** Xóa bỏ an toàn 3 file di sản từ kiến trúc MVC cũ không còn được bất kỳ route nào sử dụng trong hệ thống Modular Monolith hiện tại, giảm dung lượng bundle và tránh gây hiểu lầm cho các lập trình viên khác.
4. **Hiện trạng:**
   - `resources/js/Pages/Courses/Index.jsx`: Trang danh mục môn học cũ, gọi `AppLayout`. Tuyến `courses.index` hiện đã chuyển sang dùng `Documents/Index.jsx`.
   - `resources/js/Pages/Home.jsx`: Trang chủ cũ, gọi `AppLayout`. Tuyến `home` hiện dùng `Welcome.jsx`.
   - `resources/js/Layouts/AppLayout.jsx`: Layout cũ trước refactor, chỉ được import bởi 2 trang trên.
5. **File cần đọc:**
   - `routes/web.php`
   - `app/Modules/AcademicCatalog/routes.php`
   - `resources/js/Pages/Courses/Index.jsx`
   - `resources/js/Pages/Home.jsx`
   - `resources/js/Layouts/AppLayout.jsx`
6. **File cần sửa hoặc tạo:**
   - Xóa bỏ an toàn:
     - `resources/js/Pages/Courses/Index.jsx`
     - `resources/js/Pages/Home.jsx`
     - `resources/js/Layouts/AppLayout.jsx`
7. **Backend dependency:** Không có.
8. **Data Contract:** Không ảnh hưởng đến bất kỳ data contract nào.
9. **Các bước triển khai:**
   - *Bước 1:* Dùng lệnh tìm kiếm toàn dự án (`grep` / `Select-String`) xác nhận lần cuối không còn component nào import `AppLayout` hoặc 2 page trên.
   - *Bước 2:* Xóa 3 file nói trên khỏi kho mã nguồn.
   - *Bước 3:* Chạy `npm run build` để kiểm tra bundle không phát sinh lỗi missing chunk.
10. **Acceptance Criteria (AC):**
    - [ ] 3 file mồ côi được loại bỏ sạch sẽ.
    - [ ] Không có bất kỳ route hoặc component nào bị gãy sau khi xóa.
11. **Test Cases:**
    - `TC-BLD-01`: `npm run build` thành công, kích thước bundle giảm nhẹ.
12. **Priority:** **`P3` (Low - Housekeeping)**
13. **Dependency Task:** Không có.
14. **Estimated Effort:** **1.0 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Lỡ xóa nhầm file có route gọi ngầm.
    - *Giải pháp:* Đã kiểm tra đối chiếu kỹ lưỡng trong bước audit, toàn bộ 128 routes không có route nào gọi tới 3 file này.

---

### TASK TSK-402: Tái Thiết Kế Trang Chủ Chào Đón (`Welcome.jsx`)

1. **Task ID:** `TSK-402`
2. **Module:** M10 / Public (Landing Page)
3. **Mục tiêu:** Chuyển đổi trang `resources/js/Pages/Welcome.jsx` từ template mặc định của Laravel Breeze thành Trang Chủ Chào Đón mang đậm bản sắc học thuật của Trường Đại học An Giang.
4. **Hiện trạng:**
   - Tuyến `GET /` (`home`) render view `Welcome.jsx`.
   - Nội dung hiện tại là logo Laravel, link tài liệu Laravel, Laracasts, Forge... chưa thể hiện được chức năng của một Thư viện học thuật số.
5. **File cần đọc:**
   - `resources/js/Pages/Welcome.jsx`
   - `resources/js/Layouts/GuestLayout.jsx`
   - `.md/DESIGN_UI.md` (Hướng dẫn thiết kế nhận diện thương hiệu AGU)
6. **File cần sửa hoặc tạo:**
   - Sửa: `resources/js/Pages/Welcome.jsx`
7. **Backend dependency:** `routes/web.php` (tuyến `home`).
8. **Data Contract:**
   - Props nhận từ Backend: `{ auth: { user: Object|null } }`.
9. **Các bước triển khai:**
   - *Bước 1 (Chờ PO chốt thiết kế):* Họp nhanh với Product Owner chốt layout Landing Page.
   - *Bước 2:* Dựng Hero Banner nhận diện trường:
     - Tông màu xanh `#005baa` và vàng `#ffc72c` của Trường Đại học An Giang.
     - Khẩu hiệu: "AGU Academic Hub — Nền tảng Chia sẻ Tài liệu & Tri thức Sinh viên An Giang".
     - Nút kêu gọi hành động (CTA): "Đăng nhập Cổng Sinh viên" và "Khám phá Kho Môn học".
   - *Bước 3:* Khối Tính Năng Nổi Bật:
     - Tra cứu đề cương, giáo trình và đề thi các khóa trước.
     - Săn thưởng Bounty đóng góp tài liệu hiếm.
     - Đánh giá môn học khách quan và văn minh.
   - *Bước 4:* Khối Thống Kê Nhanh: Số Khoa đào tạo, Số môn học, Số tài liệu đã kiểm duyệt.
   - *Bước 5:* Footer: Thông tin liên hệ Trường ĐH An Giang (18 Ung Văn Khiêm, TP. Long Xuyên, An Giang).
10. **Acceptance Criteria (AC):**
    - [ ] Khách vãng lai truy cập domain gốc `/` thấy giao diện giới thiệu chuyên nghiệp của AGU.
    - [ ] Nếu đã đăng nhập, nút CTA chuyển thành "Vào Bảng Điều Khiển (Dashboard)".
11. **Test Cases:**
    - `TC-RES-04`: Mở trang chủ trên Desktop và Mobile $\to$ hiển thị đẹp mắt, không vỡ layout.
12. **Priority:** **`P3` (Low - Polish)**
13. **Dependency Task:** **`NEEDS_DECISION`** (Cần PO phê duyệt concept UI).
14. **Estimated Effort:** **5.0 Giờ**
15. **Risk & Mitigation:** Không có rủi ro kỹ thuật.

---

### TASK TSK-403: Kiểm Thử Nghiệm Thu Tích Hợp E2E Toàn Phân Hệ (QA Sign-off)

1. **Task ID:** `TSK-403`
2. **Module:** QA / Integration (All Modules M01 - M10)
3. **Mục tiêu:** Chạy kiểm thử toàn bộ 28 kịch bản trong `STUDENT_ALUMNI_TEST_CHECKLIST.md` trên môi trường thực tế, đảm bảo 0 lỗi nghiêm trọng trước khi bàn giao release.
4. **Hiện trạng:**
   - Các chức năng vừa được sửa và hoàn thiện qua các Phase 0, 1, 2, 3 cần một đợt nghiệm thu chéo liên thông toàn bộ các phân hệ.
5. **File cần đọc:**
   - `docs/planning/STUDENT_ALUMNI_TEST_CHECKLIST.md`
   - `docs/planning/STUDENT_ALUMNI_TASK_TRACKER.md`
6. **File cần sửa hoặc tạo:**
   - Cập nhật kết quả: `docs/planning/STUDENT_ALUMNI_TEST_CHECKLIST.md`
7. **Backend dependency:** Toàn bộ test suite PHP và môi trường staging.
8. **Data Contract:** Toàn bộ contracts đã chuẩn hóa.
9. **Các bước triển khai:**
   - *Bước 1:* Chạy toàn bộ test suite backend: `php artisan test` xác nhận 160/160 tests passed.
   - *Bước 2:* Chạy build frontend: `npm run build` xác nhận không có lỗi asset chunk.
   - *Bước 3:* Đăng nhập Persona 1: **Sinh viên hiện hành (`SINH_VIEN`)**:
     - Duyệt Dashboard $\to$ Tìm môn $\to$ Xem chi tiết tài liệu $\to$ Xem trước PDF $\to$ Tải file $\to$ Upvote $\to$ Tạo bài Wishlist cọc 30 điểm ví $\to$ Kiểm tra trừ điểm tại `/vi-diem`.
   - *Bước 4:* Đăng nhập Persona 2: **Cựu sinh viên (`CUU_SINH_VIEN`)**:
     - Vào thị trường Bounty $\to$ Nộp tài liệu giải quyết yêu cầu $\to$ Đăng xuất.
   - *Bước 5:* Đăng nhập lại Persona 1:
     - Vào chi tiết bài đăng Bounty $\to$ Bấm xác nhận nghiệm thu $\to$ Confetti bắn lên $\to$ Điểm chuyển thành công.
   - *Bước 6:* Đăng nhập Persona 3: **Sinh viên chưa đổi mật khẩu**:
     - Kiểm tra rào chắn `EnsurePasswordChanged` chặn chuẩn xác.
   - *Bước 7:* Đăng nhập Persona 4: **Cựu sinh viên bị từ chối KYC**:
     - Kiểm tra form đăng nhập hiển thị lý do từ chối.
   - *Bước 8:* Ký biên bản nghiệm thu trong `STUDENT_ALUMNI_TEST_CHECKLIST.md`.
10. **Acceptance Criteria (AC):**
    - [ ] 100% test cases trong checklist đạt trạng thái `PASSED`.
    - [ ] Không có bất kỳ lỗi JavaScript Uncaught Exception trên trình duyệt.
    - [ ] Đạt chuẩn hiển thị trên cả Desktop và Mobile.
11. **Test Cases:** Toàn bộ 28 test cases từ `TC-BLD-01` đến `TC-RES-04`.
12. **Priority:** **`P1` (High - Release Gate)**
13. **Dependency Task:** Hoàn thành toàn bộ các tasks từ Phase 0 đến Phase 3.
14. **Estimated Effort:** **6.0 Giờ**
15. **Risk & Mitigation:**
    - *Rủi ro:* Phát sinh regression bug ở phút chót.
    - *Giải pháp:* Ghi nhận ngay vào Tracker, cô lập bug và xử lý ngay trong ngày kiểm thử.

