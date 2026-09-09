# 🎓 Design Guidelines & UI/UX Specification

**Project:** Nền tảng Tri thức & Cộng đồng Sinh viên Nội bộ (AGU Academic Hub)  
**UI/UX Version:** v1.0.0-PROD  
**Tech Stack:** Laravel 11, Inertia.js v2, React 18+, Tailwind CSS v4, Vite, Ziggy, Lucide React.

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

Hệ thống màu bám sát bộ nhận diện AGU, cấu hình duy nhất bằng directive `@theme` trong `resources/css/app.css` theo chuẩn Tailwind CSS v4. Không tạo hoặc duy trì `tailwind.config.js` cho các design token này.

### 🔹 Tailwind v4 Theme Tokens

Khai báo các token sau trong `@theme` và dùng class semantic tương ứng trong toàn bộ giao diện. Không dùng trực tiếp mã hex trong component nếu đã có token.

```css
@import "tailwindcss";

@theme {
    --color-brand-primary: #005baa;
    --color-brand-secondary: #00a896;

    --color-semantic-success: #10b981;
    --color-semantic-warning: #f48c06;
    --color-semantic-danger: #e63946;
    --color-semantic-info: #0284c7;

    --color-surface-page: #f8fafc;
    --color-surface-default: #ffffff;
    --color-surface-admin: #0f172a;
    --color-text-primary: #0f172a;
    --color-text-secondary: #475569;
    --color-border-default: #e2e8f0;
}
```

Ví dụ class chuẩn: `bg-brand-primary`, `text-brand-primary`, `bg-brand-secondary`, `text-semantic-success`, `border-semantic-danger`, `bg-surface-page`, `bg-surface-default`, `bg-surface-admin`, `text-text-primary`, `text-text-secondary` và `border-border-default`.

### 🔹 Primary & Secondary Colors

- **#005baa (`brand-primary`):** Primary - Màu thương hiệu, Header, nút Call-to-Action (CTA) chính, liên kết văn bản. Dùng các class `bg-brand-primary`, `text-brand-primary`.
- **#00a896 (`brand-secondary`):** Secondary - CTA phụ, điểm uy tín (Reputation), các chỉ số tăng trưởng, trạng thái hoàn thành. Dùng các class `bg-brand-secondary`, `text-brand-secondary`.

### 🔹 Semantic Colors (Màu trạng thái)

- **#10b981 (`semantic-success`):** Duyệt KYC thành công, giải quyết xong Bounty, thông báo thành công. Dùng `bg-semantic-success`, `text-semantic-success`.
- **#f48c06 (`semantic-warning`):** Cảnh báo tỷ lệ trùng lặp nội dung >85%, Điểm thưởng Wishlist (Bounty Points), trạng thái Chờ duyệt. Dùng `bg-semantic-warning`, `text-semantic-warning`.
- **#e63946 (`semantic-danger`):** Báo cáo vi phạm, Xóa mềm (Soft Delete), Lỗi trùng mã băm 100%, Khóa tài khoản. Dùng `bg-semantic-danger`, `text-semantic-danger`.
- **#0284c7 (`semantic-info`):** Tooltip giải thích luật chơi, tag phân loại tài liệu (Đề thi, Đồ án). Dùng `bg-semantic-info`, `text-semantic-info`.

### 🔹 Background & Typography Colors

- **#f8fafc (`surface-page`):** Main Background - Nền trang tổng thể (Light Mode). Dùng `bg-surface-page`.
- **#ffffff (`surface-default`):** Surface - Nền thẻ tài liệu (Cards), Modal, Dropdown, Vùng hiển thị form. Dùng `bg-surface-default`.
- **#0f172a (`surface-admin`):** Admin/Dark Surface - Sử dụng riêng cho Sidebar của Bảng điều khiển Quản trị (Admin Control Panel). Dùng `bg-surface-admin`.
- **#0f172a (`text-primary`):** Text Primary - Tiêu đề, Tên môn học, nội dung chính. Dùng `text-text-primary`.
- **#475569 (`text-secondary`):** Text Secondary - Mô tả phụ, ngày tháng, tên tác giả, placeholder. Dùng `text-text-secondary`.
- **#e2e8f0 (`border-default`):** Borders - Viền mỏng phân cách các section, viền input mặc định. Dùng `border-border-default`.

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
- **Border radius:** Container, section và card nội dung dùng `rounded-xl` (12px); Button và Input dùng `rounded-md` (8px); Badge dùng `rounded-full`, nhãn ngắn có thể dùng `rounded`. Không sử dụng biến thể radius khác nếu chưa được thống nhất.

