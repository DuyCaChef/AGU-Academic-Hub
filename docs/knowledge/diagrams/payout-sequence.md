# SƠ ĐỒ TUẦN TỰ DUYỆT GIẢI NGÂN BOUNTY AN TOÀN (SAFE PAYOUT SEQUENCE DIAGRAM)
**Vị trí:** `docs/knowledge/diagrams/payout-sequence.md`  
**Mục đích:** Đặc tả các bước thực thi an toàn trong `ApproveBountyPayoutAction` bao gồm khóa bi quan, kiểm tra trạng thái và cam kết giao dịch.

```mermaid
%%{init: {"themeVariables": {"noteBkgColor": "#fef3c7", "noteBorderColor": "#d97706", "noteTextColor": "#0f172a"}}}%%
sequenceDiagram
    autonumber
    actor Admin as Cán bộ Admin / Moderator
    participant Action as ApproveBountyPayoutAction
    participant DB as MySQL InnoDB
    participant Evaluator as ReputationEvaluator
    participant Repo as WishlistBountyRepository
    participant Audit as AuditLogger
    participant Event as Domain Event Dispatcher

    Admin->>Action: execute(NguoiDung $actor, int $maYeuCau)
    Action->>Action: Kiểm tra quyền: laQuanTriVien() hoặc laKiemDuyetVien() (Nếu không -> abort 403)
    
    rect rgba(128, 128, 128, 0.12)
        note over Action, DB: PHẠM VI GIAO DỊCH DATABASE TRANSACTION
        Action->>DB: Bắt đầu giao dịch (BEGIN TRANSACTION)
        
        Action->>DB: 1. SELECT * FROM yeu_cau_tai_lieu WHERE ma_yeu_cau = ? FOR UPDATE (Khóa hàng Yêu cầu)
        DB-->>Action: Trả về $yeuCau
        Action->>Action: Guard Clause: $yeuCau->trang_thai === 'CHO_GIAI_NGAN'? (Nếu sai -> dừng an toàn)
        
        Action->>DB: 2. SELECT * FROM dap_ung_yeu_cau_tai_lieu WHERE ma_dap_ung = ? FOR UPDATE (Khóa hàng Bài nộp)
        DB-->>Action: Trả về $dapUng
        Action->>Action: Guard Clause: $dapUng->trang_thai === 'NGUOI_YEU_CAU_XAC_NHAN'? (Nếu sai -> dừng an toàn)

        Action->>Evaluator: 3. giaiNganDiemBounty($yeuCau, $nguoiDongGop)
        Evaluator->>Evaluator: Sinh Idempotency Key: 'giai_ngan_yc_X_nd_Y_nguoi_tao'
        Evaluator->>DB: Kiểm tra xem đã giải ngân chưa (exists in giao_dich_diem)
        
        Evaluator->>DB: Khóa ví người tạo: SELECT * FROM vi_diem WHERE ma_nguoi_dung = A FOR UPDATE
        Evaluator->>DB: UPDATE vi_diem SET diem_tam_giu = diem_tam_giu - 50 (Ví A)
        Evaluator->>DB: INSERT INTO giao_dich_diem (GIAI_PHONG, -50 tam giu, IdempotencyKey_A)

        Evaluator->>DB: Khóa ví người nhận: SELECT * FROM vi_diem WHERE ma_nguoi_dung = B FOR UPDATE
        Evaluator->>DB: UPDATE vi_diem SET diem_kha_dung = diem_kha_dung + 50 (Ví B)
        Evaluator->>DB: INSERT INTO giao_dich_diem (CONG_DIEM, +50 kha dung, IdempotencyKey_B)
        Evaluator->>DB: UPDATE nguoi_dung SET diem_uy_tin = diem_uy_tin + 50 WHERE ma_nguoi_dung = B

        Action->>Repo: 4. approvePayout($yeuCau, $dapUng, $actorId)
        Repo->>DB: UPDATE yeu_cau_tai_lieu SET trang_thai = 'DA_HOAN_THANH'
        Repo->>DB: UPDATE dap_ung_yeu_cau_tai_lieu SET trang_thai = 'CHAP_NHAN'

        Action->>Audit: 5. log($actor, DUYET_GIAI_NGAN_BOUNTY, ...)
        Audit->>DB: INSERT INTO nhat_ky_kiem_toan (Bất biến, không thể sửa/xóa)

        Action->>DB: 6. COMMIT TRANSACTION (Giải phóng toàn bộ Lock)
    end

    Action->>Event: 7. DB::afterCommit -> Phát sự kiện BountyFulfillmentConfirmedEvent
    Event-->>Admin: Trả về kết quả: 'Đã duyệt giải ngân thành công!'
```

