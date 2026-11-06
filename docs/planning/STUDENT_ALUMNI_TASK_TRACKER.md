# BẢNG THEO DÕI TIẾN ĐỘ TRIỂN KHAI PHÂN HỆ SINH VIÊN VÀ CỰU SINH VIÊN
## AGU Academic Hub — Task & Implementation Tracker

---

| Thông Tin Tổng Quan | Số Liệu |
| :--- | :--- |
| **Dự Án** | AGU Academic Hub — Student & Alumni Modules |
| **Tổng Số Task** | **20 Tasks** (P0: 1 \| P1: 9 \| P2: 7 \| P3: 3) |
| **Trạng Thái Hiện Tại** | **8 DONE** \| **9 READY** \| **2 BLOCKED** \| **1 NEEDS_DECISION** |
| **Tổng Ước Lượng Thời Gian** | **57.5 Giờ làm việc** (~7.2 Man-days) |
| **Đường Dẫn Master Plan** | [`docs/planning/STUDENT_ALUMNI_IMPLEMENTATION_PLAN.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/planning/STUDENT_ALUMNI_IMPLEMENTATION_PLAN.md) |
| **Đường Dẫn Test Checklist** | [`docs/planning/STUDENT_ALUMNI_TEST_CHECKLIST.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/planning/STUDENT_ALUMNI_TEST_CHECKLIST.md) |

---

## 1. THỐNG KÊ TIẾN ĐỘ THEO GIAI ĐOẠN (PHASE PROGRESS SUMMARY)

```
[ Phase 0: Stabilization        ] 3 Tasks  | 03.5h | [████████████████████] COMPLETED (100% DONE)
[ Phase 1: Document Experience  ] 5 Tasks  | 14.0h | [████████████████████] COMPLETED (100% DONE)
[ Phase 2: Wallet & Bounty      ] 5 Tasks  | 13.5h | [██████████████░░░░░░] PARTIAL (80% Ready, 1 Blocked)
[ Phase 3: Shared UI & Polish   ] 4 Tasks  | 12.0h | [████████████████████] READY (100%)
[ Phase 4: Polish & E2E Testing ] 3 Tasks  | 10.0h | [████████████░░░░░░░░] PARTIAL (1 Ready, 1 Decision, 1 Testing)
```

---

## 2. BẢNG THEO DÕI CHI TIẾT CÁC TASK (DETAILED TASK TRACKER)

