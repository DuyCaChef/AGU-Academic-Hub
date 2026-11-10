<?php

namespace App\Modules\Notifications\Infrastructure\Listeners;

use App\Models\NguoiDung;
use App\Modules\Notifications\Application\Contracts\NotificationDispatcherInterface;
use App\Modules\Notifications\Domain\Events\BountyCancelledEvent;
use App\Modules\Notifications\Domain\Events\BountyFulfillmentConfirmedEvent;
use App\Modules\Notifications\Domain\Events\DocumentContributedToBountyEvent;
use App\Modules\Notifications\Domain\Services\NotificationPayloadBuilder;
use App\Modules\Notifications\Domain\Services\NotificationRecipientPolicy;

class BountyNotificationListener
{
    public function __construct(
        protected NotificationDispatcherInterface $dispatcher,
        protected NotificationPayloadBuilder $payloadBuilder,
        protected NotificationRecipientPolicy $recipientPolicy
    ) {}

    // Xử lý khi có tài liệu đóng góp vào Bounty (UC-ST-01, UC-MOD-06)
    public function handleDocumentContributed(DocumentContributedToBountyEvent $event): void
    {
        $yeuCau = $event->yeuCau;
        $dapUng = $event->dapUng;
        $doc = $dapUng->taiLieu;

        $isPendingApproval = ($doc && $doc->trang_thai !== 'DA_DUYET');

        // 1. Gửi thông báo cho người tạo yêu cầu (Requester)
        if ($yeuCau->nguoiTao) {
            $payload = $this->payloadBuilder->bountyContributed($yeuCau, $dapUng, $isPendingApproval);
            $this->dispatcher->dispatch($yeuCau->nguoiTao, $payload);
        }

        // 2. Nếu tài liệu cần kiểm duyệt, gửi thông báo cho Ban kiểm duyệt (UC-MOD-06)
        if ($isPendingApproval && $doc) {
            $moderatorPayload = $this->payloadBuilder->bountyDocumentReviewRequired($yeuCau, $doc);
            $moderators = $this->recipientPolicy->getModeratorsForDocumentReview();
            $this->dispatcher->dispatchMultiple($moderators, $moderatorPayload);
        }
    }

    // Xử lý khi nghiệm thu giải ngân Bounty thành công (UC-ST-04, UC-ST-05)
    public function handleBountyConfirmed(BountyFulfillmentConfirmedEvent $event): void
    {
        // 1. Thông báo giải ngân cho người tạo yêu cầu (Requester)
        $reqPayload = $this->payloadBuilder->bountyConfirmedRequester($event->yeuCau, $event->points);
        $this->dispatcher->dispatch($event->requester, $reqPayload);

        // 2. Thông báo nhận điểm thưởng cho người đóng góp (Contributor)
        $contributor = $event->dapUng->nguoiDongGop;
        if ($contributor) {
            $contribPayload = $this->payloadBuilder->bountyConfirmedContributor($event->yeuCau, $event->points);
            $this->dispatcher->dispatch($contributor, $contribPayload);
        }
    }

    // Xử lý khi yêu cầu Bounty bị hủy (UC-ST-06, UC-ST-07)
    public function handleBountyCancelled(BountyCancelledEvent $event): void
    {
        // 1. Thông báo hoàn điểm cho người tạo
        $reqPayload = $this->payloadBuilder->bountyRefundedRequester($event->yeuCau, $event->refundedPoints);
        $this->dispatcher->dispatch($event->requester, $reqPayload);

        // 2. Thông báo cho các contributor đã nộp bài vào yêu cầu này
        if (!empty($event->contributorIds)) {
            $contributors = NguoiDung::whereIn('ma_nguoi_dung', $event->contributorIds)->get();
            $cancelPayload = $this->payloadBuilder->bountyCancelledContributor($event->yeuCau);
            $this->dispatcher->dispatchMultiple($contributors, $cancelPayload);
        }
    }
}

