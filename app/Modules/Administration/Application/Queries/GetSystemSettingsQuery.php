<?php

namespace App\Modules\Administration\Application\Queries;

use App\Modules\Administration\Infrastructure\Persistence\SystemSettingRepository;

class GetSystemSettingsQuery
{
    public function __construct(
        protected SystemSettingRepository $repository
    ) {}

    public function execute(): array
    {
        return $this->repository->getAllGrouped();
    }
}