| Task ID | Phase | Module | Tên Đầu Việc Triển Khai | Ưu Tiên | Trạng Thái | Phụ Thuộc | Ước Lượng | Thực Tế | Ngày Bắt Đầu | Ngày Xong | Người Phụ Trách | Ghi Chú & Điểm Nghẽn |
| :---: | :---: | :---: | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :--- |
| **TSK-001** | Phase 0 | M06 / Infra | Khắc phục lỗi Build Vite (`canvas-confetti`) | **P0** | `DONE` | *None* | 1.0h | 0.5h | 2026-11-06 | 2026-11-06 | Frontend Dev | Cài đặt package canvas-confetti, build Vite pass 100% |
| **TSK-002** | Phase 0 | M07 | Khởi tạo Shell giao diện `Wallet/Index.jsx` | **P1** | `DONE` | *None* | 2.5h | 2.0h | 2026-11-06 | 2026-11-06 | Fullstack Dev | Tạo xong Wallet/Index.jsx, cứu route GET /vi-diem |
| **TSK-003** | Phase 0 | M08 / Core | Đồng bộ Data Contract tên người dùng (`ho_ten`) | **P1** | `DONE` | *None* | 1.5h | 1.0h | 2026-11-06 | 2026-11-06 | Fullstack Dev | Thêm alias name trong HandleInertiaRequests và form Profile |
| **TSK-101** | Phase 1 | M03 | Xây dựng trang Chi Tiết Tài Liệu (`Documents/Show.jsx`) | **P1** | `DONE` | TSK-001 | 4.0h | 3.5h | 2026-11-06 | 2026-11-06 | Frontend Dev | Tách biệt hoàn toàn khỏi `Documents/Index.jsx`, render đầy đủ metadata |
| **TSK-102** | Phase 1 | M03 | Xây dựng trang Đăng Tải Tài Liệu (`Documents/Create.jsx`) | **P1** | `DONE` | *None* | 4.5h | 4.0h | 2026-11-06 | 2026-11-06 | Frontend Dev | Form upload file, SHA-256 pre-check Web Crypto, lọc khoa/môn |
| **TSK-103** | Phase 1 | M03 | Tích hợp PDF In-Browser Preview & Safe Download Stream | **P1** | `DONE` | TSK-101 | 3.0h | 2.5h | 2026-11-06 | 2026-11-06 | Fullstack Dev | Nhúng PDF iframe responsive, full tab fallback và nút tải tự tăng count |
| **TSK-104** | Phase 1 | M03 | Tích hợp Phân Trang & Upvote AJAX trên DocumentCard | **P1** | `DONE` | TSK-101 | 2.5h | 2.0h | 2026-11-06 | 2026-11-06 | Frontend Dev | Fix bug luot_binh_chon, gắn `Pagination.jsx` và AJAX toggle vote |
| **TSK-105** | Phase 1 | M03 / M10 | Xây dựng Modal Báo Cáo Vi Phạm Tài Liệu | **P2** | `DONE` | TSK-101 | 3.0h | 2.0h | 2026-11-06 | 2026-11-06 | Frontend Dev | Tạo DocumentReportModal kết nối `POST /bao-cao/tai-lieu`, ẩn với chính tác giả |
| **TSK-201** | Phase 2 | M07 / BE | Xây dựng Query Lịch Sử Giao Dịch Điểm Ví | **P1** | `READY` | TSK-002 | 3.0h | - | - | - | Backend Dev | Truy vấn phân trang từ bảng `giao_dich_diem` |
| **TSK-202** | Phase 2 | M07 | Hoàn thiện Giao diện Ví Điểm Cá Nhân & Bộ lọc Giao dịch | **P1** | `READY` | TSK-201 | 4.0h | - | - | - | Frontend Dev | Hiển thị số dư khả dụng, điểm tạm giữ và bảng biến động |
| **TSK-203** | Phase 2 | M01 | Chuẩn hóa Link Shortcut Ví Điểm trên Dashboard | **P2** | `READY` | TSK-202 | 1.0h | - | - | - | Frontend Dev | Đổi link trên `Dashboard.jsx:218` sang `route('wallet.index')` |
| **TSK-204** | Phase 2 | M05 | Tái sử dụng Component Pagination trong `Wishlist/Index` | **P2** | `READY` | *None* | 1.5h | - | - | - | Frontend Dev | Xóa code JSX tự viết lặp lại, thay bằng `Pagination.jsx` |
| **TSK-205** | Phase 2 | M06 | Xây dựng Document Picker trong `FulfillRequestModal` | **P2** | `BLOCKED` | Cần API My Docs | 4.0h | - | - | - | Fullstack Dev | **BỊ CHẶN:** Cần Query backend lấy danh sách tài liệu cá nhân |
| **TSK-301** | Phase 3 | M02 | Tách riêng Giao diện Danh Mục Khoa (`Faculties/Index.jsx`) | **P2** | `READY` | *None* | 3.5h | - | - | - | Frontend Dev | Ngừng trỏ route `/khoa` vào `Dashboard` |
| **TSK-302** | Phase 3 | M04 | Đưa trang Đánh Giá Môn Học (`CourseReviews`) vào `StudentLayout` | **P2** | `READY` | *None* | 2.5h | - | - | - | Frontend Dev | Bỏ header riêng, tích hợp Sidebar và cây thư mục môn |
| **TSK-303** | Phase 3 | M08 | Chuyển đổi Trang Hồ Sơ (`Profile/Edit.jsx`) sang `StudentLayout` | **P2** | `READY` | TSK-003 | 3.0h | - | - | - | Frontend Dev | Bỏ `AuthenticatedLayout`, bổ sung trường MSSV, Khoa readonly |
| **TSK-304** | Phase 3 | M02 / M10 | Chuẩn hóa Prop `faculties` & Hiển thị cấp Chuyên Ngành | **P2** | `READY` | *None* | 3.0h | - | - | - | Fullstack Dev | Sửa lỗi chính tả `faculities` $\to$ `faculties` trên cây thư mục |
| **TSK-401** | Phase 4 | M10 / Clean | Dọn dẹp an toàn các file mồ côi (`Courses/Index`, `Home`, `AppLayout`) | **P3** | `READY` | *None* | 1.0h | - | - | - | Lead Dev | Xóa file MVC cũ không còn sử dụng |
| **TSK-402** | Phase 4 | M10 / Public | Tái thiết kế Trang Chủ Chào Đón (`Welcome.jsx`) | **P3** | `NEEDS_DECISION` | Chốt UI với PO | 5.0h | - | - | - | UI/UX & FE | **CHỜ CHỐT:** Cần quyết định giao diện Landing Page với PO |
| **TSK-403** | Phase 4 | QA / All | Kiểm thử Nghiệm thu Tích hợp E2E (Student & Alumni) | **P1** | `READY` | Toàn bộ tasks | 6.0h | - | - | - | QA / Team | Chạy toàn bộ test checklist nghiệm thu release |

