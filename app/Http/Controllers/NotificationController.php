<?php

namespace App\Http\Controllers;

use App\Models\ThongBao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{

    // Lấy danh sách thông báo và số lượng chưa đọc của người dùng hiện tại
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->take(20)
            ->get()
            ->map(function (ThongBao $item): array {
                return [
                    'id'               => $item->id,
                    'type'             => class_basename($item->loai ?? $item->type),
                    'title'            => $item->tieu_de,
                    'message'          => $item->noi_dung,
                    'url'              => $item->duong_dan,
                    'read_at'          => $item->da_doc_luc?->toIso8601String(),
                    'is_read'          => $item->read(),
                    'created_at'       => $item->created_at?->toIso8601String(),
                    'created_at_human' => $item->created_at?->diffForHumans(),
                ];
            });

        return response()->json([
            'unread_count'  => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    // Đánh dấu một thông báo đã đọc
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification && $notification->unread()) {
            $notification->markAsRead();
        }

        return response()->json([
            'success'      => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    // Đánh dấu toàn bộ thông báo của người dùng đã đọc
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success'      => true,
            'unread_count' => 0,
        ]);
    }
}

