# AGU ACADEMIC HUB — BỘ TÀI LIỆU TÁI CẤU TRÚC TRI THỨC KỸ THUẬT & CẨM NANG BẢO VỆ ĐỒ ÁN
## MASTER PROJECT KNOWLEDGE RECONSTRUCTION & THESIS DEFENSE DOSSIER
**Phiên bản hoàn thiện:** 1.0 (Tháng 11/2026)  
**Cơ quan chủ quản:** Trường Đại học An Giang — Đại học Quốc gia TP. Hồ Chí Minh  
**Mục tiêu:** Cung cấp tài liệu kỹ thuật hoàn chỉnh, chuẩn xác và có hệ thống nhất về nền tảng AGU Academic Hub. Giúp chủ dự án hoàn toàn làm chủ hệ thống, tự tin bảo vệ đồ án trước Hội đồng đánh giá, và có khả năng tự sửa đổi, mở rộng tính năng mà không bị phụ thuộc.

---

## 1. BẢN ĐỒ CHỈ MỤC TOÀN BỘ BỘ TÀI LIỆU (DOCUMENTATION INDEX)

Tất cả các tài liệu được lưu trữ có cấu trúc trong thư mục `docs/knowledge/`:

```text
docs/
└── knowledge/
    ├── README.md                                    # (Bản đồ này) Chỉ mục trung tâm & Hướng dẫn học tập
    ├── WORK_PROGRESS.md                             # Nhật ký tiến độ 6 Phase & Inventory 55 tài liệu nguồn
    ├── FINAL_DOCUMENTATION_AUDIT.md                 # Báo cáo nghiệm thu & Trả lời 12 câu hỏi kiểm toán
    │
    ├── 00_PROJECT_HISTORY.md                        # Lịch sử 8 Phase phát triển, khủng hoảng merge & quyết định kiến trúc
    ├── 01_REQUIREMENT_IMPLEMENTATION_TRACEABILITY.md# Ma trận đối chiếu 33 yêu cầu BRD/SRS với Code & Test
    ├── 02_PROJECT_CONFLICTS_AND_GAPS.md             # Báo cáo 10 mâu thuẫn kỹ thuật: REST vs Inertia, Deadlock Zone, v.v.
    │
    ├── 03_SYSTEM_ARCHITECTURE.md                    # Kiến trúc tổng thể, 4-Tier Modular Monolith, 2 luồng request end-to-end
    ├── 04_MODULES_AND_USECASES.md                   # Khảo sát chi tiết 8 Bounded Contexts, RBAC matrix, use cases
    ├── 05_BOUNTY_DEEP_DIVE.md                       # Chuyên sâu Bounty: Escrow, Máy trạng thái, Chống Double Payout, Luồng sửa v2.0
    │
    ├── 06_TECHNICAL_HIGHLIGHTS_AND_TRADEOFFS.md     # Top 8 kỹ thuật nổi bật, phân tích Trade-off đa tiêu chí
    ├── 07_DATABASE_DEEP_DIVE.md                     # CSDL: Sơ đồ ERD 23 bảng tiếng Việt, indexes, chống N+1 Query
    ├── 08_SECURITY_AND_AUTHORIZATION.md             # An ninh: RBAC, IDOR, File Upload, Signed URL 30p, CSRF, SQLi
    ├── 09_TESTING_AND_QUALITY.md                    # Kiểm thử: 33 feature tests, test tương tranh, đối chiếu kiểm thử UI
    │
    ├── 10_CODE_READING_AND_CHANGE_GUIDE.md          # Cẩm nang định vị file 10 bước, hướng dẫn thêm field, gỡ lỗi 403/419/422
    ├── 11_THESIS_DEFENSE_GUIDE.md                   # Bộ câu hỏi phản biện 8 cấp độ & câu trả lời mẫu theo khung 5 bước
    ├── 12_PROJECT_PRESENTATION.md                   # Dàn ý thuyết trình đồ án (Slide Deck) 11 slide chuẩn mực
    ├── 13_GLOSSARY.md                               # Bảng thuật ngữ song ngữ Việt - Anh chuẩn hóa
    ├── 14_KNOWN_LIMITATIONS_AND_FUTURE_WORK.md      # Hạn chế kỹ thuật đã biết và lộ trình nâng cấp Q1-Q4/2027
    │
    └── diagrams/                                    # Thư mục lưu trữ các sơ đồ kiến trúc Mermaid độc lập
        ├── system-context.md                        # Sơ đồ ngữ cảnh hệ thống (System Context)
        ├── architecture.md                          # Sơ đồ kiến trúc phân tầng 4 lớp
        ├── module-dependencies.md                   # Sơ đồ phụ thuộc giữa 8 Bounded Contexts
        ├── bounty-flow.md                           # Sơ đồ luồng nghiệp vụ Bounty 16 bước
        ├── bounty-state-machine.md                  # Sơ đồ máy trạng thái Bounty & Bài nộp
        ├── payout-sequence.md                       # Sơ đồ tuần tự duyệt giải ngân an toàn
        ├── database-erd.md                          # Sơ đồ quan hệ thực thể ERD 23 bảng
        └── auth-flow.md                             # Sơ đồ luồng định danh Zero-KYC & KYC Cựu SV
```

---