---

## 3. PHÂN BỔ SPRINT ĐỀ XUẤT (SPRINT ALLOCATION)

```
Sprint 1 (Ngày 1 - 2): Stabilization & Document Core (24.0h)
├── TSK-001: Fix Build Dependency canvas-confetti (1.0h) [P0]
├── TSK-002: Khởi tạo Shell Wallet/Index.jsx (2.5h) [P1]
├── TSK-003: Đồng bộ Data Contract tên người dùng (1.5h) [P1]
├── TSK-101: Xây dựng Documents/Show.jsx (4.0h) [P1]
├── TSK-102: Xây dựng Documents/Create.jsx (4.5h) [P1]
├── TSK-103: PDF Preview & Download Stream (3.0h) [P1]
├── TSK-104: Pagination & Upvote AJAX DocumentCard (2.5h) [P1]
└── TSK-105: Modal Báo Cáo Vi Phạm Tài Liệu (3.0h) [P2]

Sprint 2 (Ngày 3 - 4): Wallet, Bounty & Catalog Split (18.5h)
├── TSK-201: Query Lịch Sử Giao Dịch Điểm Ví (3.0h) [P1]
├── TSK-202: Giao diện chi tiết Ví điểm & Filter (4.0h) [P1]
├── TSK-203: Shortcut Ví điểm Dashboard (1.0h) [P2]
├── TSK-204: Pagination chuẩn Wishlist/Index (1.5h) [P2]
├── TSK-301: Tách riêng Faculties/Index.jsx (3.5h) [P2]
├── TSK-302: CourseReviews sang StudentLayout (2.5h) [P2]
└── TSK-304: Sửa typo faculities & Chuyên ngành (3.0h) [P2]

Sprint 3 (Ngày 5 - 6): Profile, Polish & Testing (15.0h)
├── TSK-303: Chuyển đổi Profile/Edit.jsx sang StudentLayout (3.0h) [P2]
├── TSK-401: Dọn dẹp file mồ côi (1.0h) [P3]
├── TSK-402: Tái thiết kế Welcome.jsx (sau khi PO chốt) (5.0h) [P3]
└── TSK-403: E2E Integration Testing & Nghiệm thu (6.0h) [P1]
```

---

## 4. HƯỚNG DẪN CẬP NHẬT TRẠNG THÁI TASK
Khi lập trình viên nhận task và triển khai, cần tuân thủ quy ước cập nhật trạng thái trong bảng trên:
- **`READY` $\to$ `IN_PROGRESS`:** Khi developer bắt đầu code nhánh tính năng (`feature/task-id`).
- **`IN_PROGRESS` $\to$ `IN_REVIEW`:** Khi tạo Pull Request kèm đối chiếu Checklist nghiệm thu.
- **`IN_REVIEW` $\to$ `DONE`:** Khi code đã được review, merge vào nhánh chính và pass toàn bộ bài test.
- **`BLOCKED`:** Nếu phát sinh lỗi hạ tầng hoặc thiếu API ngoài dự kiến, ghi rõ lý do vào cột *Ghi Chú & Điểm Nghẽn*.

