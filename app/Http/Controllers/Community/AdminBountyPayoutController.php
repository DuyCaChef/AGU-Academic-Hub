<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\RejectBountyPayoutRequest;
use App\Services\Community\Actions\ApproveBountyPayoutAction;
use App\Services\Community\RejectBountyPayoutService;
use App\Services\Community\Queries\GetBountyPayoutDetailQuery;
use App\Services\Community\Queries\GetBountyPayoutQueueQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// Controller điều phối hàng đợi duyệt giải ngân điểm Bounty dành cho Admin và Kiểm duyệt viên
class AdminBountyPayoutController extends Controller
{
    // Danh sách yêu cầu tài liệu chờ duyệt giải ngân (GET /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan)
    public function index(Request $request, GetBountyPayoutQueueQuery $query): Response
    {
        $data = $query->execute();

        return Inertia::render('Admin/Bounties/PayoutQueue', [
            'queue' => $data['queue'],
        ]);
    }

    // Chi tiết yêu cầu chờ duyệt giải ngân (GET /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/{ma_yeu_cau})
    public function show(int $ma_yeu_cau, GetBountyPayoutDetailQuery $query): Response
    {
        $data = $query->execute($ma_yeu_cau);

        return Inertia::render('Admin/Bounties/PayoutDetail', [
            'yeuCau' => $data['yeuCau'],
        ]);
    }

    // Duyệt giải ngân điểm Bounty (PUT /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/{ma_yeu_cau}/duyet)
    public function approve(Request $request, int $ma_yeu_cau, ApproveBountyPayoutAction $action): RedirectResponse
    {
        $result = $action->execute($request->user(), $ma_yeu_cau);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('admin.bounties.payout-queue')
            ->with('success', $result['message']);
    }

    // Từ chối giải ngân điểm Bounty (PUT /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/{ma_yeu_cau}/tu-choi)
    public function reject(RejectBountyPayoutRequest $request, int $ma_yeu_cau, RejectBountyPayoutService $service): RedirectResponse
    {
        $result = $service->execute($request->user(), $ma_yeu_cau, $request->input('ly_do'));

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('admin.bounties.payout-queue')
            ->with('success', $result['message']);
    }
}

