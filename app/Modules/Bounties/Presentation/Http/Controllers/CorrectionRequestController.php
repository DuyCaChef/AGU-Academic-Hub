<?php

namespace App\Modules\Bounties\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bounties\Application\Actions\ConfirmCorrectionCompletionAction;
use App\Modules\Bounties\Application\Actions\CreateCorrectionRequestAction;
use App\Modules\Bounties\Application\Actions\RespondCorrectionRequestAction;
use App\Modules\Bounties\Application\Actions\SubmitReplacementDocumentAction;
use App\Modules\Bounties\Presentation\Http\Requests\ConfirmCorrectionCompletionRequest;
use App\Modules\Bounties\Presentation\Http\Requests\RespondCorrectionRequest;
use App\Modules\Bounties\Presentation\Http\Requests\StoreCorrectionRequest;
use App\Modules\Bounties\Presentation\Http\Requests\SubmitReplacementRequest;
use Illuminate\Http\RedirectResponse;

// Controller điều phối quy trình yêu cầu chỉnh sửa tài liệu sau khi Bounty hoàn thành (Phần 6)
class CorrectionRequestController extends Controller
{
    // Tạo yêu cầu chỉnh sửa mới (POST /yeu-cau-tai-lieu/{ma_yeu_cau}/yeu-cau-chinh-sua)
    public function store(
        StoreCorrectionRequest $request,
        int $ma_yeu_cau,
        CreateCorrectionRequestAction $action
    ): RedirectResponse {
        $result = $action->execute(
            $request->user(),
            $ma_yeu_cau,
            $request->input('ly_do_chinh_sua')
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    // Người đóng góp phản hồi (Chấp nhận hoặc Từ chối) (POST /yeu-cau-chinh-sua/{ma_yeu_cau_chinh_sua}/phan-hoi)
    public function respond(
        RespondCorrectionRequest $request,
        int $ma_yeu_cau_chinh_sua,
        RespondCorrectionRequestAction $action
    ): RedirectResponse {
        $result = $action->execute(
            $request->user(),
            $ma_yeu_cau_chinh_sua,
            $request->input('hanh_dong'),
            $request->input('ly_do_tu_choi')
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    // Người đóng góp nộp phiên bản thay thế (POST /yeu-cau-chinh-sua/{ma_yeu_cau_chinh_sua}/nop-thay-the)
    public function submitReplacement(
        SubmitReplacementRequest $request,
        int $ma_yeu_cau_chinh_sua,
        SubmitReplacementDocumentAction $action
    ): RedirectResponse {
        $result = $action->execute(
            $request->user(),
            $ma_yeu_cau_chinh_sua,
            $request->validated(),
            $request->file('file')
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    // Người tạo nghiệm thu hoàn tất chỉnh sửa (POST /yeu-cau-chinh-sua/{ma_yeu_cau_chinh_sua}/xac-nhan-hoan-thanh)
    public function confirmCompletion(
        ConfirmCorrectionCompletionRequest $request,
        int $ma_yeu_cau_chinh_sua,
        ConfirmCorrectionCompletionAction $action
    ): RedirectResponse {
        $result = $action->execute(
            $request->user(),
            $ma_yeu_cau_chinh_sua,
            (int) $request->input('ma_lan_nop_thay_the')
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }
}

