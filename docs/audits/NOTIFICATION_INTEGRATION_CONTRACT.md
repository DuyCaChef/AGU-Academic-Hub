# HỢP ĐỒNG TÍCH HỢP HỆ THỐNG THÔNG BÁO (NOTIFICATION INTEGRATION CONTRACT)
## DỰ ÁN: AGU ACADEMIC HUB — CENTRALIZED NOTIFICATION MODULE
### DÀNH CHO CÁC NHÓM PHÁT TRIỂN MODULE: BOUNTIES, DOCUMENTS, MODERATION, REVIEWS, KYC, WALLET

---

## 1. NGUYÊN TẮC BẮT BUỘC (CARDINAL RULES)

Để bảo đảm tính toàn vẹn dữ liệu, hiệu năng cao và phân tách trách nhiệm (Separation of Concerns), tất cả các kỹ sư phần mềm khi phát triển hoặc bảo trì các module nghiệp vụ phải tuân thủ nghiêm ngặt 5 nguyên tắc sau:

> [!IMPORTANT]
> **1. KHÔNG trực tiếp thao tác bảng `notifications` hoặc model `ThongBao`:**
> Tuyệt đối không gọi `DB::table('notifications')->insert(...)` hoặc tạo mới model thông báo trong các Action/Service nghiệp vụ. Mọi tương tác phải đi qua việc phát Domain Event hoặc qua `NotificationDispatcherInterface`.

> [!WARNING]
> **2. CHỈ phát sự kiện SAU KHI Transaction hoàn tất (After Commit):**
> Tuyệt đối không bắn Event bên trong khối `DB::transaction(...)` trước khi lệnh commit diễn ra. Nếu giao dịch bị rollback vì lỗi database, thông báo "ảo" (phantom notification) sẽ bị gửi đi gây hiểu lầm cho người dùng. Khuyến nghị: Bắn event ngay sau khi khối `DB::transaction()` kết thúc hoặc dùng callback `DB::afterCommit(fn() => event(...))`.

> [!CAUTION]
> **3. KHÔNG đưa dữ liệu nhạy cảm vào Payload (No PII / Secrets):**
> Tuyệt đối không lưu mật khẩu, mã OTP, token truy cập, số CMND/CCCD, hoặc dữ liệu định danh nhạy cảm vào trường `message` hoặc `data` của thông báo.

> [!NOTE]
> **4. Thông báo KHÔNG lưu trạng thái nghiệp vụ (Stateless Pointers):**
> Thông báo chỉ là con trỏ điều hướng mang theo `target_url`. Khi người dùng click vào thông báo, backend của module đích vẫn phải kiểm tra thẩm quyền (Authorization) và trạng thái thực tế của tài nguyên.

> [!TIP]
> **5. Cung cấp `idempotencyKey` khi gọi Dispatcher thủ công:**
> Khi gọi trực tiếp `NotificationDispatcherInterface::send()`, luôn cung cấp `idempotencyKey` duy nhất (ví dụ ghép từ tên tác vụ, entity ID và timestamp ngày) để đảm bảo không bị nhân bản thông báo khi retry.

---

## 2. DANH MỤC EVENT CONTRACT HIỆN CÓ

