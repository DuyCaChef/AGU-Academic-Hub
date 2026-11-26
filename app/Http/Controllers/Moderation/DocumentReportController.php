<?php

namespace App\Http\Controllers\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\StoreDocumentReportRequest;
use App\Services\Moderation\DocumentReportService;
use Illuminate\Http\RedirectResponse;

class DocumentReportController extends Controller
{
    // Tiếp nhận báo cáo vi phạm tài liệu từ người dùng
    public function store(
        StoreDocumentReportRequest $request,
        DocumentReportService $service
    ): RedirectResponse
    {
        $service->submit(
            (int) $request->validated('ma_tai_lieu'),
            $request->validated(),
            $request->user()
        );

        return back()->with('success', 'Đã gửi báo cáo. Cảm ơn bạn đã giúp bảo vệ kho tài liệu.');
    }
}

