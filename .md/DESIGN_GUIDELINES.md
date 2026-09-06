# 🎓 Design Guidelines & UI/UX Specification

**Project:** Nền tảng Tri thức & Cộng đồng Sinh viên Nội bộ (AGU Academic Hub)  
**UI/UX Version:** v1.0.0-PROD  
**Tech Stack:** Laravel Blade, Tailwind CSS, React.

## 1. 🎯 Overview (Tổng quan)

### Visual style (Phong cách trực quan)

- Sạch sẽ, hiện đại, mang đậm tính học thuật và đáng tin cậy.
- Sử dụng Light Theme (Nền sáng) làm chủ đạo để tối ưu hóa việc đọc tài liệu văn bản dài.
- Thiết kế dạng "Hub / Dashboard" giúp điều hướng nhanh chóng giữa các khoa, môn học và tài liệu.

### Target experience (Trải nghiệm mục tiêu)

- Nhanh chóng & Chính xác: Tối ưu hóa thanh tìm kiếm toàn cục (Global Search) theo Mã học phần và Tên môn học.
- An toàn & Minh bạch: Giao diện thể hiện rõ trạng thái bảo mật (KYC ẩn, Đánh giá ẩn danh).
- Mobile-First cho Tra cứu: Sinh viên dễ dàng xem trước (preview) tài liệu PDF, Office ngay trên điện thoại.
- Phản hồi tức thì (Real-time feedback): Các luồng phức tạp như Lọc trùng tài liệu (Deduplication) phải có cảnh báo UI rõ ràng, tránh gây bối rối.

### Core principles (Nguyên tắc cốt lõi)

- Mọi màn hình phải tuân thủ hệ thống màu sắc nhận diện của Đại học An Giang (AGU Blue & Teal).
- Không thêm các màu sắc neon, hiệu ứng đổ bóng quá gắt hoặc phông chữ mang tính giải trí.
- Các chức năng bị khóa (do phân quyền hoặc Community Ownership) phải hiển thị icon "Khóa" kèm Tooltip giải thích, không được ẩn đi một cách khó hiểu.

## 2. 🎨 Color System (Hệ thống Màu sắc)

Hệ thống màu bám sát bộ nhận diện AGU, cấu hình trực tiếp vào tailwind.config.js.

### 🔹 Primary & Secondary Colors

- **#005baa (AGU Deep Blue):** Primary - Màu thương hiệu, Header, nút Call-to-Action (CTA) chính, liên kết văn bản.
- **#00a896 (Teal Green):** Secondary - CTA phụ, điểm uy tín (Reputation), các chỉ số tăng trưởng, trạng thái hoàn thành.

### 🔹 Semantic Colors (Màu trạng thái)

- **#10b981 (Success):** Duyệt KYC thành công, giải quyết xong Bounty, thông báo thành công.
- **#f48c06 (Warning / Accent):** Cảnh báo tỷ lệ trùng lặp nội dung >85%, Điểm thưởng Wishlist (Bounty Points), trạng thái Chờ duyệt.
- **#e63946 (Danger / Error):** Báo cáo vi phạm, Xóa mềm (Soft Delete), Lỗi trùng mã băm 100%, Khóa tài khoản.
- **#0284c7 (Info):** Tooltip giải thích luật chơi, tag phân loại tài liệu (Đề thi, Đồ án).

### 🔹 Background & Typography Colors

- **#f8fafc (Slate 50):** Main Background - Nền trang tổng thể (Light Mode).
- **#ffffff (White):** Surface - Nền thẻ tài liệu (Cards), Modal, Dropdown, Vùng hiển thị form.
- **#0f172a (Slate 900):** Admin/Dark Surface - Sử dụng riêng cho Sidebar của Bảng điều khiển Quản trị (Admin Control Panel).
- **#0f172a (Slate 900):** Text Primary - Tiêu đề, Tên môn học, nội dung chính.
- **#475569 (Slate 600):** Text Secondary - Mô tả phụ, ngày tháng, tên tác giả, placeholder.
- **#e2e8f0 (Slate 200):** Borders - Viền mỏng phân cách các section, viền input mặc định.

