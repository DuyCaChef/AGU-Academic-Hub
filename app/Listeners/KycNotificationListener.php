<?php

namespace App\Listeners;

use App\Services\Community\Contracts\NotificationDispatcherInterface;
use App\Events\KycDecisionEvent;
use App\Events\KycSubmittedEvent;
use App\Services\Community\NotificationPayloadBuilder;
use App\Services\Community\NotificationRecipientPolicy;

class KycNotificationListener
{
    public function __construct(
        protected NotificationDispatcherInterface $dispatcher,
        protected NotificationPayloadBuilder $payloadBuilder,
        protected NotificationRecipientPolicy $recipientPolicy
    ) {}

    // Thông báo cho Quản trị viên khi có hồ sơ KYC mới (UC-MOD-07, UC-ADM-01)
    public function handleKycSubmitted(KycSubmittedEvent $event): void
    {
        $admins = $this->recipientPolicy->getAdminsForKyc();
        $payload = $this->payloadBuilder->kycSubmitted($event->kyc, $event->applicant->ho_ten ?? 'Cựu sinh viên');

        $this->dispatcher->dispatchMultiple($admins, $payload);
    }

    // Thông báo kết quả phê duyệt/từ chối KYC cho Cựu sinh viên
    public function handleKycDecision(KycDecisionEvent $event): void
    {
        $applicant = $event->kyc->nguoiDung;
        if ($applicant) {
            $isApproved = ($event->decision === 'DA_DUYET');
            $payload = $this->payloadBuilder->kycDecision($event->kyc, $isApproved, $event->reason);

            $this->dispatcher->dispatch($applicant, $payload);
        }
    }
}

