# AGU Academic Hub

Nền tảng tri thức và cộng đồng sinh viên nội bộ của Đại học An Giang (AGU).

## 1. Tech Stack

- **Backend:** Laravel 11, PHP 8.2+, Monolithic Modular.
- **Frontend:** React 18+, Inertia.js v2, Vite.
- **Styling:** Tailwind CSS v4 qua `@theme` trong `resources/css/app.css`.
- **Routing frontend:** Inertia `Link`/`router` kết hợp Ziggy `route()`.
- **Database:** MySQL 8.0 hoặc SQLite cho local development.
- **Queue:** Database Queue mặc định; Redis phù hợp cho production.
- **Icons:** Lucide React.

Các tài liệu đặc tả nằm trong thư mục `.md/`:

- [Yêu cầu dự án](.md/TaiLieuYeuCau.md)
- [Thiết kế hệ thống](.md/TaiLieuThietKe.md)
- [Tài liệu kỹ thuật](.md/TaiLieuKyThuat.md)
- [Design Guidelines](.md/DESIGN_GUIDELINES.md)

## 2. Kiến trúc tổng thể

Ứng dụng dùng Laravel monolith, nhưng chia ranh giới theo nghiệp vụ. Laravel chịu trách nhiệm routing, authentication, authorization, validation, query, business rules và render Inertia response. React chỉ chịu trách nhiệm giao diện và tương tác.

```text
Browser
	-> Laravel Web Routes
	-> Module Presentation (Controller / Request / Resource)
	-> Module Application (Action / Query / DTO)
	-> Module Domain (Model / Policy / Contract / Rule)
	-> Module Infrastructure (Eloquent / Queue / Storage / External Service)
	-> MySQL / Redis / Private Storage
```

### Nguyên tắc module

1. Mỗi module sở hữu một business capability và route của nó.
2. Business rule không đặt trong React, Blade hoặc controller quá lớn.
3. Controller chỉ điều phối request, gọi Application action và trả Inertia/Resource response.
4. Domain không phụ thuộc vào Inertia hoặc component React.
5. Giao tiếp giữa module thông qua contract, DTO, event hoặc application service.
6. Policy/Gate và backend authorization luôn là nguồn quyết định quyền cuối cùng; UI role chỉ là lớp hiển thị.

## 3. Cây cấu trúc thư mục

