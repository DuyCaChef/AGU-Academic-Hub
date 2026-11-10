<?php

namespace App\Modules\Notifications\Infrastructure\Listeners;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Modules\Notifications\Application\Contracts\NotificationDispatcherInterface;
use App\Modules\Notifications\Domain\Events\DocumentModeratedEvent;
use App\Modules\Notifications\Domain\Services\NotificationPayloadBuilder;

class DocumentModerationNotificationListener
{
    public function __construct(
        protected NotificationDispatcherInterface $dispatcher,
        protected NotificationPayloadBuilder $payloadBuilder
    ) {}

    // Xử lý thông báo khi tài liệu được phê duyệt hoặc từ chối kiểm duyệt (UC-ST-02, UC-ST-03, UC-ST-08)
    public function handle(DocumentModeratedEvent $event): void
    {
        $document = $event->document;
        $author = $document->nguoiDang;

        // 1. Phê duyệt tài liệu
        if (in_array($event->decision, ['KEEP', 'RESTORE'], true)) {
            // Thông báo cho tác giả tải lên
            if ($author) {
                $payload = $this->payloadBuilder->documentApproved($document);
                $this->dispatcher->dispatch($author, $payload);
            }

            // Kiểm tra xem tài liệu này có nằm trong bài nộp của yêu cầu săn thưởng đang mở nào không
            // Nếu có, gửi thông báo cho Requester của Bounty đó (UC-ST-02)
            $submissions = $document->dapUngYeuCaus()
                ->with(['yeuCau.nguoiTao'])
                ->get();

            foreach ($submissions as $sub) {
                $yeuCau = $sub->yeuCau;
                if ($yeuCau && $yeuCau->trang_thai === TrangThaiYeuCauTaiLieu::MO && $yeuCau->nguoiTao) {
                    $bountyPayload = $this->payloadBuilder->bountyDocumentApprovedForRequester($yeuCau, $document);
                    $this->dispatcher->dispatch($yeuCau->nguoiTao, $bountyPayload);
                }
            }
        }

        // 2. Từ chối hoặc xóa tài liệu
        if (in_array($event->decision, ['DELETE', 'HIDE'], true)) {
            if ($author) {
                $payload = $this->payloadBuilder->documentRejected($document, $event->reason);
                $this->dispatcher->dispatch($author, $payload);
            }
        }
    }
}

