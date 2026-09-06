# PHẦN 2: TÀI LIỆU THIẾT KẾ (DESIGN SPECIFICATION)

### 1\. Thiết Kế Kiến Trúc Hệ Thống (System Architecture)

Hệ thống được thiết kế theo mô hình **Monolithic Modular** chuẩn hoá trên nền tảng **Laravel 11**, kết hợp xử lý tác vụ nền bất đồng bộ (Asynchronous Background Jobs) nhằm tách biệt luồng xử lý I/O nặng (trích xuất text, bóc tách mã băm, gửi mail KYC).

``` mermaid
graph TD
    A[Client Tier: Browser/Mobile] -->|HTTPS / RESTful API| B[Reverse Proxy & Web Server: Nginx]
    B -->|PHP-FPM| C[Application Tier: Laravel 11]
    
    C -->|SQL / Eloquent| D[Database Tier: MySQL 8.0]
    C -->|Read / Write File| E[Storage Tier: Public & Private]
    
    subgraph "Tầng Ứng dụng (Application Tier)"
    C1[Middleware: Auth, RoleGate]
    C2[Controllers: Auth, Doc, CourseReview...]
    C3[Domain Services: Deduplication, KYC, Bounty]
    C4[Queue Workers: Jobs]
    end 
```

-----

### 2\. Thiết Kế Cơ Sở Dữ Liệu (Database Design & ERD)

#### A. Sơ đồ Quan hệ Thực thể (Entity Relationship Diagram - ERD)

``` mermaid
erDiagram
    FACULTIES ||--o{ COURSES : "Quản lý"
    FACULTIES ||--o{ USERS : "Thuộc khoa"
    USERS ||--o{ DOCUMENTS : "Đăng tài liệu"
    USERS ||--o{ DOCUMENT_REPORTS : "Báo cáo"
    USERS ||--o{ DOCUMENT_REQUESTS : "Yêu cầu"
    USERS ||--o{ VERIFICATIONS : "Xác thực"
    COURSES ||--o{ DOCUMENTS : "Có tài liệu"
    COURSES ||--o{ COURSE_REVIEWS : "Có review"
    DOCUMENTS ||--o{ DOCUMENT_REPORTS : "Bị báo cáo" 
```

#### Chi tiết bảng dữ liệu:

**1. Bảng khoa (Danh mục Khoa / Bộ môn)**

  * **ma\_khoa**: Số nguyên lớn không dấu, Khóa chính, Tự tăng.
  * **ky\_hieu\_khoa**: Chuỗi ký tự tối đa 20, Duy nhất (Ví dụ: CNTT, KT, SP).
  * **ten\_khoa**: Chuỗi ký tự tối đa 255 (Ví dụ: Khoa Công nghệ Thông tin).

**2. Bảng nguoi\_dung (Tài khoản Người dùng)**

  * **ma\_nguoi\_dung**: Số nguyên lớn không dấu, Khóa chính, Tự tăng.
  * **ma\_khoa**: Số nguyên lớn không dấu, Khóa ngoại liên kết bảng khoa, Có thể rỗng.
  * **ho\_ten**: Chuỗi ký tự tối đa 255.
  * **email**: Chuỗi ký tự tối đa 255, Duy nhất.
  * **ma\_so\_sinh\_vien**: Chuỗi ký tự tối đa 50, Duy nhất, Có thể rỗng.
  * **mat\_khau**: Chuỗi ký tự tối đa 255 (Bcrypt).
  * **vai\_tro**: Lựa chọn (KHACH, SINH\_VIEN, CUU\_SINH\_VIEN, KIEM\_DUYET\_VIEN, QUAN\_TRI\_VIEN).
  * **trang\_thai**: Lựa chọn (CHO\_XAC\_THUC, HOAT\_DONG, TAM\_KHOA).
  * **diem\_uy\_tin**: Số nguyên, Mặc định 100.
  * **ngay\_tao, ngay\_cap\_nhat, ngay\_xoa\_tam**: Dấu mốc thời gian.

**3. Bảng mon\_hoc (Danh mục Môn học / Học phần)**

  * **ma\_mon\_hoc**: Số nguyên lớn không dấu, Khóa chính, Tự tăng.
  * **ma\_khoa**: Số nguyên lớn không dấu, Khóa ngoại liên kết bảng khoa.
  * **ma\_hoc\_phan**: Chuỗi ký tự tối đa 50, Duy nhất (Ví dụ: COS101, KTE201).
  * **ten\_mon\_hoc**: Chuỗi ký tự tối đa 255.
  * **so\_tin\_chi**: Số nguyên nhỏ không dấu, Mặc định 3.
  * **mo\_ta**: Văn bản dài, Có thể rỗng.

