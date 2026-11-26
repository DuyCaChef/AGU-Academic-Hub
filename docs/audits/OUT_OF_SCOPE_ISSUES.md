# DANH SÁCH CÁC VẤN ĐỀ NGOÀI PHẠM VI (OUT-OF-SCOPE ISSUES)
## AGU Academic Hub — Phân Hệ Sinh Viên & Cựu Sinh Viên
**Thời điểm lập:** 2026-11-06  
**Cập nhật lần cuối:** 2026-11-06 (Sau khi hoàn thành Phase 2 - Dashboard)  
**Quy tắc:** Chỉ ghi nhận hiện tượng và nguyên nhân, tuyệt đối không tự ý sửa đổi mã nguồn ngoài phạm vi phase đang thực hiện.

---

| Mã Vấn Đề | Phân Hệ / Module | Vị Trí File & Dòng | Mô Tả Hiện Tượng | Mức Độ | Trạng Thái Xử Lý |
| :---: | :--- | :--- | :--- | :---: | :--- |
| **OOS-01** | M01 — Dashboard | `resources/js/Pages/Dashboard.jsx:419-430` | Khối thông báo "Chưa có tài liệu học tập" render vô điều kiện đè dưới danh sách tài liệu mới. | Cao | **ĐÃ XỬ LÝ XONG (Phase 2)** |
| **OOS-02** | M01 — Dashboard | `resources/js/Pages/Dashboard.jsx:374-417` | Các thẻ `recentDocuments` là `<article>` tĩnh, thiếu `<Link>` tới `documents.show`. | Trung bình | **ĐÃ XỬ LÝ XONG (Phase 2)** |
| **OOS-03** | M01 — Dashboard | `resources/js/Pages/Dashboard.jsx:205` | Nhãn hồ sơ hiển thị cố định "Hồ sơ sinh viên" cho cả user `CUU_SINH_VIEN`. | Thấp | **ĐÃ XỬ LÝ XONG (Phase 2)** |
| **OOS-04** | M02 — Academic Catalog | `app/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController.php:45-50` | `courses.show` (`GET /mon-hoc/{code}`) render `Documents/Index` thay vì trang chi tiết môn học chuyên biệt. | Cao | **ĐÃ XỬ LÝ XONG (Phase 3A, 3B1, 3B2)** |
| **OOS-05** | M02 — Academic Catalog | `resources/js/Pages/Faculties/Index.jsx:169` | Nút "Xem môn học" trên thẻ Khoa không truyền tham số `ma_khoa`. | Trung bình | Dự kiến Phase P1 (Task TSK-REV-004) |
| **OOS-06** | M03 — Documents Upload | `resources/js/Pages/Documents/Create.jsx:116` | Chưa kiểm tra `file.size > 50MB` trước khi tính hash SHA-256 gây nguy cơ đơ trình duyệt. | Cao | Dự kiến Phase P1 (Task TSK-REV-005) |
| **OOS-07** | M03 — Documents Upload | `app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php:76` | Thiếu validation rule `mimes` đối với file tải lên phía server. | Cao | Dự kiến Phase P1 (Task TSK-REV-005) |
| **OOS-08** | M03 — Documents Backend | `app/Modules/Documents/Infrastructure/Persistence/DocumentRepository.php:18` | Tìm kiếm tài liệu chỉ tìm theo `tieu_de`, chưa tìm theo `mo_ta` hoặc mã môn. | Thấp | Dự kiến Phase P2 (Task TSK-REV-007) |
