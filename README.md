# AGU Academic Hub

Nền tảng tài liệu học thuật và cộng đồng sinh viên nội bộ của Đại học An Giang
(AGU), xây dựng bằng Laravel 11, Inertia.js và React.

## 1. Công nghệ

- **Backend:** PHP 8.2+, Laravel 11.
- **Frontend:** React 18+, Inertia.js 2, Vite.
- **CSS:** Tailwind CSS 4.
- **Routing frontend:** Ziggy kết hợp Inertia `Link`/`router`.
- **Database:** MySQL 8+ trên production; SQLite in-memory cho test.
- **Queue:** Database Queue mặc định; có thể dùng Redis trên production.
- **Storage:** Laravel Filesystem; tài liệu và minh chứng KYC được bảo vệ
  theo từng luồng nghiệp vụ.
- **Icons:** Lucide React.

## 2. Kiến trúc hiện tại

Backend hiện sử dụng **Modern Monolith theo mô hình MVC + Service Layer**, được
phân nhóm theo miền nghiệp vụ:

```text
HTTP Request
    │
    ▼
Route + Middleware
    │
    ▼
Controller mỏng
    │  nhận Form Request, gọi Service, trả Inertia/JSON/Redirect/Stream
    ▼
Service Layer theo miền
    │  nghiệp vụ, transaction, cache, storage, state transition
    ▼
Eloquent Models + Events/Notifications/Jobs
    │
    ▼
Database / Cache / Private Storage
```

### Quy tắc tổ chức

1. **Controller chỉ điều phối:** Không chứa business logic phức tạp, transaction,
   truy vấn thống kê hoặc xử lý file.
2. **Form Request chứa validation:** Rules, messages và authorization của request
   nằm trong `app/Http/Requests/<Domain>/`.
3. **Service chứa nghiệp vụ:** Các luồng nhiều bước, giao dịch nguyên tử,
   pessimistic locking, caching và storage được thực hiện trong
   `app/Services/<Domain>/`.
4. **Model chứa persistence behavior:** Quan hệ, casts, scopes, hooks và
   soft-delete được đặt trong `app/Models/`.
5. **Không thay đổi frontend contract:** Tên Inertia page, route name và cấu
   trúc props phải tương thích với `resources/js/`.
6. **Tương thích database driver:** Các truy vấn thống kê phải hỗ trợ cả
   MySQL và SQLite test.

> Một số lớp nghiệp vụ vẫn đang nằm trong các thư mục con `Actions/`, `Queries/`,
> `Repositories/` hoặc `Storage/` bên dưới Service Layer. Đây là các thành phần
> chuyển tiếp tương thích ngược; chúng không còn thuộc kiến trúc `app/Modules`
> cũ. Các Service nghiệp vụ mới được đặt trực tiếp trong domain khi đã chuẩn hóa.

## 3. Cây cấu trúc thư mục hiện tại

