<?php

namespace App\Modules\AcademicCatalog\Domain\Services;

use App\Models\ChuyenNganh;
use App\Models\MonHoc;

class AcademicCatalogRuleEvaluator
{
    // Đánh giá điều kiện chuyển đổi trạng thái (vô hiệu hóa hoặc kích hoạt) của Chuyên ngành
    public function validateMajorToggle(ChuyenNganh $major): ?string
    {
        // Chuyên ngành chỉ được kích hoạt khi Khoa cha đang hoạt động
        if ($major->trashed() && $major->khoa()->withTrashed()->whereNotNull('ngay_xoa_tam')->exists()) {
            return 'Không thể kích hoạt chuyên ngành khi Khoa đang bị vô hiệu hóa.';
        }

        return null;
    }

    // Đánh giá điều kiện chuyển đổi trạng thái (vô hiệu hóa hoặc kích hoạt) của Môn học
    public function validateCourseToggle(MonHoc $course, bool $hasCourseLinks): ?string
    {
        // Môn học chỉ được kích hoạt lại khi Khoa cha đang hoạt động
        if ($course->trashed() && $course->khoa()->withTrashed()->whereNull('ngay_xoa_tam')->doesntExist()) {
            return 'Không thể kích hoạt môn học khi Khoa đang bị vô hiệu hóa.';
        }

        // Không cho phép vô hiệu hóa môn học nếu đang có tài liệu, review hoặc yêu cầu tài liệu liên kết
        if (!$course->trashed() && $hasCourseLinks) {
            return 'Không thể vô hiệu hóa môn học đang có tài liệu, review hoặc yêu cầu tài liệu liên kết.';
        }

        return null;
    }
}
