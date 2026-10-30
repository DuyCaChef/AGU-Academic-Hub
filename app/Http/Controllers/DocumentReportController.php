<?php

namespace App\Http\Controllers;

use App\Models\TaiLieu;
use App\Shared\Services\DocumentReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentReportController extends Controller
{
    public function store(Request $request, DocumentReportService $service): RedirectResponse
    {
        $data = $request->validate([
            'ma_tai_lieu' => ['required', 'integer', 'exists:tai_lieu,ma_tai_lieu'],
            'ly_do' => ['required', 'in:TRUNG_LAP,SAI_NOI_DUNG,MA_DOC,BAN_QUYEN,KHAC'],
            'chi_tiet_loi' => ['nullable', 'string', 'max:2000'],
        ]);

        $document = TaiLieu::query()->findOrFail($data['ma_tai_lieu']);
        $service->create($document, $request->user(), $data);

        return back()->with('success', 'Đã gửi báo cáo. Cảm ơn bạn đã giúp bảo vệ kho tài liệu.');
    }
}
