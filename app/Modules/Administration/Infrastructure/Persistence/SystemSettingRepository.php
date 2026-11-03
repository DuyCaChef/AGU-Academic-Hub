<?php

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Models\CauHinhHeThong;
use Illuminate\Database\Eloquent\Collection;

class SystemSettingRepository
{
    /**
     * Lấy toàn bộ danh sách cấu hình hệ thống nhóm theo phân loại.
     */
    public function getAllGrouped(): array
    {
        /** @var Collection<int, CauHinhHeThong> $settings */
        $settings = CauHinhHeThong::all();

        return $settings->groupBy('nhom')->toArray();
    }

    /**
     * Tìm cấu hình theo khóa.
     */
    public function findByKey(string $key): ?CauHinhHeThong
    {
        return CauHinhHeThong::find($key);
    }

    /**
     * Cập nhật giá trị cấu hình.
     */
    public function updateValue(CauHinhHeThong $setting, mixed $value): CauHinhHeThong
    {
        $setting->update([
            'gia_tri' => (string) $value,
        ]);

        return $setting;
    }
}

