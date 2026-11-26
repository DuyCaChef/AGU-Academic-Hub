# SƠ ĐỒ NGỮ CẢNH HỆ THỐNG AGU ACADEMIC HUB (SYSTEM CONTEXT DIAGRAM)
**Vị trí:** `docs/knowledge/diagrams/system-context.md`  
**Mục đích:** Mô tả ranh giới tương tác giữa các tác nhân người dùng, hệ thống bên ngoài và nền tảng AGU Academic Hub.

```mermaid
flowchart TD
    subgraph ExternalActors ["CÁC TÁC NHÂN NGƯỜI DÙNG"]
        Student["Sinh viên Đang học (SINH_VIEN)<br>• Tra cứu, tải đề thi/slide<br>• Đăng đánh giá môn học<br>• Tạo và săn thưởng Bounty"]
        Alumni["Cựu sinh viên (CUU_SINH_VIEN)<br>• Nộp hồ sơ KYC bằng cấp<br>• Chia sẻ đồ án, đề cương<br>• Hỗ trợ giải quyết Bounty"]
        Moderator["Kiểm duyệt viên (KIEM_DUYET_VIEN)<br>• Thẩm định tài liệu mới<br>• Duyệt KYC cựu sinh viên<br>• Duyệt giải ngân Bounty"]
        Admin["Quản trị viên (QUAN_TRI_VIEN)<br>• Cấu hình danh mục Khoa/Môn<br>• Quản lý người dùng, phân quyền<br>• Giám sát lưu trữ & Thùng rác 60 ngày"]
        Guest["Khách vãng lai<br>• Xem trang chủ, danh mục công khai"]
    end

    subgraph CoreSystem ["NỀN TẢNG AGU ACADEMIC HUB"]
        Hub["AGU Academic Hub Core Platform<br>(Laravel 11 + Inertia React 18 Modular Monolith)"]
    end

    subgraph ExternalServices ["HẠ TẦNG & NGUỒN DỮ LIỆU NGOÀI"]
        RawData[("Danh sách Sinh viên Thô<br>(Phòng Đào tạo AGU nạp trước)")]
        StorageDisk[("Phân vùng Lưu trữ Tệp<br>(Private Docs & Secure KYC Disk)")]
        MySQLDB[("Cơ sở Dữ liệu Quan hệ<br>(MySQL 8.0 InnoDB)")]
    end

    Student <-->|HTTPS / Session / Web UI| Hub
    Alumni <-->|HTTPS / KYC Submission / Web UI| Hub
    Moderator <-->|HTTPS / Moderation Queue / Web UI| Hub
    Admin <-->|HTTPS / Admin Portal / Web UI| Hub
    Guest -->|HTTPS / Read-only Catalog| Hub

    Hub <-->|Import & Đối soát đăng nhập lần đầu| RawData
    Hub <-->|Lưu trữ an toàn & Temporary Signed URL| StorageDisk
    Hub <-->|Giao dịch ACID & Khóa hàng lockForUpdate| MySQLDB
```

