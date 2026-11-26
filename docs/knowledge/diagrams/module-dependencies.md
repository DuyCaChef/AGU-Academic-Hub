# SƠ ĐỒ PHỤ THUỘC GIỮA CÁC MODULE (MODULE DEPENDENCY GRAPH)
**Vị trí:** `docs/knowledge/diagrams/module-dependencies.md`  
**Mục đích:** Thể hiện luồng phụ thuộc dữ liệu và sự kiện giữa 8 Bounded Contexts trong kiến trúc Modular Monolith.

```mermaid
graph TD
    Identity["Identity Module<br>(Định danh, Xác thực & KYC)"]
    Catalog["AcademicCatalog Module<br>(Khoa, Ngành & Môn học)"]
    Docs["Documents Module<br>(Kho Lưu trữ & Lọc trùng)"]
    Reviews["Reviews Module<br>(Đánh giá Môn & Lọc từ cấm)"]
    Bounties["Bounties Module<br>(Săn thưởng & Ví điểm)"]
    Moderation["Moderation Module<br>(Kiểm duyệt, Phạt & Clawback)"]
    Admin["Administration Module<br>(Audit Logs & Storage Stats)"]
    Notifications["Notifications Module<br>(Thông báo Nghiệp vụ Thời gian thực)"]

    %% Các quan hệ phụ thuộc
    Catalog -->|Cung cấp ma_mon_hoc| Docs
    Catalog -->|Cung cấp ma_mon_hoc| Reviews
    Catalog -->|Cung cấp ma_mon_hoc| Bounties

    Identity -->|Cung cấp ma_nguoi_dung| Docs
    Identity -->|Cung cấp ma_nguoi_dung| Reviews
    Identity -->|Cung cấp ma_nguoi_dung| Bounties
    Identity -->|Cung cấp hồ sơ KYC| Moderation

    Docs -->|Cung cấp tài liệu nộp giải quyết| Bounties
    Docs -->|Chuyển tiếp báo cáo vi phạm| Moderation

    Reviews -->|Chuyển tiếp báo cáo review| Moderation

    Bounties -->|Đẩy hàng đợi duyệt giải ngân| Moderation
    Moderation -->|Kích hoạt thu hồi điểm Clawback| Bounties

    Moderation -->|Ghi nhật ký kiểm toán bất biến| Admin
    Bounties -->|Ghi nhật ký giải ngân| Admin
    Identity -->|Ghi nhật ký thay đổi trạng thái user| Admin

    Moderation -->|Bắn Domain Events| Notifications
    Bounties -->|Bắn Domain Events| Notifications
    Reviews -->|Bắn Domain Events| Notifications
    Identity -->|Bắn Domain Events| Notifications

    style Identity fill:#e0f2fe,stroke:#0284c7,stroke-width:2px,color:#0f172a;
    style Catalog fill:#fef3c7,stroke:#d97706,stroke-width:2px,color:#0f172a;
    style Docs fill:#dcfce7,stroke:#16a34a,stroke-width:2px,color:#0f172a;
    style Reviews fill:#f3e8ff,stroke:#9333ea,stroke-width:2px,color:#0f172a;
    style Bounties fill:#fee2e2,stroke:#ef4444,stroke-width:2px,color:#0f172a;
    style Moderation fill:#ffedd5,stroke:#ea580c,stroke-width:2px,color:#0f172a;
    style Admin fill:#f1f5f9,stroke:#475569,stroke-width:2px,color:#0f172a;
    style Notifications fill:#e0e7ff,stroke:#4f46e5,stroke-width:2px,color:#0f172a;
```