Tất cả các event được đặt tại namespace: `App\Modules\Notifications\Domain\Events\`

### 2.1. Module Wishlist / Bounties
- **`DocumentContributedToBountyEvent`**
  ```php
  namespace App\Modules\Notifications\Domain\Events;

  class DocumentContributedToBountyEvent
  {
      public function __construct(
          public readonly int $yeuCauId,
          public readonly int $taiLieuId,
          public readonly int $nguoiDongGopId
      ) {}
  }
  ```
- **`BountyFulfillmentConfirmedEvent`**
  ```php
  namespace App\Modules\Notifications\Domain\Events;

  class BountyFulfillmentConfirmedEvent
  {
      public function __construct(
          public readonly int $yeuCauId,
          public readonly int $taiLieuId,
          public readonly int $nguoiDongGopId,
          public readonly int $diemThuong
      ) {}
  }
  ```
- **`BountyCancelledEvent`**
  ```php
  namespace App\Modules\Notifications\Domain\Events;

  class BountyCancelledEvent
  {
      public function __construct(
          public readonly int $yeuCauId,
          public readonly int $nguoiYeuCauId,
          public readonly int $diemHoanTra
      ) {}
  }
  ```

### 2.2. Module Tài liệu & Kiểm duyệt (Documents & Moderation)
- **`DocumentModeratedEvent`**
  ```php
  namespace App\Modules\Notifications\Domain\Events;

  class DocumentModeratedEvent
  {
      public function __construct(
          public readonly int $taiLieuId,
          public readonly string $trangThai, // 'CHO_DUYET' | 'DA_DUYET' | 'TU_CHOI'
          public readonly ?int $nguoiDuyetId = null,
          public readonly ?string $lyDoTuChoi = null
      ) {}
  }
  ```

### 2.3. Module Đánh giá môn học (Course Reviews)
- **`ReviewReportedEvent`**
  ```php
  namespace App\Modules\Notifications\Domain\Events;

  class ReviewReportedEvent
  {
      public function __construct(
          public readonly int $danhGiaId,
          public readonly int $nguoiBaoCaoId,
          public readonly string $lyDoBaoCao
      ) {}
  }
  ```

### 2.4. Module Định danh & KYC (Identity & KYC)
- **`KycSubmittedEvent`**
  ```php
  namespace App\Modules\Notifications\Domain\Events;

  class KycSubmittedEvent
  {
      public function __construct(
          public readonly int $nguoiDungId,
          public readonly string $loaiXacThuc = 'CUU_SINH_VIEN'
      ) {}
  }
  ```
- **`KycDecisionEvent`**
  ```php
  namespace App\Modules\Notifications\Domain\Events;

  class KycDecisionEvent
  {
      public function __construct(
          public readonly int $nguoiDungId,
          public readonly string $trangThai, // 'DA_DUYET' | 'TU_CHOI'
          public readonly ?string $lyDo = null
      ) {}
  }
  ```

---

## 3. CẤU TRÚC `NotificationPayload` DTO

Mọi thông báo chuyển qua `NotificationDispatcherInterface` đều phải đóng gói vào `NotificationPayload`:

```php
namespace App\Modules\Notifications\Application\DTOs;

class NotificationPayload
{
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly NotificationType $type,
        public readonly NotificationCategory $category,
        public readonly ?string $targetUrl = null,
        public readonly ?array $data = null,
        public readonly ?string $icon = null,
        public readonly ?string $idempotencyKey = null,
    )
}
```

### Các trường dữ liệu:
1. **`title`** *(bắt buộc - tối đa 255 ký tự)*: Tiêu đề súc tích của thông báo.
2. **`message`** *(bắt buộc)*: Nội dung tóm lược hành động hoặc kết quả.
3. **`type`** *(bắt buộc - `NotificationType` enum)*: Định danh phân loại ngữ cảnh nghiệp vụ.
4. **`category`** *(bắt buộc - `NotificationCategory` enum)*:
   - `NotificationCategory::PERSONAL`: Thông báo riêng của sinh viên/người dùng.
   - `NotificationCategory::WORK_ITEM`: Hạng mục cần xử lý nghiệp vụ của Mod/Admin.
5. **`targetUrl`** *(tùy chọn)*: Đường dẫn nội bộ để người dùng click xem chi tiết (ví dụ: `/courses/5/documents/21`). Hệ thống tự động khử các link nguy hiểm.
6. **`data`** *(tùy chọn - mảng key/value)*: Metadata bổ trợ (ví dụ ID thực thể) không chứa dữ liệu nhạy cảm.
7. **`idempotencyKey`** *(tùy chọn)*: Khóa chống gửi lặp (ví dụ: `"kyc_approved_{$userId}_{$timestamp}"`).

---

## 4. HƯỚNG DẪN CODE VÀ CODE EXAMPLES TỪNG MODULE

### 4.1. Ví dụ Tích hợp trong Action Nghiệp vụ (Phương án khuyến nghị 1: Bắn Event)

Đây là phương án chuẩn nhất. Action nghiệp vụ chỉ thực hiện tác vụ dữ liệu và phát sinh sự kiện. Listener của Notification Module sẽ tự động xử lý phần còn lại.

```php
namespace App\Modules\Bounties\Application\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\Notifications\Domain\Events\BountyFulfillmentConfirmedEvent;