## 5. 🧩 Key UI Components (Thành phần UI Cốt lõi)

### 🔹 Nút bấm (Buttons)

- **Primary:** Nền `bg-brand-primary`, chữ trắng, bo góc `rounded-md` (8px), hiệu ứng shadow nhỏ.
- **Secondary/Outline:** Viền `border-border-default`, nền `bg-surface-default`, chữ `text-text-secondary`. Dùng cho nút Hủy, Đóng modal.
- **Bounty/Action:** Nền `bg-brand-secondary`, dùng cho "Săn thưởng", "Nộp KYC".
- **Hover/Active:** Giảm lightness 10% (VD: hover:bg-blue-800), hiệu ứng scale nhẹ (active:scale-95).
- **Luật:** Khi call API, nút bắt buộc chuyển sang trạng thái Loading (hiển thị Spinner, disabled) để chống double-click.

### 🔹 Inputs, Forms & Dropzone

- Mọi Input phải có Label rõ ràng, dấu \* màu đỏ nếu bắt buộc, nền `bg-surface-default`, viền `border-border-default` và bo góc `rounded-md` (8px).
- Upload Dropzone: Khu vực kéo thả file phải có nét đứt và bo góc `rounded-xl` (12px). Khi người dùng kéo file vào, chuyển màu nền sang `bg-brand-primary/5` để phản hồi tương tác.

### 🔹 Document Card (Thẻ Tài liệu)

- Thiết kế thẻ bọc trong viền mỏng `border-border-default`, nền `bg-surface-default`, bo góc `rounded-xl` (12px), đổ bóng `hover:shadow-md`.
- Bắt buộc hiển thị các thông tin: Loại tài liệu (Badge màu), Version (nếu có), Tên tài liệu, Lượt tải, Số Upvote.
- Community Ownership Lock: Nếu downloads >= 10 hoặc rating >= 4, hiển thị icon Khiên Bảo Vệ 🛡️ góc phải trên cùng của thẻ.

### 🔹 Badges & Tags (Nhãn & Phân loại)

- Đề thi: Nền `bg-semantic-warning/10`, chữ `text-semantic-warning`.
- Bài giảng: Nền `bg-brand-secondary/10`, chữ `text-brand-secondary`.
- Đồ án: Dùng token màu phụ đã được phê duyệt, không tự thêm màu tùy ý.
- Trạng thái: Dùng màu Semantic (Xanh lá = Hoạt động, Đỏ = Khóa, Vàng = Chờ duyệt) kết hợp icon hoặc text, không chỉ dựa vào màu.
- Badge dùng `rounded-full`; nhãn ngắn không cần pill dùng `rounded`.

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

Giao diện (Đặc biệt là các nút hành động) phải render có điều kiện dựa trên `auth.user.vai_tro` trong global props của Inertia. Laravel Middleware `HandleInertiaRequests` là nơi chia sẻ thông tin user/role; React đọc dữ liệu bằng `usePage().props`. Không dùng Bearer Token ở client-side để quyết định giao diện.

