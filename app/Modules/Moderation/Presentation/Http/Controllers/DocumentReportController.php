<?php

namespace App\Modules\Moderation\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Moderation\Application\Actions\SubmitDocumentReportAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentReportController extends Controller
{
    // Tiếp nhận báo cáo vi phạm tài liệu từ người dùng
    public function store(Request $request, SubmitDocumentReportAction $action): RedirectResponse
    {
        $data = $request->validate([
            'ma_tai_lieu'  => ['required', 'integer', 'exists:tai_lieu,ma_tai_lieu'],
            'ly_do'        => ['required', 'in:TRUNG_LAP,SAI_NOI_DUNG,MA_DOC,BAN_QUYEN,KHAC'],
            'chi_tiet_loi' => ['nullable', 'string', 'max:2000'],
        ]);

        $action->execute((int) $data['ma_tai_lieu'], $data, $request->user());

        return back()->with('success', 'Đã gửi báo cáo. Cảm ơn bạn đã giúp bảo vệ kho tài liệu.');
    }
}