```text
AGU-Academic-Hub/
├── app/
│   ├── Console/
│   │   └── Commands/                 # Artisan Commands và tác vụ định kỳ.
│   │
│   ├── Enums/                        # Backed Enums dùng chung:
│   │                                  # vai trò, trạng thái, audit, notification,
│   │                                  # giao dịch điểm và state machine.
│   │
│   ├── Events/                       # Domain events sau các nghiệp vụ quan trọng.
│   ├── Listeners/                    # Listener cập nhật notification/side effects.
│   ├── Notifications/                # Laravel Notifications cho user và staff.
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                # Dashboard, user, raw roster, storage,
│   │   │   │                          # audit log và system settings.
│   │   │   ├── Auth/                 # Login, registration, password, profile,
│   │   │   │                          # first-time password và KYC.
│   │   │   ├── Community/            # Catalog, review, bounty, wallet,
│   │   │   │                          # notification và correction workflow.
│   │   │   ├── Documents/            # Upload, preview, download, version,
│   │   │   │                          # upvote và removal request.
│   │   │   ├── Moderation/           # Document/review report, moderation queue,
│   │   │   │                          # removal request và trash.
│   │   │   ├── DashboardController.php
│   │   │   └── NotificationController.php
│   │   │
│   │   ├── Middleware/               # CheckRole, password state, Inertia props,
│   │   │                              # signed URL và middleware HTTP khác.
│   │   ├── Requests/
│   │   │   ├── *.php                 # Form Requests dùng chung/backward-compatible.
│   │   │   ├── Admin/                # Validation quản trị và raw students.
│   │   │   ├── Auth/                 # Login, password, profile và KYC.
│   │   │   ├── Community/            # Review, bounty, wallet và correction.
│   │   │   ├── Documents/            # Upload, version, duplicate và removal.
│   │   │   └── Moderation/           # Duyệt tài liệu và xử lý reports.
│   │   └── Resources/                # API/Inertia resource transformations.
│   │
│   ├── Models/                       # Eloquent models, relationships, casts,
│   │                                  # scopes, hooks và soft deletes.
│   │
│   ├── Services/
│   │   ├── Admin/                    # Dashboard, user management, audit,
│   │   │                              # raw roster, settings và storage.
│   │   │   ├── Actions/              # Write operations tương thích chuyển tiếp.
│   │   │   ├── Queries/               # Read models và thống kê quản trị.
│   │   │   ├── Repositories/          # Persistence queries dùng chung domain.
│   │   │   └── Storage/               # Physical storage scanners/adapters.
│   │   ├── Auth/                     # Identity, authentication, password và KYC.
│   │   │   ├── Actions/
│   │   │   ├── Queries/
│   │   │   ├── Repositories/
│   │   │   ├── Storage/
│   │   │   └── ValueObjects/
│   │   ├── Community/                # Catalog, reviews, bounty, wallet và
│   │   │                              # notification orchestration.
│   │   │   ├── Actions/
│   │   │   ├── Queries/
│   │   │   └── Repositories/
│   │   ├── Documents/                # Document upload, file access, versions,
│   │   │                              # deduplication và author ownership.
│   │   │   ├── Actions/
│   │   │   ├── Queries/
│   │   │   ├── Repositories/
│   │   │   └── Storage/
│   │   ├── Moderation/               # Report thresholds, review decisions,
│   │   │                              # trash, removal và penalties.
│   │   │   ├── Actions/
│   │   │   ├── Queries/
│   │   │   └── Repositories/
│   │   └── Shared/                   # Service lõi dùng chung giữa các domain.
│   │
│   └── Providers/
│       ├── AppServiceProvider.php    # Bindings và event listener registration.
│       └── ...                        # Laravel framework providers.
│
├── bootstrap/
│   ├── app.php                        # Middleware, exception và route bootstrap.
│   └── providers.php                  # Danh sách provider của ứng dụng.
│
├── config/                            # Cấu hình Laravel, moderation, storage,
│                                      # documents, queue, database...
├── database/
│   ├── factories/                     # Model factories cho test/seed.
│   ├── migrations/                    # Schema database; không đổi tên nghiệp vụ.
│   └── seeders/                       # Dữ liệu khởi tạo.
│
├── resources/
│   ├── css/                           # Tailwind theme và global styles.
│   └── js/
│       ├── Components/                # UI components dùng lại.
│       ├── Layouts/                   # Guest, student, alumni và admin layouts.
│       └── Pages/                     # Inertia React pages; giữ nguyên page names.
│
├── routes/
│   ├── web.php                        # Trang chủ, auth, profile và route chung.
│   ├── documents.php                  # Documents và moderation document routes.
│   ├── community.php                  # Community, bounty, KYC và admin routes.
│   └── console.php                    # Artisan console routes.
│
├── storage/                           # Runtime cache, logs, queue và files.
├── tests/
│   ├── Feature/                       # Business workflow, authorization,
│   │                                  # notification và integration tests.
│   └── Unit/                          # Unit tests cho component/service nhỏ.
│
├── public/                            # Public entry point và built assets.
├── docs/                              # Đặc tả nghiệp vụ và tài liệu tích hợp.
├── composer.json                      # PHP dependencies và Composer scripts.
├── package.json                       # Frontend dependencies và Vite scripts.
└── vite.config.js                     # Cấu hình build frontend.
```

Các thư mục `app/Modules/` và `app/Shared/` của kiến trúc cũ **không còn được
ứng dụng sử dụng**. Mã nguồn hiện tại được autoload trực tiếp dưới namespace
`App\` theo chuẩn Laravel.

## 4. Routes và middleware

Ứng dụng hiện có **156 routes**. Các route được tách theo chức năng nhưng vẫn
được nạp thống nhất từ `bootstrap/app.php`:

- `routes/web.php`: public, authentication, profile, dashboard và route chung.
- `routes/documents.php`: upload, preview, download, moderation document,
  reports, removal requests và trash.
- `routes/community.php`: academic catalog, reviews, bounty, wallet,
  notifications, KYC và admin workflows.
- `routes/console.php`: command scheduling/console routes.

Không thêm nhóm `web` trùng lặp khi compose các route file. Middleware và route
names là một phần của frontend/backend contract, không được thay đổi tùy tiện.

## 5. Yêu cầu môi trường, thư viện và cách chạy hệ thống

### 5.1. Yêu cầu môi trường (Prerequisites)

- **PHP:** `8.2+` (bật các extension chuẩn của Laravel: `pdo_mysql` / `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`).
- **Composer:** `2.x`
- **Node.js & npm:** Node.js `18+` (khuyên dùng `20+`) và `npm`.
- **Cơ sở dữ liệu:** MySQL `8+` (hoặc SQLite cho môi trường local/test nhanh).
- **Mô hình AI/ML ngoài:** Hệ thống **không yêu cầu** cài đặt thêm mô hình AI/ML, Python hay OCR service bên ngoài; toàn bộ nghiệp vụ chạy thuần trên stack Laravel + React.

### 5.2. Các thư viện chính của hệ thống

- **Backend (`composer.json`):**
  - `laravel/framework` (`^11.0`), `inertiajs/inertia-laravel` (`^2.0`), `tightenco/ziggy` (`^2.0`), `laravel/sanctum` (`^4.0`), `laravel/tinker` (`^2.10.1`).
  - Dev/Test: `phpunit/phpunit` (`^11.5.50`), `laravel/breeze`, `laravel/pail`, `laravel/pint`, `mockery/mockery`, `fakerphp/faker`.
- **Frontend (`package.json`):**
  - `react` (`^18.3.1`), `react-dom` (`^18.3.1`), `@inertiajs/react` (`^2.0`), `@headlessui/react` (`^2.2.10`), `lucide-react` (`^0.468.0`), `canvas-confetti` (`^1.9.4`).
  - Build/Style: `vite` (`^7.0.7`), `@vitejs/plugin-react`, `tailwindcss` (`^4.0.0`), `@tailwindcss/vite`, `axios`, `concurrently`.

### 5.3. Tải thư viện và thiết lập ban đầu

1. **Tải thư viện PHP và JavaScript:**

```bash
composer install
npm install
```

2. **Tạo file cấu hình môi trường `.env` và khóa ứng dụng:**

```bash
# Windows (CMD/PowerShell)
copy .env.example .env

