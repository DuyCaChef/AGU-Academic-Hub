<?php

namespace App\Modules\Notifications\Application\Queries;

use App\Models\NguoiDung;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetNotificationsQuery
{
    // Lấy danh sách thông báo có phân trang và bộ lọc cho người dùng hiện tại
    public function execute(NguoiDung $user, ?string $filter = 'all', ?string $category = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $user->notifications();

        // 1. Bộ lọc trạng thái đọc
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        // 2. Bộ lọc nhóm thông báo (Cá nhân vs Tác vụ xử lý)
        if (!empty($category)) {
            $query->where('data', 'like', '%"category":"' . $category . '"%');
        }

        // 3. Sắp xếp mới nhất trước và phân trang
        return $query->latest('created_at')->paginate($perPage)->withQueryString();
    }
}