- **Khách (Guest):** Chỉ nhìn thấy trang Landing và Form Đăng nhập/KYC. Không thấy thanh search tài liệu.
- **Sinh viên / Cựu Sinh viên:** Thấy toàn bộ Cổng học tập.
- **Nút Xóa tài liệu:** Chỉ hiện ở file của chính họ VÀ downloads == 0 VÀ time < 24h.
- **Nút Request Removal (Xin gỡ):** Xuất hiện thay thế nút Xóa khi tài liệu bị khóa bởi Community Ownership.
- **Moderator:** Có nút "Duyệt nhanh", "Ẩn tài liệu", vào được Hàng đợi báo cáo (Report Queue).
- **Admin:** Có menu riêng truy cập System Settings, Hàng đợi KYC, Khôi phục thùng rác (Restore).
- Ví dụ đọc role: `const { auth } = usePage().props; const role = auth?.user?.vai_tro;`. Quyền hiển thị chỉ là lớp UX; Policy/Gate và controller Laravel vẫn phải kiểm tra quyền ở backend.

## 11. ✅ UI Design and Implementation Checklist (Danh sách Kiểm tra)

- [ ] Giao diện có giữ màu nền sáng, font chữ dễ đọc văn bản dài không?
- [ ] Đã cấu hình Form KYC để người dùng có thể tải ảnh Thẻ SV / Bằng tốt nghiệp dễ dàng chưa?
- [ ] Luồng Tải lên (Upload) đã xử lý UI cho cả 2 trường hợp: Báo lỗi Conflict 409 (Trùng băm) và Gợi ý v1.1 (Trùng nội dung >85%) chưa?
- [ ] Nút "Xóa" đã biến mất và thay bằng "Khóa - Sở hữu cộng đồng" đối với file >10 lượt tải chưa?
- [ ] Form đánh giá môn học (Course Review) có giao diện chấm điểm 3 tiêu chí trực quan không?
- [ ] Modal Báo cáo vi phạm (Report) có đủ các lý do: Trùng lặp, Sai nội dung, Mã độc, Bản quyền chưa?
- [ ] Liên kết nội bộ và `router.visit()` đều dùng Ziggy `route()`; không hard-code URL dạng chuỗi như `/courses/10`.

## 12. 🚫 What Not To Do (Những điều KHÔNG ĐƯỢC LÀM)

- KHÔNG thiết kế Dark Mode làm mặc định. Đây là ứng dụng đọc tài liệu học thuật, Dark mode ép buộc sẽ gây mỏi mắt.
- KHÔNG dùng alert() mặc định của trình duyệt. Mọi phản hồi lỗi (Toast/Modal) phải được custom theo Design System.
- KHÔNG cho phép submit form tải lên tài liệu mà chưa chọn Phân loại (Đề thi/Bài giảng...) và Mã học phần.
- KHÔNG ẩn hoàn toàn tài liệu khi bị report 1 lần. Chỉ chuyển sang UI TAM_AN_KIEM_TRA khi đủ $\ge 3$ reports.
- KHÔNG hard-code URL nội bộ bằng chuỗi. Bắt buộc dùng Ziggy `route('courses.show', id)` trong `Link`, thuộc tính `href`, action của component hoặc `router.visit()`.

## 13. 📝 Developer & AI Agent Notes (Hướng dẫn Coder & AI Prompt)

Hệ thống AI (Cursor, GitHub Copilot, v0.dev) chú ý:

- Khi generate components cho dự án này, hãy tuân thủ nghiêm ngặt Tailwind CSS v4 và các token `@theme`. Sử dụng `bg-brand-primary` cho Primary, `bg-brand-secondary` cho Secondary và `text-semantic-danger` cho lỗi.
- Các form bắt buộc phải có error states text (`text-semantic-danger`) ngay dưới input.
- Bất kỳ component "Xóa" nào liên quan đến tài liệu phải giả định có trạng thái is_community_owned để render fallback UI (Nút Xin Gỡ Tài Liệu) thay vì nút Xóa thông thường.
- Mọi link nội bộ phải dùng Ziggy `route()`; không tự ghép chuỗi URL và không hard-code ID vào path.

## 14. 🏗️ Frontend Architecture & File Organization (Kiến trúc Frontend)

