<?php

namespace App\Services\Admin\Queries;

use App\Services\Admin\Repositories\RawStudentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetRawStudentsQuery
{
    public function __construct(
        protected RawStudentRepository $repository
    ) {}

    public function execute(array $filters = []): array
    {
        $paginator = $this->repository->getPaginated($filters, 20);

        return [
            'students' => $paginator,
            'filters'  => $filters,
        ];
    }
}

