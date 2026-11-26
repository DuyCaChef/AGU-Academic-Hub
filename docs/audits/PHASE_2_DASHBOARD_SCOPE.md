# TÀI LIỆU KHÓA PHẠM VI TRIỂN KHAI — PHASE 2: DASHBOARD FUNCTIONALITY
## AGU Academic Hub — Bảng Điều Khiển Sinh Viên & Cựu Sinh Viên (`GET /trang-chu`)
**Thời điểm lập:** 2026-11-06  
**Trạng thái:** SCOPE LOCKED  
**Phạm vi:** Triển khai độc lập Phase 2 (Hoàn thiện Dashboard). Tuyệt đối không can thiệp vào các Phase ngoài phạm vi.

---

## 1. MỤC TIÊU & PHẠM VI TRIỂN KHAI

### 1.1. Mục tiêu duy nhất
Hoàn thiện toàn bộ chức năng, điều hướng, và trải nghiệm người dùng trên Bảng điều khiển (Dashboard) tại tuyến đường `GET /trang-chu` (`dashboard`) dành cho Sinh viên (`SINH_VIEN`) và Cựu sinh viên (`CUU_SINH_VIEN`).

### 1.2. Ba Trọng Tâm Trong Phạm Vi (In-Scope)
1. **Phạm vi A: Khu vực Tài liệu học tập mới (`recentDocuments`):**
   - Sửa dứt điểm lỗi hiển thị: Khi có tài liệu thì hiển thị danh sách tài liệu, khi rỗng thì hiển thị Empty State. **Tuyệt đối không bao giờ hiển thị đồng thời cả hai**.
   - Bổ sung trường `'luot_tai'` trong câu truy vấn `DashboardController` để hiển thị chính xác số lượt tải thực tế.
   - Thẻ tài liệu: Click vào tiêu đề/thẻ mở đúng trang chi tiết tài liệu `GET /tai-lieu/{id}` (`documents.show`).
   - Nút Download: Chuyển từ thẻ `span` tĩnh thành nút tải thực tế `GET /tai-lieu/{id}/tai-ve` (`documents.download`).
   - Loại bỏ nhãn thử nghiệm `"Mẫu giao diện"` còn sót lại.
2. **Phạm vi B: Bốn Tác Vụ Nhanh (`quickLinks`):**
   - **Tìm môn học:** Kết nối trực tiếp tới `route('courses.index')` (`Courses/Index.jsx` đã hoàn thành ở Phase 1).
   - **Săn thưởng tài liệu:** Kết nối tới `route('document-requests.index')` (Chợ Bounty tại `Wishlist/Index.jsx`).
   - **Duyệt danh sách khoa:** Kết nối tới `route('faculties.index')` (Danh mục Khoa tại `Faculties/Index.jsx`).
   - **Đăng tài liệu:** Kết nối tới `route('documents.create')` (Form đăng tải tại `Documents/Create.jsx`).
   - Đảm bảo 100% các nút và thẻ đều có route đích hợp lệ, không dẫn đến trang lỗi hay trang sai mục đích.
3. **Phạm vi C: Danh tính Người Dùng (Student & Alumni):**
   - Phân biệt danh xưng trong khối Profile Highlight: `SINH_VIEN` hiển thị `"Hồ sơ sinh viên"`, `CUU_SINH_VIEN` hiển thị `"Hồ sơ Cựu sinh viên"`.
   - Xử lý trường hợp Cựu sinh viên không có MSSV: Hiển thị `"Cựu sinh viên AGU"`.
   - Đảm bảo cả hai vai trò đều truy cập Dashboard bình thường, không thay đổi quyền và không làm vỡ `StudentLayout`.

### 1.3. Thành phần Tuyệt Đối Ngoài Phạm Vi (Out-of-Scope)
- Tuyệt đối KHÔNG thực hiện:
  - `Courses/Show.jsx` và Tabs môn học (Phase 3).
  - Nghiệp vụ Đánh giá môn học (Reviews).
  - Nghiệp vụ Săn thưởng / Giải ngân Bounty (Wishlist/Bounty backend).
  - Thiết kế lại trang Khoa (`Faculties/Index.jsx`).
  - Pipeline Đăng tải tài liệu (Upload Pipeline an toàn - Phase P1).
  - Tối ưu hóa tìm kiếm tài liệu (`DocumentRepository`).
  - Thay đổi migration hoặc cấu trúc CSDL.
  - Refactor toàn bộ `StudentLayout`.