class ConfirmBountyFulfillmentAction
{
    public function execute(int $yeuCauId, int $taiLieuId, int $confirmerId): void
    {
        $fulfillment = DB::transaction(function () use ($yeuCauId, $taiLieuId, $confirmerId) {
            // 1. Cập nhật trạng thái yêu cầu
            // 2. Chuyển điểm cọc sang ví người đóng góp
            // 3. Ghi log kiểm toán ví điểm
            return $record;
        });

        // 4. Bắn sự kiện thông báo SAU KHI TRANSACTION COMMIT THÀNH CÔNG
        event(new BountyFulfillmentConfirmedEvent(
            yeuCauId: $fulfillment->ma_yeu_cau,
            taiLieuId: $fulfillment->ma_tai_lieu,
            nguoiDongGopId: $fulfillment->ma_nguoi_dong_gop,
            diemThuong: $fulfillment->diem_thuong
        ));
    }
}
```

### 4.2. Ví dụ Gửi thông báo trực tiếp qua `NotificationDispatcherInterface` (Phương án 2: Khi không cần Event riêng)

Sử dụng khi một tác vụ hệ thống (như cảnh báo bảo mật khẩn cấp, nhắc nhở định kỳ) muốn gửi thông báo trực tiếp mà không cần sinh Event:

```php
namespace App\Modules\Identity\Application\Actions;

use App\Models\NguoiDung;
use App\Modules\Notifications\Application\Contracts\NotificationDispatcherInterface;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use App\Modules\Notifications\Domain\Enums\NotificationCategory;
use App\Modules\Notifications\Domain\Enums\NotificationType;

class LockSuspiciousAccountAction
{
    public function __construct(
        private readonly NotificationDispatcherInterface $dispatcher
    ) {}

    public function execute(NguoiDung $user, string $reason): void
    {
        // Khóa tài khoản
        $user->update(['trang_thai' => 'KHOA_TAM_THOI']);

        // Gửi thông báo cảnh báo đến người dùng
        $payload = new NotificationPayload(
            title: 'Tài khoản của bạn tạm thời bị khóa',
            message: "Tài khoản bị tạm khóa do: {$reason}. Vui lòng liên hệ ban quản trị.",
            type: NotificationType::ADMIN_SECURITY_ALERT,
            category: NotificationCategory::PERSONAL,
            targetUrl: '/support',
            data: ['reason' => $reason],
            idempotencyKey: "lock_notice_user_{$user->ma_nguoi_dung}_" . date('YmdHi')
        );

        $this->dispatcher->send($user, $payload);
    }
}
```

### 4.3. Ví dụ Gửi cho nhóm Kiểm duyệt viên theo phân quyền

Khi cần gửi mục công việc đến các Kiểm duyệt viên phụ trách:

```php
namespace App\Modules\Moderation\Application\Actions;

use App\Modules\Notifications\Application\Contracts\NotificationDispatcherInterface;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use App\Modules\Notifications\Domain\Enums\NotificationCategory;
use App\Modules\Notifications\Domain\Enums\NotificationType;
use App\Modules\Notifications\Domain\Services\NotificationRecipientPolicy;

class DispatchModerationAlertAction
{
    public function __construct(
        private readonly NotificationDispatcherInterface $dispatcher,
        private readonly NotificationRecipientPolicy $recipientPolicy
    ) {}

