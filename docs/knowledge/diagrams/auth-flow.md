# SƠ ĐỒ LUỒNG ĐỊNH DANH SINH VIÊN VS CỰU SINH VIÊN (AUTHENTICATION & KYC FLOW)
**Vị trí:** `docs/knowledge/diagrams/auth-flow.md`  
**Mục đích:** Đặc tả sự tách biệt rạch ròi giữa cơ chế Zero-KYC của sinh viên đang học và quy trình KYC có kiểm duyệt của cựu sinh viên.

```mermaid
flowchart TD
    Start([Người dùng truy cập Hệ thống]) --> RoleCheck{Người dùng thuộc nhóm đối tượng nào?}

    %% Phân luồng Sinh viên đang học
    RoleCheck -->|Sinh viên Đang học| LoginStudent[Truy cập /dang-nhap bằng MSSV / Email AGU]
    LoginStudent --> CheckRaw{Có trong danh sách raw nạp trước?}
    CheckRaw -->|Không| InvalidAuth[Báo lỗi: Thông tin không tồn tại -> Dừng]
    CheckRaw -->|Có| CheckActivated{Tài khoản đã kích hoạt chưa?}
    
    CheckActivated -->|Chưa kích hoạt| AutoProvision[Hệ thống tự tạo tài khoản CHUA_XAC_THUC]
    AutoProvision --> MiddlewareBlock[Middleware EnsurePasswordChanged chặn toàn bộ truy cập]
    MiddlewareBlock --> ForcePassForm[Chuyển hướng bắt buộc vào /mat-khau-lan-dau]
    ForcePassForm --> ChangePass[Sinh viên nhập mật khẩu mới an toàn]
    ChangePass --> ActivateAccount[Đổi trạng thái DANG_HOAT_DONG<br>Đánh dấu da_kich_hoat = true trên bảng raw]
    ActivateAccount --> ProvisionWallet[Tự động tạo Ví Điểm với 100 điểm khả dụng ban đầu]
    ProvisionWallet --> DashboardStudent[Vào Bảng điều khiển /trang-chu]

    CheckActivated -->|Đã kích hoạt| NormalAuth[Đăng nhập bình thường với mật khẩu mới]
    NormalAuth --> DashboardStudent

    %% Phân luồng Cựu sinh viên
    RoleCheck -->|Cựu Sinh viên| AlumniForm[Truy cập form /cuu-sinh-vien/dang-ky]
    AlumniForm --> SubmitKYC[Nhập MSSV cũ, năm tốt nghiệp + Tải ảnh bằng tốt nghiệp]
    SubmitKYC --> StorePrivate[Lưu minh chứng vào storage/app/private/kyc/]
    StorePrivate --> CreateKYCRecord[Tạo YeuCauXacThuc trạng thái CHO_DUYET<br>Tài khoản ở trạng thái CHUA_XAC_THUC]
    CreateKYCRecord --> ModQueue[Hồ sơ xuất hiện trên Hàng đợi duyệt KYC của Mod/Admin]

    ModQueue --> ModInspect[Cán bộ kiểm duyệt xem ảnh qua Signed URL 30 phút]
    ModInspect --> ModDecision{Quyết định duyệt hồ sơ?}
    
    ModDecision -->|Từ chối| RejectKYC[Lưu lý do từ chối + Gửi thông báo cho Cựu SV]
    ModDecision -->|Phê duyệt| ApproveKYC[Kích hoạt tài khoản DANG_HOAT_DONG]
    ApproveKYC --> ProvisionWalletAlumni[Tự động tạo Ví Điểm với 100 điểm khả dụng ban đầu]
    ProvisionWalletAlumni --> AlumniNotify[Cựu sinh viên nhận thông báo và có thể đăng nhập]
```