- Bất kỳ lỗi ngoài phạm vi được ghi nhận vào [`docs/audits/OUT_OF_SCOPE_ISSUES.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/audits/OUT_OF_SCOPE_ISSUES.md).

---

## 2. HIỆN TRẠNG KỸ THUẬT & KIỂM CHỨNG TRƯỚC TRIỂN KHAI

1. **Tuyến đường & Controller:**
   - Route: `Route::get('/trang-chu', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');` trong `routes/web.php:20`.
   - Controller: `App\Http\Controllers\DashboardController`.
2. **Khảo sát 4 Route Đích của Tác vụ nhanh:**
   - `courses.index` (`GET /mon-hoc`): **ĐÃ SẴN SÀNG & HOẠT ĐỘNG HOÀN HẢO** (hoàn thành tại Phase 1, render `Courses/Index`).
   - `document-requests.index` (`GET /yeu-cau-tai-lieu`): **ĐÃ SẴN SÀNG & HOẠT ĐỘNG** (Module Bounties, render `Wishlist/Index`).
   - `faculties.index` (`GET /khoa`): **ĐÃ SẴN SÀNG & HOẠT ĐỘNG** (Module AcademicCatalog, render `Faculties/Index`).
   - `documents.create` (`GET /tai-lieu/dang-tai`): **ĐÃ SẴN SÀNG & HOẠT ĐỘNG** (Module Documents, render `Documents/Create`).
   - *Kết luận:* Cả 4 route đích đều đã tồn tại và trả về view tương ứng.
3. **Lỗi hiển thị nghiêm trọng tại `Dashboard.jsx:419-430`:**
   - Khối `<div className="col-span-full ...">Chưa có tài liệu học tập</div>` nằm hoàn toàn ngoài bất kỳ mệnh đề điều kiện nào, khiến giao diện luôn hiển thị thông báo rỗng ngay bên dưới danh sách tài liệu mới.
4. **Lỗi thẻ tài liệu thiếu liên kết tại `Dashboard.jsx:374-417`:**
   - Thẻ tài liệu là `<article>` tĩnh, không click được. Nút tải file chỉ là `<span>` tĩnh, không gọi route tải file.
5. **Truy vấn thiếu trường tại `DashboardController.php:34-41`:**
   - Phương thức `get([...])` không nạp trường `luot_tai`, khiến giao diện luôn nhận giá trị rỗng/null cho biến đếm lượt tải.

---

## 3. DATA CONTRACT THỰC TẾ

```typescript
interface DashboardPageProps {
    auth: {
        user: {
            ma_nguoi_dung: number;
            ho_ten: string;
            name?: string;
            email: string;
            vai_tro: 'SINH_VIEN' | 'CUU_SINH_VIEN' | 'GIANG_VIEN' | 'QUAN_TRI_VIEN';
            mssv?: string | null;
            ma_sinh_vien?: string | null;
            ma_khoa?: number | null;
            diem_uy_tin: number;
            vi_diem?: {
                diem_kha_dung: number;
                diem_tam_giu: number;
            };
        };
    };
    stats: {
        totalCourses: number;
        myDocuments: number;
        trustScore: number;
    };
    recentDocuments: Array<{
        ma_tai_lieu: number;
        ma_mon_hoc: number;
        tieu_de: string;
        dinh_dang_tep: string;
        phan_loai: string;
        luot_tai: number;
        created_at: string;
        mon_hoc?: {
            ma_mon_hoc: number;
            ma_hoc_phan: string;
            ten_mon_hoc: string;
        };
    }>;
    faculties: Array<any>;
}
```

---

## 4. MA TRẬN TIÊU CHÍ NGHIỆM THU (AC-01 ĐẾN AC-13)

| Mã AC | Tiêu Chí Nghiệm Thu | Phương Pháp Kiểm Chứng | Trạng Thái Dự Kiến |
| :---: | :--- | :--- | :---: |
| **AC-01** | Có tài liệu mới thì hiển thị danh sách tài liệu. | Mock DB có 3 tài liệu, kiểm tra DOM hiển thị đủ 3 thẻ. | CHƯA KIỂM CHỨNG |
| **AC-02** | Không có tài liệu thì hiển thị Empty State. | Mock DB rỗng (`recentDocuments = []`), kiểm tra DOM xuất hiện khối báo rỗng. | CHƯA KIỂM CHỨNG |
| **AC-03** | Không bao giờ hiển thị đồng thời danh sách có dữ liệu và Empty State. | Kiểm tra điều kiện loại trừ tương hỗ (`ternary` / `if-else`). | CHƯA KIỂM CHỨNG |
| **AC-04** | Click tài liệu mở đúng trang chi tiết (`documents.show`). | Thẻ tài liệu chứa `<Link href={route('documents.show', doc.ma_tai_lieu)}>`. | CHƯA KIỂM CHỨNG |
| **AC-05** | Nút download hoạt động đúng với tài liệu được phép tải. | Nút tải là thẻ `<a href={route('documents.download', doc.ma_tai_lieu)}>`. | CHƯA KIỂM CHỨNG |
| **AC-06** | Thẻ "Tìm môn học" mở `Courses/Index`. | Thẻ dẫn đến `route('courses.index')` (đã xong Phase 1). | CHƯA KIỂM CHỨNG |
| **AC-07** | Thẻ "Săn thưởng" mở đúng trang Bounty hiện có. | Thẻ dẫn đến `route('document-requests.index')`. | CHƯA KIỂM CHỨNG |
| **AC-08** | Thẻ "Duyệt danh sách khoa" mở đúng trang Faculty hiện có. | Thẻ dẫn đến `route('faculties.index')`. | CHƯA KIỂM CHỨNG |
| **AC-09** | Thẻ "Đăng tài liệu" mở đúng trang Upload hiện có. | Thẻ dẫn đến `route('documents.create')`. | CHƯA KIỂM CHỨNG |
| **AC-10** | Student và Alumni truy cập đúng theo chính sách phân quyền và hiển thị danh xưng chuẩn. | Đăng nhập với role `SINH_VIEN` và `CUU_SINH_VIEN`, kiểm tra profile card. | CHƯA KIỂM CHỨNG |
| **AC-11** | Loading, Empty và Error State hoạt động đúng. | Rà soát giao diện và trạng thái xử lý lỗi rỗng. | CHƯA KIỂM CHỨNG |
| **AC-12** | Responsive cơ bản trên Mobile, Tablet và Desktop. | Layout co giãn 1 cột (Mobile), 2 cột (Tablet), 3/4 cột (Desktop). | CHƯA KIỂM CHỨNG |
| **AC-13** | Không làm hỏng chức năng hiện có (182+ tests pass, Vite build sạch). | Chạy full test suite và build Vite. | CHƯA KIỂM CHỨNG |