```text
AGU-Academic-Hub/
├── app/                                  # Mã nguồn PHP của ứng dụng
│   ├── Http/                             # Lớp HTTP dùng chung ngoài phạm vi module
│   │   ├── Controllers/                  # Chỉ đặt controller cross-module hoặc legacy
│   │   └── Middleware/                   # Xử lý request trước/sau controller
│   │       └── HandleInertiaRequests.php # Chia sẻ auth, flash và props chung cho React
│   ├── Models/                           # Model dùng chung hoặc model legacy cần giữ tương thích
│   ├── Modules/                          # Các bounded context nghiệp vụ của monolith
│   │   ├── Identity/                     # Đăng ký, đăng nhập, profile, role và KYC
│   │   │   ├── Application/              # Use case điều phối nghiệp vụ Identity
│   │   │   │   └── Actions/              # Hành động đơn: RegisterUser, SubmitKyc...
│   │   │   ├── Domain/                   # Luật nghiệp vụ thuần, không phụ thuộc Inertia
│   │   │   │   ├── Contracts/            # Interface/repository contract của module
│   │   │   │   ├── Enums/                # Role, user status, KYC status
│   │   │   │   ├── Models/               # User profile, verification và entity liên quan
│   │   │   │   ├── Policies/             # Policy kiểm tra quyền trong module
│   │   │   │   └── Rules/                # Rule domain như email AGU, điều kiện KYC
│   │   │   ├── Infrastructure/          # Adapter kết nối framework và dịch vụ ngoài
│   │   │   │   ├── Persistence/          # Eloquent repository, query và mapping database
│   │   │   │   └── Services/             # Mail, storage hoặc identity provider adapter
│   │   │   ├── Presentation/             # Điểm vào HTTP của module
│   │   │   │   └── Http/
│   │   │   │       ├── Controllers/      # Nhận request, gọi Action, trả Inertia/Resource
│   │   │   │       ├── Requests/         # Validation và authorization của input
│   │   │   │       └── Resources/        # Chuẩn hóa dữ liệu trả về frontend/API
│   │   │   ├── routes.php                # Route auth/profile/KYC của Identity
│   │   │   └── IdentityServiceProvider.php # Đăng ký binding, route và module bootstrapping
│   │   ├── AcademicCatalog/              # Khoa, ngành, môn học và tìm kiếm học phần
│   │   │   ├── Application/Actions/      # CreateCourse, UpdateCourse, SearchCourses...
│   │   │   ├── Domain/                   # Course/faculty entity, enum, policy, rule
│   │   │   ├── Infrastructure/           # Eloquent query và persistence catalog
│   │   │   ├── Presentation/Http/        # Controller, Request, Resource của catalog
│   │   │   ├── routes.php                # Route faculties và courses
│   │   │   └── AcademicCatalogServiceProvider.php
│   │   ├── Documents/                    # Upload, preview, version và deduplication
│   │   │   ├── Application/               # Use case luồng tài liệu
│   │   │   │   ├── Actions/               # UploadDocument, CreateVersion, DeleteDocument
│   │   │   │   ├── DTOs/                  # Dữ liệu trung gian bất biến giữa các tầng
│   │   │   │   └── Queries/               # Query tìm kiếm, filter, pagination tài liệu
│   │   │   ├── Domain/                    # Document entity, status, ownership rule
│   │   │   ├── Infrastructure/            # File storage, queue và persistence
│   │   │   │   ├── Persistence/            # Document repository và query database
│   │   │   │   ├── Services/               # Hashing, text extraction, similarity adapter
│   │   │   │   └── Jobs/                   # Queue job xử lý file ngoài HTTP request
│   │   │   ├── Presentation/Http/         # Endpoint upload, preview, download
│   │   │   ├── routes.php                 # Route lifecycle tài liệu
│   │   │   └── DocumentsServiceProvider.php
│   │   ├── Reviews/                       # Review môn học, ẩn danh và bad-word filter
│   │   │   ├── Application/               # SubmitReview, UpdateReview, MarkHelpful
│   │   │   ├── Domain/                    # Review entity, rating rule và policy
│   │   │   ├── Infrastructure/            # Lưu review và bộ lọc từ khóa
│   │   │   ├── Presentation/Http/         # Form Request, Controller, Resource
│   │   │   ├── routes.php
│   │   │   └── ReviewsServiceProvider.php
│   │   ├── Bounties/                      # Wishlist, bounty points và giải ngân
│   │   │   ├── Application/               # CreateRequest, FulfillRequest, ConfirmBounty
│   │   │   ├── Domain/                    # Wallet, bounty status và invariant điểm
│   │   │   ├── Infrastructure/            # Transaction, notification và persistence
│   │   │   ├── Presentation/Http/         # Endpoint request/fulfill/confirm
│   │   │   ├── routes.php
│   │   │   └── BountiesServiceProvider.php
│   │   ├── Moderation/                    # Report queue, ẩn tài liệu và community ownership
│   │   │   ├── Application/               # ResolveReport, RequestRemoval, HideDocument
│   │   │   ├── Domain/                    # Report status, threshold và moderation policy
│   │   │   ├── Infrastructure/            # Queue báo cáo, audit log và persistence
│   │   │   ├── Presentation/Http/         # Moderator/Admin controller và resource
│   │   │   ├── routes.php
│   │   │   └── ModerationServiceProvider.php
│   │   └── Administration/               # Dashboard, KYC queue, user và restore
│   │       ├── Application/               # Admin action và thống kê hệ thống
│   │       ├── Domain/                    # Admin policy, status và rule quản trị
│   │       ├── Infrastructure/            # Query thống kê, audit và storage adapter
│   │       ├── Presentation/Http/         # Admin controller, request, resource
│   │       ├── routes.php
│   │       └── AdministrationServiceProvider.php
│   └── Shared/                            # Thành phần dùng chung, không thuộc module riêng
│       ├── Support/                       # Base Module, helper, formatter, shared value
│       ├── Exceptions/                    # Exception dùng chung và handler contract
│       └── Contracts/                     # Contract cross-module, event hoặc service chung
├── bootstrap/                             # Bootstrap vòng đời Laravel
│   ├── app.php                            # Khai báo routing, middleware, exception pipeline
│   └── providers.php                      # Danh sách service provider của framework
├── config/                                # Cấu hình app, auth, database, queue, filesystems
├── database/                              # Migration, factory và seed dữ liệu
│   ├── factories/                         # Factory tạo dữ liệu test/dev
│   ├── migrations/                        # Lịch sử schema database, không sửa migration đã chạy
│   └── seeders/                           # Dữ liệu nền như role, faculty, course mẫu
├── public/                                # Document root web server, không đặt logic nghiệp vụ
│   ├── build/                             # Asset production do Vite tạo, không sửa tay
│   └── index.php                          # Entry point của mọi HTTP request Laravel
├── resources/                             # View source và frontend source
│   ├── css/app.css                        # Tailwind v4 import, @source và @theme token
│   ├── js/                                # React/Inertia application
│   │   ├── Components/                    # Component tái sử dụng
│   │   │   ├── ui/                        # Button, Input, Modal, Badge, Skeleton, Tooltip
│   │   │   └── domain/                    # DocumentCard, CourseFilters, ReviewForm...
│   │   ├── Layouts/                       # Khung theo vai trò: Guest, Student, Admin
│   │   ├── Pages/                         # Mỗi file là một Inertia page tương ứng route
│   │   │   ├── Courses/                   # Index, Show và page môn học
│   │   │   ├── Documents/                 # Index, Show, Upload, Preview
│   │   │   ├── Reviews/                   # Review list và review form
│   │   │   └── Admin/                     # Dashboard, KYC queue, report queue
│   │   ├── hooks/                         # Custom hook dùng chung, tên useSomething
│   │   ├── lib/                           # Helper thuần, constants, formatter, mapping
│   │   ├── app.js                         # Khởi tạo Inertia app và resolve Pages
│   │   └── bootstrap.js                   # CSRF, axios hoặc frontend bootstrap mặc định
│   └── views/app.blade.php                # Root HTML shell chứa @inertia và @vite
├── routes/                                # Route cấp application
│   ├── web.php                            # Home route và loader routes của module
│   └── console.php                        # Artisan command tùy chỉnh
├── storage/                               # Log, cache, session và file upload
│   ├── app/private/                       # KYC/tài liệu nhạy cảm, không public trực tiếp
│   ├── app/public/                        # File được phép expose qua storage link
│   ├── framework/                         # Cache, session, compiled view của Laravel
│   └── logs/                              # Log ứng dụng
├── tests/                                 # Automated tests
│   ├── Feature/                           # Test route, middleware, policy và database flow
│   └── Unit/                              # Test domain rule, action và helper độc lập
├── .env.example                           # Mẫu biến môi trường, không chứa secret thật
├── artisan                                # CLI entry point của Laravel
├── composer.json                          # PHP dependency, PSR-4 autoload và scripts
├── package.json                           # Frontend dependency và npm scripts
├── package-lock.json                      # Lock phiên bản npm dependency
└── vite.config.js                         # Vite input, React plugin, Laravel plugin
```