- Laravel là nơi xử lý routing, authentication, authorization, validation, query và business rules. React chỉ chịu trách nhiệm hiển thị và tương tác người dùng.
- Mọi trang được render qua Inertia, không tạo SPA router riêng và không dùng Blade để dựng các màn hình ứng dụng sau khi đã chuyển sang Inertia.
- Quy ước thư mục chuẩn:
    - `resources/js/Pages/`: mỗi file là một màn hình tương ứng với một Inertia page.
    - `resources/js/Layouts/`: layout dùng chung theo vai trò, ví dụ `StudentLayout`, `AdminLayout`, `GuestLayout`.
    - `resources/js/Components/`: component có thể tái sử dụng giữa nhiều page.
    - `resources/js/Components/ui/`: primitive UI như Button, Input, Modal, Badge, Skeleton, Tooltip.
    - `resources/js/Components/domain/`: component theo nghiệp vụ như DocumentCard, CourseFilters, ReviewForm.
    - `resources/js/hooks/`: custom hooks dùng chung, đặt tên theo dạng `useSomething`.
    - `resources/js/lib/`: helper thuần, formatter, constants và quy tắc mapping trạng thái.
- Tên file component dùng PascalCase. Tên page dùng PascalCase và phản ánh route, ví dụ `Documents/Index.jsx`, `Documents/Show.jsx`.
- Không đặt business rule, query hoặc quyền truy cập trong React. Không hard-code dữ liệu nghiệp vụ trong component nếu dữ liệu đó có thể thay đổi từ backend.
- Mỗi page phải có một layout rõ ràng; không lặp lại header, sidebar, breadcrumb và flash-message ở từng page.

## 15. 🔗 Inertia.js & Laravel Contract (Hợp đồng Backend/Frontend)

- Backend phải truyền dữ liệu page dưới dạng props có cấu trúc ổn định, tên rõ nghĩa và nhất quán giữa các route. Không truyền một object khổng lồ nếu page chỉ cần một phần dữ liệu.
- Props dạng danh sách phải dùng paginator/resource rõ ràng, gồm tối thiểu `data`, `links` và `meta` khi cần phân trang. UI không tự đoán cấu trúc response.
- Dùng `Link` của Inertia cho điều hướng nội bộ và Ziggy `route()` để tạo URL. Chỉ dùng thẻ `<a>` cho URL bên ngoài, file download hoặc anchor thực sự.
- Cài và dùng `tightenco/ziggy` làm route helper thống nhất. Ví dụ: `<Link href={route('courses.show', course.id)}>`, hoặc `router.visit(route('courses.show', course.id))`. Cấm hard-code URL dạng `/courses/10`.
- Dùng `router.get`, `router.post`, `router.patch`, `router.delete` hoặc `useForm` cho mutation; không dùng `fetch`/Axios trực tiếp cho luồng Inertia nếu không có lý do kỹ thuật rõ ràng.
- Tìm kiếm, lọc và sắp xếp phải đồng bộ với query string để URL có thể bookmark, refresh và dùng nút Back/Forward của trình duyệt.
- Khi lọc hoặc phân trang, giữ lại các filter đang có bằng `preserveState` và tránh reset scroll nếu người dùng chưa rời khỏi ngữ cảnh hiện tại.
- Flash message từ session phải được hiển thị qua một component dùng chung. Không dùng `alert()` mặc định của trình duyệt.
- Lỗi validation từ Laravel phải hiển thị ngay tại field tương ứng; lỗi hệ thống hoặc lỗi mạng phải hiển thị bằng Toast/Banner có thể đóng.
- UI phải đọc role từ `usePage().props.auth.user.vai_tro`, được chia sẻ qua Laravel Middleware `HandleInertiaRequests`; đây chỉ là lớp hiển thị. Policy/Gate và controller Laravel vẫn là nơi quyết định quyền cuối cùng.

## 16. ⚛️ React Component Rules (Quy ước Component React)

