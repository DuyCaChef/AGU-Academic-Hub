# SƠ ĐỒ MÁY TRẠNG THÁI BOUNTY & BÀI NỘP (BOUNTY & CONTRIBUTION STATE MACHINES)
**Vị trí:** `docs/knowledge/diagrams/bounty-state-machine.md`  
**Mục đích:** Đặc tả tường minh các trạng thái hợp lệ, điều kiện chuyển trạng thái và các tác nhân được phép can thiệp.

## title Máy Trạng Thái Yêu Cầu Săn Thưởng (YeuCauTaiLieu)
```mermaid
stateDiagram-v2
    
    [*] --> MO: CreateBountyRequestAction<br>[SV tạo yêu cầu & Đóng băng điểm TAM_GIU]
    
    MO --> CHO_GIAI_NGAN: ConfirmBountyFulfillmentAction<br>[SV yêu cầu nghiệm thu chấp thuận tài liệu]
    MO --> DA_HUY: CancelBountyRequestAction<br>[SV yêu cầu hủy khi chưa có bài nộp duyệt -> Hoàn cọc]
    MO --> DONG: SV chủ động đóng yêu cầu
    
    CHO_GIAI_NGAN --> DA_HOAN_THANH: ApproveBountyPayoutAction<br>[Admin/Mod duyệt giải ngân -> Thực chi điểm]
    CHO_GIAI_NGAN --> MO: RejectBountyPayoutAction<br>[Admin/Mod từ chối giải ngân kèm lý do]
    
    DA_HOAN_THANH --> [*]
    DA_HUY --> [*]
    DONG --> [*]
```

   ## title Máy Trạng Thái Bài Nộp Đóng Góp (DapUngYeuCauTaiLieu)
```mermaid
stateDiagram-v2

    [*] --> CHO_DUYET: Nộp bằng file mới từ máy tính (Method B)
    [*] --> DA_DUYET: Nộp bằng file có sẵn đã duyệt từ kho (Method A)

    CHO_DUYET --> DA_DUYET: DecideDocumentReviewAction (KEEP)<br>[Moderator duyệt file học thuật hợp lệ]
    CHO_DUYET --> TU_CHOI: DecideDocumentReviewAction (DELETE)<br>[Moderator xóa/từ chối file rác]

    DA_DUYET --> NGUOI_YEU_CAU_XAC_NHAN: ConfirmBountyFulfillmentAction<br>[Người yêu cầu nghiệm thu nội dung]
    DA_DUYET --> TU_CHOI: RejectBountyFulfillmentAction<br>[Người yêu cầu từ chối kèm lý do]

    NGUOI_YEU_CAU_XAC_NHAN --> CHAP_NHAN: ApproveBountyPayoutAction<br>[Admin/Mod duyệt giải ngân thành công]
    NGUOI_YEU_CAU_XAC_NHAN --> TU_CHOI: RejectBountyPayoutAction<br>[Admin/Mod từ chối giải ngân]

    CHAP_NHAN --> [*]
    TU_CHOI --> [*]
```

