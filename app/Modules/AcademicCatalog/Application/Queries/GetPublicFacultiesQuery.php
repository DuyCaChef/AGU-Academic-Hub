<?php

namespace App\Modules\AcademicCatalog\Application\Queries;

use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Collection;

class GetPublicFacultiesQuery
{
    // Lấy danh sách Khoa đang hoạt động và sắp xếp ưu tiên Khoa của người dùng đăng nhập
    public function execute(?NguoiDung $user = null): Collection
    {
        return Khoa::with('monHocs')
            ->get()
            ->sortBy(function ($faculty) use ($user) {
                return $user && $faculty->ma_khoa === $user->ma_khoa ? 0 : 1;
            })
            ->values();
    }

    // Lấy danh sách môn học công khai trực thuộc một khoa
    public function getCoursesByFacultyId(int $facultyId): Collection
    {
        return MonHoc::query()
            ->where('ma_khoa', $facultyId)
            ->whereHas('khoa')
            ->get();
    }
}