- Component phải có một trách nhiệm chính, ưu tiên composition hơn component page quá lớn.
- Props phải được đặt tên mô tả và có giá trị mặc định hợp lý. Không truyền nhiều boolean có quan hệ mơ hồ; dùng prop trạng thái rõ nghĩa hoặc object cấu hình.
- Component dùng chung không được phụ thuộc trực tiếp vào route hoặc dữ liệu của một page cụ thể nếu có thể truyền qua props.
- Ưu tiên HTML semantic: `button`, `nav`, `main`, `aside`, `section`, `form`, `label`, `table` đúng với ngữ nghĩa; không dùng `div` thay cho control tương tác.
- Button phải khai báo rõ `type="button"`, `type="submit"` hoặc `type="reset"`. Nút loading phải disabled và không được gửi request lần hai.
- Không dùng inline style, CSS viết riêng hoặc class Tailwind ngẫu hứng nếu token tương ứng đã có trong design system.
- Icon phải đến từ một thư viện thống nhất của dự án. Icon-only button bắt buộc có `aria-label` và tooltip khi ý nghĩa không hiển nhiên.
- Không lạm dụng `useEffect` để đồng bộ state có thể tính trực tiếp từ props hoặc state hiện có. State UI cục bộ để ở component gần nơi sử dụng nhất.
- Không thêm `useMemo` hoặc `useCallback` chỉ vì thói quen; chỉ dùng khi có lý do đo được hoặc khi API của thư viện yêu cầu ổn định reference.

## 17. 🎨 Tailwind & Design Token Rules (Quy ước Tailwind)

- Tailwind là nguồn styling chính. Mọi màu, radius, shadow, spacing và breakpoint dùng trong UI phải xuất phát từ token đã thống nhất, không tự tạo màu gần giống.
- Với Tailwind v4, khai báo token dùng chung trong `resources/css/app.css` theo cơ chế theme của Tailwind v4; không tạo thêm một hệ token thứ hai trong component.
- Không dùng arbitrary value như `w-[...]`, `text-[#...]` hoặc `[#...]` cho các giá trị lặp lại. Chỉ dùng khi giá trị thật sự đặc thù và có lý do được ghi nhận.
- Class responsive phải đi theo mobile-first: style mặc định cho mobile, sau đó mở rộng bằng `md:`, `lg:` và `xl:`.
- Các component cùng loại phải có cùng chiều cao control, radius, trạng thái focus, khoảng cách icon và kiểu typography.
- Không trộn nhiều kiểu bo góc, shadow hoặc gradient trong cùng một màn hình. Container/Card dùng `rounded-xl` (12px), Button/Input dùng `rounded-md` (8px), Badge dùng `rounded-full` hoặc `rounded`; ưu tiên border nhẹ hơn shadow mạnh.
- Route helper dùng Ziggy `route()` cho mọi URL nội bộ; không tạo URL bằng nối chuỗi hoặc hard-code path.
- Khi class điều kiện dài, dùng helper đã được dự án chọn như `clsx`/`cn`; không nối chuỗi class thủ công ở nhiều nơi.
- Không dùng màu chỉ để truyền đạt trạng thái. Luôn kết hợp icon, text hoặc pattern để hỗ trợ người dùng khiếm thị màu.

## 18. 🔄 UI State & Data States (Các trạng thái bắt buộc)

- Mỗi page hoặc component gọi dữ liệu phải xác định rõ các trạng thái: loading, success có dữ liệu, success rỗng, validation error, authorization error, server error và network error khi phù hợp.
- Loading dùng Skeleton có cùng hình dạng và kích thước gần với nội dung thật; không dùng spinner toàn màn hình cho các thao tác thông thường.
- Empty state phải giải thích ngắn gọn nguyên nhân và cung cấp hành động tiếp theo, ví dụ `Tải tài liệu`, `Xóa bộ lọc` hoặc `Quay lại danh sách`.
- Mutation phải có trạng thái pending, thành công và thất bại. Trong lúc pending, khóa đúng control liên quan nhưng không khóa cả màn hình nếu không cần thiết.
- Optimistic update chỉ dùng cho thao tác có thể rollback rõ ràng như upvote hoặc bookmark; upload, delete, moderation và thanh toán phải chờ server xác nhận.
- Các bảng, card và form cần giữ layout ổn định khi loading hoặc khi text dài. Dùng `min-w-0`, `truncate`, `line-clamp` và overflow có chủ đích.
- Modal phải quản lý focus, đóng bằng Escape, có nút đóng dễ thấy và không được đặt nội dung quan trọng ngoài vùng đọc trên mobile.