## 3. ✍️ Typography (Nghệ thuật Chữ)

### Font family

- **Primary font:** Inter hoặc Be Vietnam Pro (Bắt buộc để hiển thị dấu thanh Tiếng Việt hoàn hảo không bị lỗi baseline).
- **Monospace font:** JetBrains Mono hoặc Fira Code (Dành riêng cho việc hiển thị Mã học phần, Mã băm SHA-256, Code snippets).

### Typography scale (Thang đo chữ)

| Token      | Size    | Weight        | Line-height | Usage (Ứng dụng)                                     |
| ---------- | ------- | ------------- | ----------- | ---------------------------------------------------- |
| Heading 1  | 24-28px | 700 (Bold)    | 1.2         | Tên môn học chi tiết, Tiêu đề trang chính.           |
| Heading 2  | 20-22px | 600 (Semi)    | 1.3         | Tiêu đề phân mục, Tên tài liệu trong trang chi tiết. |
| Heading 3  | 16-18px | 600 (Semi)    | 1.4         | Tiêu đề Card tài liệu, Label của khối.               |
| Body Large | 16px    | 400 (Regular) | 1.6         | Nội dung Review môn học, Bài đăng diễn đàn.          |
| Body Small | 14px    | 400 (Regular) | 1.5         | Meta data (Lượt tải, Ngày đăng), Gợi ý form.         |
| Badge/Meta | 12px    | 500 (Medium)  | 1           | Mã học phần (Badge), Version tài liệu (v1.1).        |

### Typography rules

- Tối ưu hóa độ cao dòng (leading-relaxed / 1.6) cho các đoạn văn bản mô tả tài liệu hoặc bài đánh giá để sinh viên dễ đọc.
- Các mã học phần (vd: COS101) phải luôn được bọc trong thẻ <kbd> hoặc Badge có font Monospace để dễ nhận diện.

## 4. 📐 Layout & Spacing (Bố cục & Khoảng cách)

### Spacing scale

Sử dụng hệ thống lưới 8px (Tailwind mặc định):

- **4px / 8px:** Khoảng cách icon và text, gap giữa các badge.
- **16px:** Padding chuẩn cho các Card, Modal.
- **24px:** Khoảng cách giữa các hàng/cột trong lưới tài liệu.
- **32px - 48px:** Khoảng cách giữa các Section lớn.

### Container & Layout

- **Cổng Sinh Viên (Student Hub):** Sử dụng Layout 2 cột trên Desktop (Sidebar trái 260px cố định chứa Khoa/Môn học, Vùng nội dung chính max-w-6xl ở giữa).
- **Màn hình Đọc (Preview Document):** Ưu tiên mở rộng tối đa không gian (Fluid Layout) cho component PDF Viewer.
- **Admin Panel:** Sidebar 280px nền tối, phần còn lại là không gian quản trị bảng biểu.

## 5. 🧩 Key UI Components (Thành phần UI Cốt lõi)

### 🔹 Nút bấm (Buttons)

- **Primary:** Nền #005baa, chữ trắng, bo góc vừa phải (rounded-md), hiệu ứng shadow nhỏ.
- **Secondary/Outline:** Viền #cbd5e1, nền trắng, chữ Slate-700. Dùng cho nút Hủy, Đóng modal.
- **Bounty/Action:** Nền #00a896 (Teal), dùng cho "Săn thưởng", "Nộp KYC".
- **Hover/Active:** Giảm lightness 10% (VD: hover:bg-blue-800), hiệu ứng scale nhẹ (active:scale-95).
- **Luật:** Khi call API, nút bắt buộc chuyển sang trạng thái Loading (hiển thị Spinner, disabled) để chống double-click.

### 🔹 Inputs, Forms & Dropzone

- Mọi Input phải có Label rõ ràng, dấu \* màu đỏ nếu bắt buộc.
- Upload Dropzone: Khu vực kéo thả file phải có nét đứt. Khi người dùng kéo file vào, chuyển màu nền sang xanh nhạt bg-blue-50 để phản hồi tương tác.