**4. Bảng tai\_lieu (Kho Tài liệu Học tập)**

  * **ma\_tai\_lieu**: Số nguyên lớn không dấu, Khóa chính, Tự tăng.
  * **ma\_tai\_lieu\_goc**: Quản lý phiên bản (v1.1, v2.0).
  * **ma\_mon\_hoc**: Khóa ngoại liên kết bảng mon\_hoc.
  * **ma\_nguoi\_dang**: Khóa ngoại liên kết bảng nguoi\_dung.
  * **tieu\_de**: Chuỗi ký tự tối đa 255.
  * **duong\_dan\_tep**: Đường dẫn lưu file trên máy chủ.
  * **dinh\_dang\_tep**: Định dạng file (pdf, docx, zip...).
  * **dung\_luong\_kb**: Số nguyên không dấu.
  * **ma\_bam\_file**: Mã SHA-256 chống tải file trùng 100%.
  * **chu\_ky\_noi\_dung**: Chữ ký text rút gọn.
  * **phan\_loai**: Lựa chọn (DE\_THI, BAI\_GIANG, DO\_AN, BAI\_TAP, KHAC).
  * **phien\_ban**: Mặc định '1.0'.
  * **luot\_tai**: Mặc định 0.
  * **luot\_thich**: Mặc định 0.
  * **trang\_thai**: Lựa chọn (CHO\_DUYET, DA\_DUYET, TAM\_AN\_KIEM\_TRA, TU\_CHOI).
  * **ngay\_tao, ngay\_cap\_nhat, ngay\_xoa\_tam**: Dấu mốc thời gian.

**5. Bảng danh\_gia\_mon\_hoc (Đánh giá Môn học)**

  * **ma\_danh\_gia**: Số nguyên lớn không dấu, Khóa chính, Tự tăng.
  * **ma\_mon\_hoc**: Khóa ngoại liên kết bảng mon\_hoc.
  * **ma\_nguoi\_danh\_gia**: Khóa ngoại liên kết bảng nguoi\_dung.
  * **diem\_do\_kho**: Số nguyên (1-5).
  * **diem\_thuc\_tien**: Số nguyên (1-5).
  * **diem\_ap\_luc\_bai\_tap**: Số nguyên (1-5).
  * **nhan\_xet**: Văn bản dài.
  * **la\_an\_danh**: Boolean, Mặc định Đúng.
  * **ngay\_tao, ngay\_xoa\_tam**: Dấu mốc thời gian.

**6. Bảng yeu\_cau\_tai\_lieu (Yêu cầu Tìm Tài liệu / Wishlist)**

  * **ma\_yeu\_cau**: Số nguyên lớn không dấu, Khóa chính, Tự tăng.
  * **ma\_mon\_hoc**: Khóa ngoại liên kết bảng mon\_hoc.
  * **ma\_nguoi\_yeu\_cau**: Khóa ngoại liên kết bảng nguoi\_dung.
  * **tieu\_de**: Chuỗi ký tự tối đa 255.
  * **chi\_tiet\_yeu\_cau**: Văn bản dài.
  * **diem\_thuong**: Số nguyên, Mặc định 50.
  * **trang\_thai**: Lựa chọn (DANG\_MO, DA\_GIAI\_QUYET, DA\_DONG).
  * **ma\_tai\_lieu\_giai\_quyet**: Khóa ngoại liên kết bảng tai\_lieu.

**7. Bảng bao\_cao\_vi\_pham (Báo cáo Vi phạm)**

  * **ma\_bao\_cao**: Số nguyên lớn không dấu, Khóa chính, Tự tăng.
  * **ma\_tai\_lieu**: Khóa ngoại liên kết bảng tai\_lieu.
  * **ma\_nguoi\_bao\_cao**: Khóa ngoại liên kết bảng nguoi\_dung.
  * **ly\_do**: Lựa chọn (TRUNG\_LAP, SAI\_NOI\_DUNG, MA\_DOC, BAN\_QUYEN, KHAC).
  * **chi\_tiet\_loi**: Văn bản dài.
  * **trang\_thai\_xu\_ly**: Lựa chọn (CHO\_XU\_LY, DA\_XU\_LY, BAC\_BO).

-----

### 3\. Thiết Kế Giao Diện (UI/UX Design)

#### A. Hệ thống Nhận diện Trực quan (Design System / Style Guide)

  * **Primary Color:** \#005baa (Deep Blue)
  * **Secondary Color:** \#00a896 (Teal Green)
  * **Accent/Warning Color:** \#f48c06 (Amber) & \#e63946 (Đỏ cảnh báo)
  * **Neutral Background:** \#f8fafc (Light Mode), \#0f172a (Dark Mode)
  * **Typography:** Inter / Be Vietnam Pro

#### B. Bản vẽ Giao diện (Wireframe Layout)

  * **Màn hình Kho Môn Học & Tài Liệu (Desktop Hub View):** Hiển thị danh mục khoa bên trái, danh sách tài liệu chi tiết của môn học ở giữa, kèm thông tin giảng viên và các tab chức năng (Kho tài liệu, Review, Wishlist).
  * **Màn hình Đánh giá Môn học (Course Review Form):** Cửa sổ Modal hiển thị thang điểm cho các tiêu chí: Độ khó, Tính thực tiễn, Khối lượng bài tập. Hỗ trợ tùy chọn ẩn danh 2 lớp cho người dùng.