## 19. 📏 Interaction, Accessibility & Responsive QA (Kiểm tra trải nghiệm)

- Target tương tác tối thiểu khoảng 44x44px trên mobile. Không yêu cầu hover để hiểu hoặc hoàn thành một thao tác.
- Focus ring phải luôn nhìn thấy khi dùng bàn phím; không dùng `outline-none` nếu không thay bằng trạng thái focus tương đương.
- Mỗi input phải có label liên kết bằng `htmlFor`, placeholder không được thay thế label, và lỗi phải liên kết bằng `aria-describedby` khi có thể.
- Bảng dữ liệu phải có header, thứ tự tab hợp lý và phiên bản cuộn ngang có chủ đích trên màn hình hẹp.
- Kiểm tra tối thiểu ở các viewport 375px, 768px, 1024px và 1440px trước khi merge. Không để text, modal, bảng hoặc action chính bị tràn/che khuất.
- Kiểm tra cả keyboard navigation, trạng thái loading, empty state, lỗi validation, quyền bị từ chối và dữ liệu dài bất thường.

## 20. 🤝 Team Consistency & Prompt Contract (Quy ước làm việc nhóm và prompt)

- Trước khi tạo màn hình mới, tìm component và pattern gần nhất trong `Components`, `Layouts` và `Pages`; tái sử dụng trước khi tạo biến thể mới.
- Mỗi PR chỉ thêm component hoặc token mới khi component/token hiện có không đáp ứng được yêu cầu. Ghi rõ lý do trong mô tả PR.
- Không tự ý đổi màu thương hiệu, font, breakpoint, radius, spacing scale hoặc quy ước đặt tên trong một task giao diện riêng lẻ.
- Prompt tạo UI phải nêu rõ: route/page, vai trò người dùng, dữ liệu/props, trạng thái cần có, action và quyền, breakpoint, component có thể tái sử dụng, cùng các giới hạn không được vi phạm.
- Prompt mẫu: `Tạo Inertia React page [TênPage] cho route [route], dùng [Layout], nhận props [danh sách props], đọc role từ usePage().props.auth.user.vai_tro, tạo URL nội bộ bằng Ziggy route(), hỗ trợ các trạng thái [danh sách], dùng lại [component], tuân thủ token AGU và responsive mobile-first. Không tạo router riêng, không hard-code URL/business rule, không dùng alert hoặc inline style.`
- Definition of Done cho UI: đúng route và quyền, có loading/empty/error/success state, responsive ở 4 viewport chuẩn, keyboard accessible, không có console error, dùng component/token hiện có và đã được kiểm tra bằng dữ liệu dài.

## 21. 🧾 Frontend Review Checklist (Checklist trước khi merge)

- [ ] Page dùng đúng Inertia layout và điều hướng nội bộ dùng `Link`/Inertia router.
- [ ] Props, validation errors, flash messages và permission được xử lý theo hợp đồng backend.
- [ ] Không có business rule, dữ liệu giả hoặc URL API hard-code trong component.
- [ ] Đã xử lý loading, empty, error, success và pending state phù hợp.
- [ ] Đã tái sử dụng component/token hiện có; không tạo style hoặc pattern trùng lặp.
- [ ] Form có label, error message, focus state, keyboard navigation và chống submit lặp.
- [ ] Đã kiểm tra mobile, tablet, desktop, dữ liệu dài và quyền không đủ.
- [ ] Không có `alert()`, inline style, console error hoặc icon-only control thiếu nhãn truy cập.
