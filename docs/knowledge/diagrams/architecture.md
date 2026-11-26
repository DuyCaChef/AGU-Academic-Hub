# SƠ ĐỒ KIẾN TRÚC PHÂN TẦNG 4 LỚP (4-TIER LAYERED ARCHITECTURE DIAGRAM)
**Vị trí:** `docs/knowledge/diagrams/architecture.md`  
**Mục đích:** Đặc tả cấu trúc phân tầng bên trong mỗi Bounded Context theo chuẩn Domain-Driven Design (DDD).

```mermaid
graph TD
    subgraph PresentationTier ["1. PRESENTATION LAYER (TẦNG HIỂN THỊ & TIẾP NHẬN)"]
        Controllers["Controllers (~30-70 dòng)<br>• Nhận Request từ Inertia<br>• Gọi Action hoặc Query tương ứng<br>• Trả về Inertia::render() hoặc JSON"]
        FormRequests["Form Requests<br>• Xác thực tính hợp lệ dữ liệu (Validation)<br>• Kiểm tra quyền cơ bản (authorize)"]
        Resources["Eloquent API Resources<br>• Format dữ liệu phản hồi<br>• Thực thi ẩn danh 2 lớp bảo vệ PII"]
        InertiaPages["React Pages & Layouts<br>• Render DOM, quản lý state Form<br>• Tương tác qua Inertia protocol"]
    end

    subgraph ApplicationTier ["2. APPLICATION LAYER (TẦNG ĐIỀU PHỐI USE CASES - CQS)"]
        Actions["Command Actions (Thay đổi trạng thái)<br>• Tạo mới, cập nhật, xóa bản ghi<br>• Điều phối DB::transaction()<br>• Bắn Domain Events qua afterCommit"]
        Queries["Query Handlers (Chỉ đọc dữ liệu)<br>• Nạp dữ liệu có phân trang<br>• Eager loading with() chống N+1<br>• Tích hợp bộ nhớ đệm Cache::remember"]
        DTOs["DTOs & Contracts<br>• Đóng gói tham số truyền giữa các tầng<br>• Định nghĩa Interface mở rộng"]
    end

    subgraph DomainTier ["3. DOMAIN LAYER (TẦNG QUY TẮC NGHIỆP VỤ CỐT LÕI)"]
        Evaluators["Domain Evaluators & Services<br>• ReputationEvaluator: Điểm ví & Khóa hàng<br>• DeduplicationEvaluator: Băm SHA-256<br>• ProfanityFilterEvaluator: Lọc từ cấm<br>• RemovalEvaluator: Sở hữu cộng đồng"]
        Enums["PHP 8.2 Backed Enums<br>• TrangThaiYeuCauTaiLieu, LoaiGiaoDichDiem<br>• VaiTroNguoiDung, TrangThaiTaiLieu"]
        Entities["Eloquent Entities / Models<br>• Ràng buộc quan hệ belongsTo, hasMany<br>• Booted hooks bảo vệ bất biến"]
    end

    subgraph InfrastructureTier ["4. INFRASTRUCTURE LAYER (TẦNG HẠ TẦNG & TRUY XUẤT)"]
        Repositories["Repositories (Truy vấn CSDL)<br>• Tương thích đa CSDL (MySQL vs SQLite)<br>• Tối ưu hóa truy vấn phức tạp"]
        StorageAdapters["Storage Adapters<br>• Private Storage Disk (Lưu trữ học liệu)<br>• Secure KYC Disk (Signed URL 30p)"]
        Notifications["Notification Dispatchers & Listeners<br>• Lắng nghe Domain Events<br>• Gửi thông báo có phân loại Category"]
    end

    InertiaPages <-->|HTTP / JSON Props| Controllers
    Controllers --> FormRequests
    Controllers --> Actions
    Controllers --> Queries
    Controllers --> Resources

    Actions --> Evaluators
    Actions --> Repositories
    Actions --> StorageAdapters
    Actions --> Notifications

    Queries --> Repositories
    Repositories --> Entities
    Evaluators --> Entities
    Evaluators --> Enums

    style PresentationTier fill:#e0f2fe,stroke:#0284c7,stroke-width:2px;
    style ApplicationTier fill:#fef3c7,stroke:#d97706,stroke-width:2px;
    style DomainTier fill:#dcfce7,stroke:#16a34a,stroke-width:2px;
    style InfrastructureTier fill:#f3e8ff,stroke:#9333ea,stroke-width:2px;
```