### Quy tắc đọc và mở rộng cây thư mục

- Dấu `#` trong cây là chú thích trách nhiệm, không phải tên file hoặc folder bổ sung.
- Các module có cùng tầng `Application`, `Domain`, `Infrastructure`, `Presentation`; chỉ `Documents` mở rộng thêm `DTOs`, `Queries` và `Jobs` vì có luồng upload/deduplication phức tạp.
- File nghiệp vụ phải nằm trong module sở hữu nó. Ví dụ logic khóa xóa tài liệu đặt trong `Documents` hoặc `Moderation`, không đặt trong `resources/js`.
- `Presentation` được phép phụ thuộc `Application`; `Application` được phép dùng `Domain`; `Infrastructure` triển khai contract của `Domain`.
- `Domain` không được import Inertia, React, Blade, Request hoặc Eloquent-specific query nếu có thể tránh được.
- `resources/js/Pages` chỉ nhận props từ backend và điều phối UI; không đặt query database, policy hoặc business rule tại đây.
- Folder rỗng trong cây là convention để chuẩn bị cho feature tiếp theo. Không cần tạo file giả chỉ để giữ folder trong Git; khi có file đầu tiên, dùng đúng namespace của module.

## 4. Lệnh tạo cấu trúc

### 4.1. Bash / Git Bash

