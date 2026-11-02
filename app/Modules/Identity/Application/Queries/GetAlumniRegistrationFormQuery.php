<?php

namespace App\Modules\Identity\Application\Queries;

use App\Models\Khoa;
use Illuminate\Database\Eloquent\Collection;

class GetAlumniRegistrationFormQuery
{
    // Lấy danh sách Khoa phục vụ form nộp hồ sơ cựu sinh viên
    public function execute(): Collection
    {
        return Khoa::select('ma_khoa', 'ten_khoa', 'ky_hieu_khoa')->get();
    }
}