# macOS / Linux
cp .env.example .env

php artisan key:generate
```

3. **Cấu hình Database trong `.env`:**
   - Mặc định `.env.example` dùng `DB_CONNECTION=sqlite` (tạo sẵn file `database/database.sqlite` nếu chưa có) và `QUEUE_CONNECTION=database`.
   - Nếu dùng MySQL, cập nhật `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` tương ứng.

4. **Khởi tạo bảng, dữ liệu mẫu và liên kết storage:**

```bash
php artisan migrate --seed
php artisan storage:link
```

> Lưu ý: `composer run setup` đã cấu hình sẵn chuỗi cài đặt nhanh (`composer install`, tạo `.env`, `key:generate`, `migrate --force`, `npm install`, `npm run build`). Nếu muốn nạp cả dữ liệu mẫu (Khoa, Chuyên ngành, Môn học, Tài khoản, Tài liệu, Bounty, KYC...), hãy chạy thêm `php artisan db:seed`.

### 5.4. Chạy toàn bộ hệ thống

#### Cách 1: Chạy gộp bằng 1 lệnh (Khuyên dùng cho local)

Lệnh sau (khai báo sẵn trong `composer.json`) sẽ khởi chạy đồng thời **Web Server**, **Queue Worker**, **Log Viewer (Pail)** và **Vite Dev Server**:

```bash
composer run dev
```

#### Cách 2: Chạy tách từng terminal

1. **Web Server (Laravel):**

```bash
php artisan serve
```

2. **Frontend Dev Server (Vite):**

```bash
npm run dev
```

*(Hoặc build bản production bằng `npm run build` nếu không chạy Vite dev server).*

3. **Queue Worker (xử lý job & thông báo nền):**

```bash
php artisan queue:listen --tries=1 --timeout=0
```

4. **Scheduler (tùy chọn, cho các tác vụ định kỳ trong `routes/console.php` như `documents:purge-trash`, `documents:notify-expiring-trash`, `bounty:auto-refund`):**

```bash
php artisan schedule:work
```

## 6. Kiểm thử và kiểm tra

Chạy toàn bộ PHPUnit/Pest-compatible Laravel test suite:

```bash
php artisan test
```

Các kiểm tra hữu ích sau khi thay đổi backend:

```bash
php artisan route:list
composer dump-autoload
php artisan config:clear
npm run build
```

Trạng thái kiểm thử gần nhất của repository:

```text
308 tests passed
2332 assertions passed
```

## 7. Các invariant nghiệp vụ quan trọng

- Không thay đổi URL, HTTP method, route name, middleware hoặc Inertia props.
- Tài liệu được chống trùng theo SHA-256 trong phạm vi môn học.
- Tài liệu chưa duyệt chỉ được truy cập bởi actor hợp lệ.
- KYC dùng private storage và signed URL có thời hạn.
- Bounty dùng escrow, `lockForUpdate()`, state guard và idempotency key.
- Từ chối giải ngân hoàn điểm ký quỹ; từ chối tài liệu chưa duyệt trong luồng
  moderation giữ escrow để Bounty tiếp tục nhận đóng góp khác.
- Báo cáo vi phạm, progressive penalty, audit log immutable và trash retention
  phải được bảo toàn.
- Thống kê phải chạy được trên cả MySQL và SQLite in-memory.

Đặc tả nghiệp vụ chi tiết nằm tại
[`docs/integration/DAC_TA_TOAN_DIEN_TINH_NANG_PHAN_QUYEN_VA_LUONG_NGHIEP_VU.md`](docs/integration/DAC_TA_TOAN_DIEN_TINH_NANG_PHAN_QUYEN_VA_LUONG_NGHIEP_VU.md).