## 2. HƯỚNG DẪN THỨ TỰ ĐỌC HỌC TẬP THEO MỤC TIÊU (LEARNING PATHS)

### 2.1. Lộ trình Dành cho Người Muốn Hiểu Toàn Bộ Hệ Thống (Từ Đầu đến Cuối)
1. **Khởi động (Nắm bối cảnh & kiến trúc tổng quan):**
   - Đọc [`12_PROJECT_PRESENTATION.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/12_PROJECT_PRESENTATION.md) (15 phút).
   - Đọc [`00_PROJECT_HISTORY.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/00_PROJECT_HISTORY.md) (30 phút).
   - Đọc [`03_SYSTEM_ARCHITECTURE.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/03_SYSTEM_ARCHITECTURE.md) (45 phút).
2. **Nghiệp vụ chi tiết & Phân hệ trọng tâm:**
   - Đọc [`04_MODULES_AND_USECASES.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/04_MODULES_AND_USECASES.md) (60 phút).
   - Đọc kỹ phân hệ cốt lõi: [`05_BOUNTY_DEEP_DIVE.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/05_BOUNTY_DEEP_DIVE.md) (60 phút).
3. **Kỹ thuật chuyên sâu & Đánh đổi kiến trúc:**
   - Đọc [`06_TECHNICAL_HIGHLIGHTS_AND_TRADEOFFS.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/06_TECHNICAL_HIGHLIGHTS_AND_TRADEOFFS.md) (45 phút).
   - Đọc [`07_DATABASE_DEEP_DIVE.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/07_DATABASE_DEEP_DIVE.md) (30 phút).
   - Đọc [`08_SECURITY_AND_AUTHORIZATION.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/08_SECURITY_AND_AUTHORIZATION.md) (30 phút).
   - Đọc [`09_TESTING_AND_QUALITY.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/09_TESTING_AND_QUALITY.md) (30 phút).
4. **Kiểm tra chéo & Khoảng trống:**
   - Đọc [`01_REQUIREMENT_IMPLEMENTATION_TRACEABILITY.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/01_REQUIREMENT_IMPLEMENTATION_TRACEABILITY.md).
   - Đọc [`02_PROJECT_CONFLICTS_AND_GAPS.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/02_PROJECT_CONFLICTS_AND_GAPS.md).

---

### 2.2. Lộ trình Cấp Tốc Dành Cho Bảo Vệ Đồ Án (Trước Giờ G)
Chỉ cần đọc và học thuộc 3 tài liệu trọng yếu sau:
1. **[`11_THESIS_DEFENSE_GUIDE.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/11_THESIS_DEFENSE_GUIDE.md)**: Nắm chắc toàn bộ 8 câu hỏi phản biện then chốt, đặc biệt là cơ chế `lockForUpdate()`, quy trình duyệt 2 bước, và điểm yếu lớn nhất.
2. **[`05_BOUNTY_DEEP_DIVE.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/05_BOUNTY_DEEP_DIVE.md)**: Nắm vững bảng số học diễn tiến số dư ví (Khả dụng vs Tạm giữ) tại Mục 2.3 và cơ chế phòng thủ 5 lớp chống Double Payout tại Mục 4.
3. **[`06_TECHNICAL_HIGHLIGHTS_AND_TRADEOFFS.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/06_TECHNICAL_HIGHLIGHTS_AND_TRADEOFFS.md)**: Nắm chắc bảng so sánh Modular Monolith vs Microservices để trả lời câu hỏi vì sao không dùng Microservices.

---

### 2.3. Lộ trình Dành Cho Lập Trình Viên Muốn Sửa Code & Thêm Tính Năng
1. Mở ngay **[`10_CODE_READING_AND_CHANGE_GUIDE.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/10_CODE_READING_AND_CHANGE_GUIDE.md)**: Làm theo đúng quy trình 10 bước để tìm file và kịch bản thêm field/thay đổi nghiệp vụ.
2. Tra cứu cấu trúc bảng và ràng buộc tại **[`07_DATABASE_DEEP_DIVE.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/07_DATABASE_DEEP_DIVE.md)** trước khi viết migration.
3. Chạy kiểm thử hồi quy theo hướng dẫn tại **[`09_TESTING_AND_QUALITY.md`](file:///d:/CNWEB/AGU-Academic-Hub/docs/knowledge/09_TESTING_AND_QUALITY.md)** (`php artisan test`).

---

## 3. NGUYÊN TẮC BẤT DI BẤT DỊCH CỦA BỘ TÀI LIỆU
- **100% Khớp với Source Code:** Mọi đường dẫn file, tên class, tên method và ràng buộc CSDL đều được kiểm chứng trực tiếp trên mã nguồn thật của dự án, không có mã giả định hay số dòng bịa đặt.
- **Minh bạch Trạng thái:** Phân định rạch ròi giữa tính năng đã kiểm chứng (`IMPLEMENTED & VERIFIED`) với tính năng mới chỉ thiết kế (`DESIGNED ONLY`).
- **Trung thực Kỹ thuật:** Chủ động chỉ ra các điểm yếu, vùng chết nghiệp vụ và hướng xử lý, tạo phong thái đĩnh đạc của một kỹ sư phần mềm chuyên nghiệp trước Hội đồng đánh giá.

