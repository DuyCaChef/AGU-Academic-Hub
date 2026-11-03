<?php

namespace App\Modules\Administration\Application\Queries;

use App\Modules\Administration\Infrastructure\Persistence\RawStudentRepository;
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

