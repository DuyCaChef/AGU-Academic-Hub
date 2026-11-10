<?php

namespace App\Modules\Notifications\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Application\Actions\DeleteNotificationAction;
use App\Modules\Notifications\Application\Actions\MarkAllNotificationsAsReadAction;
use App\Modules\Notifications\Application\Actions\MarkNotificationAsReadAction;
use App\Modules\Notifications\Application\Queries\GetNotificationsQuery;
use App\Modules\Notifications\Application\Queries\GetUnreadNotificationCountQuery;
use App\Modules\Notifications\Presentation\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    // Lấy danh sách thông báo: Hỗ trợ cả Web Inertia (Trung tâm thông báo) và REST API (Dropdown/Polling)
    public function index(
        Request $request,
        GetNotificationsQuery $query,
        GetUnreadNotificationCountQuery $countQuery
    ): JsonResponse|InertiaResponse {
        $user = $request->user();
        $filter = $request->query('filter', 'all');
        $category = $request->query('category');
        $perPage = (int) $request->query('per_page', 15);

        $paginator = $query->execute($user, $filter, $category, $perPage);
        $unreadCount = $countQuery->execute($user);

        // Trường hợp API JSON (Dropdown, Ajax fetch, Mobile)
        if (!$request->inertia() && ($request->wantsJson() || $request->ajax() || $request->has('json'))) {
            return response()->json([
                'unread_count'  => $unreadCount,
                'notifications' => NotificationResource::collection($paginator->items()),
                'pagination'    => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'has_more'     => $paginator->hasMorePages(),
                ],
            ]);
        }

        // Trường hợp truy cập giao diện Notification Center qua Inertia
        return Inertia::render('Notifications/Index', [
            'notifications' => [
                'data'         => NotificationResource::collection($paginator->items())->resolve(),
                'links'        => $paginator->linkCollection()->toArray(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'unread_count'  => $unreadCount,
            'filters'       => [
                'filter'   => $filter,
                'category' => $category,
            ],
        ]);
    }

    // Đếm số lượng thông báo chưa đọc của người dùng hiện tại
    public function unreadCount(Request $request, GetUnreadNotificationCountQuery $countQuery): JsonResponse
    {
        return response()->json([
            'unread_count' => $countQuery->execute($request->user()),
        ]);
    }

    // Đánh dấu một thông báo là đã đọc
    public function markAsRead(
        Request $request,
        string $id,
        MarkNotificationAsReadAction $action,
        GetUnreadNotificationCountQuery $countQuery
    ): JsonResponse {
        $success = $action->execute($request->user(), $id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông báo hoặc bạn không có quyền truy cập.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success'      => true,
            'unread_count' => $countQuery->execute($request->user()),
        ]);
    }

    // Đánh dấu toàn bộ thông báo của người dùng là đã đọc
    public function markAllAsRead(
        Request $request,
        MarkAllNotificationsAsReadAction $action
    ): JsonResponse {
        $action->execute($request->user());

        return response()->json([
            'success'      => true,
            'unread_count' => 0,
        ]);
    }

    // Xóa một thông báo thuộc quyền sở hữu của người dùng hiện tại
    public function destroy(
        Request $request,
        string $id,
        DeleteNotificationAction $action,
        GetUnreadNotificationCountQuery $countQuery
    ): JsonResponse {
        $success = $action->execute($request->user(), $id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông báo hoặc bạn không có quyền xóa.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success'      => true,
            'unread_count' => $countQuery->execute($request->user()),
        ]);
    }
}