### 🔹 Document Card (Thẻ Tài liệu)

- Thiết kế thẻ bọc trong viền mỏng border-slate-200, đổ bóng hover:shadow-md.
- Bắt buộc hiển thị các thông tin: Loại tài liệu (Badge màu), Version (nếu có), Tên tài liệu, Lượt tải, Số Upvote.
- Community Ownership Lock: Nếu downloads >= 10 hoặc rating >= 4, hiển thị icon Khiên Bảo Vệ 🛡️ góc phải trên cùng của thẻ.

### 🔹 Badges & Tags (Nhãn & Phân loại)

- Đề thi: Nền Cam nhạt, chữ Cam đậm.
- Bài giảng: Nền Xanh ngọc nhạt, chữ Xanh ngọc đậm.
- Đồ án: Nền Tím nhạt, chữ Tím đậm.
- Trạng thái: Dùng màu Semantic (Xanh lá = Hoạt động, Đỏ = Khóa, Vàng = Chờ duyệt).

## 6. ✨ Motion, Effects & Interaction (Chuyển động & Tương tác)

- Tốc độ & Tính chất: Nhanh gọn, mượt mà (150ms - 300ms). Hệ thống tập trung vào công việc (productivity), không làm animation bay lượn rườm rà.

### Lọc trùng lặp (Deduplication UI)

- Trùng 100% (SHA-256): Khung Dropzone rung lắc nhẹ (Shake animation) và chuyển viền đỏ lập tức.
- Trùng nội dung >85%: Hiển thị Modal Slide-up (từ dưới lên) cảnh báo và gọi ý "Tạo Version mới" một cách lịch sự.
- Giải ngân Bounty: Khi người yêu cầu ấn "Xác nhận tài liệu đúng", hiển thị hiệu ứng Confetti (pháo hoa nhỏ) để chúc mừng người đóng góp được cộng điểm.

## 7. 📱 Responsive & Mobile-First Rules (Quy tắc Đa nền tảng)

### Mobile (< 768px)

- Layout 1 cột. Sidebar chuyển thành Drawer (Menu vuốt từ trái sang).
- Bộ lọc (Filters) chuyển thành Bottom Sheet (Vuốt từ dưới lên) để dễ bấm bằng một tay.
- Tối ưu hóa PDF Viewer: Phải có nút "Mở toàn màn hình" hoặc "Tải về" vì màn hình nhỏ khó đọc.

### Tablet (768px - 1024px)

- Lưới tài liệu (Grid) hiển thị 2 cột.
- Bảng Admin có thể cho phép cuộn ngang (overflow-x-auto).

### Desktop (> 1024px)

- Tận dụng hover states, hiển thị tooltip chi tiết. Lưới tài liệu 3-4 cột.

## 8. ⚡ Performance & Frontend Optimization (Tối ưu Hiệu năng)

> (Bắt buộc tuân thủ để tránh làm treo trình duyệt sinh viên)

- **Loading States:** Không dùng Spinner toàn màn hình. Bắt buộc dùng Skeleton UI (Khung xám nhấp nháy) có hình dạng giống hệt Thẻ tài liệu / Bảng dữ liệu để giữ vững layout khi API đang fetch.
- **Debounce Search:** Thanh tìm kiếm Mã học phần/Tên môn học phải gắn Debounce (300-500ms) để tránh gọi API /courses liên tục khi người dùng đang gõ.
- **PDF/Office Viewer:** Trình xem trước (Preview) bắt buộc dùng cơ chế Lazy Load hoặc Render theo trang (Paginated Render). Không load toàn bộ file PDF 50MB vào RAM của trình duyệt cùng lúc.
- **Client-side Hashing:** Việc tính mã băm SHA-256 (Lọc trùng Lớp 1) ưu tiên thực hiện bằng Web Worker trên trình duyệt của người dùng trước khi upload file vật lý, giúp tiết kiệm băng thông.