Chạy từ thư mục gốc repository:

```bash
mkdir -p app/Shared/{Support,Exceptions,Contracts}

for module in Identity AcademicCatalog Documents Reviews Bounties Moderation Administration; do
	mkdir -p "app/Modules/$module/Application/Actions"
	mkdir -p "app/Modules/$module/Domain/Contracts"
	mkdir -p "app/Modules/$module/Domain/Enums"
	mkdir -p "app/Modules/$module/Domain/Models"
	mkdir -p "app/Modules/$module/Domain/Policies"
	mkdir -p "app/Modules/$module/Domain/Rules"
	mkdir -p "app/Modules/$module/Infrastructure/Persistence"
	mkdir -p "app/Modules/$module/Infrastructure/Services"
	mkdir -p "app/Modules/$module/Presentation/Http/Controllers"
	mkdir -p "app/Modules/$module/Presentation/Http/Requests"
	mkdir -p "app/Modules/$module/Presentation/Http/Resources"
	touch "app/Modules/$module/routes.php"
	touch "app/Modules/$module/${module}ServiceProvider.php"
done

mkdir -p resources/js/{Components/ui,Components/domain,Layouts,Pages,hooks,lib}
mkdir -p resources/js/Pages/{Courses,Documents,Reviews,Admin}
mkdir -p resources/views database/{factories,migrations,seeders} tests/{Feature,Unit}
```

### 4.2. PowerShell trên Windows

```powershell
$directories = @(
	'app/Shared/Support', 'app/Shared/Exceptions', 'app/Shared/Contracts',
	'resources/js/Components/ui', 'resources/js/Components/domain',
	'resources/js/Layouts', 'resources/js/Pages/Courses',
	'resources/js/Pages/Documents', 'resources/js/Pages/Reviews',
	'resources/js/Pages/Admin', 'resources/js/hooks', 'resources/js/lib',
	'resources/views', 'tests/Feature', 'tests/Unit'
)

$modules = @('Identity', 'AcademicCatalog', 'Documents', 'Reviews', 'Bounties', 'Moderation', 'Administration')
foreach ($module in $modules) {
	$directories += @(
		"app/Modules/$module/Application/Actions",
		"app/Modules/$module/Domain/Contracts",
		"app/Modules/$module/Domain/Enums",
		"app/Modules/$module/Domain/Models",
		"app/Modules/$module/Domain/Policies",
		"app/Modules/$module/Domain/Rules",
		"app/Modules/$module/Infrastructure/Persistence",
		"app/Modules/$module/Infrastructure/Services",
		"app/Modules/$module/Presentation/Http/Controllers",
		"app/Modules/$module/Presentation/Http/Requests",
		"app/Modules/$module/Presentation/Http/Resources"
	)
}

$directories | ForEach-Object { New-Item -ItemType Directory -Force $_ | Out-Null }
```

### 4.3. Lệnh Laravel tạo file nghiệp vụ

```bash
php artisan make:model User -m
php artisan make:request App/Modules/AcademicCatalog/Presentation/Http/Requests/CourseIndexRequest
php artisan make:resource App/Modules/AcademicCatalog/Presentation/Http/Resources/CourseResource
php artisan make:controller App/Modules/AcademicCatalog/Presentation/Http/Controllers/CourseController
php artisan make:policy CoursePolicy --model=Course
php artisan make:job App/Modules/Documents/Infrastructure/Jobs/ExtractDocumentText
php artisan make:request App/Modules/Documents/Presentation/Http/Requests/UploadDocumentRequest
php artisan make:resource App/Modules/Documents/Presentation/Http/Resources/DocumentResource
php artisan make:test --Feature CourseSearchTest
php artisan make:test --Unit DocumentDeduplicationTest
```

Laravel không có generator mặc định cho mọi thư mục Application/Domain/Infrastructure. Với các file đó, tạo đúng path và namespace thủ công theo mẫu:

```bash
mkdir -p app/Modules/Documents/Application/Actions
touch app/Modules/Documents/Application/Actions/UploadDocument.php
touch app/Modules/Documents/Domain/Contracts/DocumentRepository.php
touch app/Modules/Documents/Domain/Enums/DocumentStatus.php
touch app/Modules/Documents/Infrastructure/Persistence/EloquentDocumentRepository.php
```

