<?php

namespace App\Services\Admin\Queries;

use App\Services\Admin\Repositories\SystemSettingRepository;

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