## 9. ♿ Accessibility & Usability (Khả năng Truy cập)

- Mọi Input phải dùng phím Tab để di chuyển được.
- Modal (Đăng nhập, Upload, Báo cáo) khi mở lên phải tự động Focus vào input đầu tiên, và có thể đóng bằng phím ESC.
- Form Review Đánh giá môn học: Input nhận xét (ẩn danh) cần bôi đỏ/gạch chân trực tiếp (Real-time) nếu người dùng gõ phải từ khóa lăng mạ, cấm kỵ (Bad-words filter).

## 10. 🔐 Role-Based UI Rules (Quy tắc Giao diện theo Phân quyền)

Giao diện (Đặc biệt là các nút hành động) phải Render có điều kiện dựa trên Role của Token:

- **Khách (Guest):** Chỉ nhìn thấy trang Landing và Form Đăng nhập/KYC. Không thấy thanh search tài liệu.
- **Sinh viên / Cựu Sinh viên:** Thấy toàn bộ Cổng học tập.
- **Nút Xóa tài liệu:** Chỉ hiện ở file của chính họ VÀ downloads == 0 VÀ time < 24h.
- **Nút Request Removal (Xin gỡ):** Xuất hiện thay thế nút Xóa khi tài liệu bị khóa bởi Community Ownership.
- **Moderator:** Có nút "Duyệt nhanh", "Ẩn tài liệu", vào được Hàng đợi báo cáo (Report Queue).
- **Admin:** Có menu riêng truy cập System Settings, Hàng đợi KYC, Khôi phục thùng rác (Restore).

## 11. ✅ UI Design and Implementation Checklist (Danh sách Kiểm tra)

- [ ] Giao diện có giữ màu nền sáng, font chữ dễ đọc văn bản dài không?
- [ ] Đã cấu hình Form KYC để người dùng có thể tải ảnh Thẻ SV / Bằng tốt nghiệp dễ dàng chưa?
- [ ] Luồng Tải lên (Upload) đã xử lý UI cho cả 2 trường hợp: Báo lỗi Conflict 409 (Trùng băm) và Gợi ý v1.1 (Trùng nội dung >85%) chưa?
- [ ] Nút "Xóa" đã biến mất và thay bằng "Khóa - Sở hữu cộng đồng" đối với file >10 lượt tải chưa?
- [ ] Form đánh giá môn học (Course Review) có giao diện chấm điểm 3 tiêu chí trực quan không?
- [ ] Modal Báo cáo vi phạm (Report) có đủ các lý do: Trùng lặp, Sai nội dung, Mã độc, Bản quyền chưa?

## 12. 🚫 What Not To Do (Những điều KHÔNG ĐƯỢC LÀM)

- KHÔNG thiết kế Dark Mode làm mặc định. Đây là ứng dụng đọc tài liệu học thuật, Dark mode ép buộc sẽ gây mỏi mắt.
- KHÔNG dùng alert() mặc định của trình duyệt. Mọi phản hồi lỗi (Toast/Modal) phải được custom theo Design System.
- KHÔNG cho phép submit form tải lên tài liệu mà chưa chọn Phân loại (Đề thi/Bài giảng...) và Mã học phần.
- KHÔNG ẩn hoàn toàn tài liệu khi bị report 1 lần. Chỉ chuyển sang UI TAM_AN_KIEM_TRA khi đủ $\ge 3$ reports.

## 13. 📝 Developer & AI Agent Notes (Hướng dẫn Coder & AI Prompt)

Hệ thống AI (Cursor, GitHub Copilot, v0.dev) chú ý:

- Khi generate components cho dự án này, hãy tuân thủ nghiêm ngặt Tailwind CSS classes. Sử dụng #005baa cho Primary, #00a896 cho Secondary.
- Các form bắt buộc phải có error states text (màu #e63946) ngay dưới input.
- Bất kỳ component "Xóa" nào liên quan đến tài liệu phải giả định có trạng thái is_community_owned để render fallback UI (Nút Xin Gỡ Tài Liệu) thay vì nút Xóa thông thường.