    public function notifyFacultyModerators(int $facultyId, string $docTitle, int $docId): void
    {
        // Policy tự động truy vấn danh sách Kiểm duyệt viên thuộc khoa đang Active
        $moderators = $this->recipientPolicy->getFacultyModerators($facultyId);

        if ($moderators->isEmpty()) {
            // Fallback gửi cho Admin nếu khoa chưa có kiểm duyệt viên
            $moderators = $this->recipientPolicy->getActiveAdmins();
        }

        $payload = new NotificationPayload(
            title: 'Mục công việc: Tài liệu mới cần kiểm duyệt',
            message: "Tài liệu \"{$docTitle}\" đang chờ phê duyệt.",
            type: NotificationType::MOD_DOCUMENT_PENDING,
            category: NotificationCategory::WORK_ITEM,
            targetUrl: "/moderator/documents?status=CHO_DUYET",
            data: ['tai_lieu_id' => $docId]
        );

        $this->dispatcher->sendMany($moderators, $payload);
    }
}
```

---

## 5. BẢNG PHÂN LOẠI NOTIFICATION TYPE VÀ QUYỀN TRUY CẬP

| Enum Value | Đối tượng nhận | Danh mục | URL gợi ý |
| :--- | :--- | :--- | :--- |
| `BOUNTY_NEW_CONTRIBUTION` | Chủ yêu cầu Bounty | `PERSONAL` | `/courses/{mon_hoc_id}/documents/{tai_lieu_id}` |
| `BOUNTY_FULFILLMENT_ACCEPTED`| Người giải quyết Bounty | `PERSONAL` | `/courses/{mon_hoc_id}/documents/{tai_lieu_id}` |
| `BOUNTY_CANCELLED` | Chủ yêu cầu Bounty | `PERSONAL` | `/courses/{mon_hoc_id}?tab=bounties` |
| `BOUNTY_REWARD_CLAIMED` | Người giải quyết Bounty | `PERSONAL` | `/courses/{mon_hoc_id}/documents/{tai_lieu_id}` |
| `DOCUMENT_APPROVED` | Tác giả tài liệu | `PERSONAL` | `/courses/{mon_hoc_id}/documents/{tai_lieu_id}` |
| `DOCUMENT_REJECTED` | Tác giả tài liệu | `PERSONAL` | `/courses/{mon_hoc_id}/documents/{tai_lieu_id}` |
| `REVIEW_REPORTED` | Tác giả bài đánh giá | `PERSONAL` | `/courses/{mon_hoc_id}?tab=reviews` |
| `KYC_APPROVED` | Người nộp hồ sơ KYC | `PERSONAL` | `/profile` |
| `KYC_REJECTED` | Người nộp hồ sơ KYC | `PERSONAL` | `/kyc/resubmit` |
| `WALLET_POINTS_CHANGED` | Chủ sở hữu ví điểm | `PERSONAL` | `/wallet/transactions` |
| `MOD_DOCUMENT_PENDING` | Kiểm duyệt viên phụ trách | `WORK_ITEM` | `/moderator/documents?status=CHO_DUYET` |
| `MOD_REVIEW_REPORTED` | Kiểm duyệt viên | `WORK_ITEM` | `/moderator/reviews` |
| `MOD_KYC_PENDING` | Kiểm duyệt viên / Admin | `WORK_ITEM` | `/admin/kyc` |
| `MOD_BOUNTY_DISPUTED` | Kiểm duyệt viên | `WORK_ITEM` | `/moderator/bounties` |
| `MOD_DOCUMENT_REPORTED`| Kiểm duyệt viên | `WORK_ITEM` | `/moderator/reports` |
| `ADMIN_SECURITY_ALERT` | Quản trị viên (`ADMIN`) | `WORK_ITEM` | `/admin/security-logs` |
| `SYSTEM_MAINTENANCE` | Tất cả người dùng | `PERSONAL` | `/thong-bao` |