## 5. Cài đặt local

Yêu cầu PHP 8.2+, Composer, Node.js 20+ và npm. Trên Windows, kiểm tra PATH trước:

```powershell
php --version
composer --version
node --version
npm --version
```

Cài dependency và khởi tạo ứng dụng:

```bash
composer install
cp .env.example .env                 # PowerShell: Copy-Item .env.example .env
php artisan key:generate
npm install
php artisan migrate
npm run build
```

Thiết lập database trong `.env`:

```dotenv
APP_NAME="AGU Academic Hub"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agu_academic_hub
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```

Nếu chỉ cần chạy local nhanh, dùng SQLite:

```bash
touch database/database.sqlite
php artisan migrate
```

## 6. Chạy ứng dụng

Một terminal:

```bash
php artisan serve
```

Terminal thứ hai:

```bash
npm run dev
```

Nếu dùng queue/database:

```bash
php artisan queue:listen --tries=1
```

Hoặc dùng script tổng hợp:

```bash
composer run dev
```

Các URL scaffold hiện có:

- `/` — Inertia Home page.
- `/courses` — AcademicCatalog index scaffold.
- `/documents` — Documents index scaffold.

## 7. Frontend conventions

- Page đặt tại `resources/js/Pages/<Feature>/<Page>.jsx`.
- Layout dùng chung đặt tại `resources/js/Layouts`.
- Primitive UI đặt tại `resources/js/Components/ui`; component nghiệp vụ đặt tại `resources/js/Components/domain`.
- Dùng `Link` hoặc `router.visit(route(...))` cho URL nội bộ.
- Không dùng SPA router riêng, không dùng `fetch`/Axios cho mutation Inertia nếu không có lý do kỹ thuật rõ ràng.
- Role đọc từ `usePage().props.auth.user.vai_tro`; backend Policy/Gate vẫn quyết định quyền.
- Mọi màu và radius dùng token Tailwind v4 trong `resources/css/app.css`.

## 8. Backend conventions

- `Presentation`: Controller, Form Request, Resource và Inertia response.
- `Application`: Use case, Action, Query, DTO và transaction orchestration.
- `Domain`: Entity/model, enum, policy, rule và contract của nghiệp vụ.
- `Infrastructure`: Eloquent repository, storage, queue job và adapter bên ngoài.
- Validation đặt trong Form Request; authorization đặt trong Policy/Gate.
- Upload, deduplication, trích xuất text và gửi mail chạy qua Queue; không chặn HTTP request dài.
- File KYC nằm trong private storage và chỉ mở qua route có authorization.
- Dùng soft delete cho tài liệu; không xóa vật lý nếu chưa có quy trình retention phù hợp.

## 9. Kiểm tra trước khi merge

```bash
php artisan test
php artisan route:list
php artisan config:clear
php artisan pint --test
npm run build
```

Kiểm tra tối thiểu UI ở viewport 375px, 768px, 1024px và 1440px; kiểm tra keyboard navigation, loading/empty/error state, role bị từ chối và dữ liệu dài.

## 10. Quy trình thêm một module mới

1. Tạo thư mục `app/Modules/<ModuleName>` theo layout chuẩn.
2. Tạo `routes.php` và `ModuleNameServiceProvider.php`.
3. Đăng ký provider trong `bootstrap/providers.php` nếu module cần boot lifecycle; route loader hiện tại có thể load trực tiếp `routes.php`.
4. Tạo migration/model, sau đó tạo Domain contract và Application action.
5. Tạo Form Request, Policy, Resource và Controller ở Presentation.
6. Tạo Inertia page và test Feature/Unit tương ứng.
7. Thêm module vào README và chạy toàn bộ command trong mục kiểm tra trước khi merge.

## 11. Trạng thái scaffold

Đã có nền tảng Laravel 11, Inertia middleware/root view, React entry point, Tailwind v4 theme tokens, Ziggy hook, bảy module boundary, hai controller/page mẫu và README này. Các nghiệp vụ KYC, upload, deduplication, review, bounty, moderation và admin sẽ được triển khai trong module tương ứng theo đặc tả trong `.md/`.
