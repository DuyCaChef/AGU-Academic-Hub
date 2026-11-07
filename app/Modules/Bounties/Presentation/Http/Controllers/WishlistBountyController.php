<?php

namespace App\Modules\Bounties\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bounties\Application\Actions\CancelBountyRequestAction;
use App\Modules\Bounties\Application\Actions\ConfirmBountyFulfillmentAction;
use App\Modules\Bounties\Application\Actions\CreateBountyRequestAction;
use App\Modules\Bounties\Application\Actions\FulfillBountyRequestAction;
use App\Modules\Bounties\Application\Queries\GetWishlistBountyDetailQuery;
use App\Modules\Bounties\Application\Queries\GetWishlistBountyListQuery;
use App\Modules\Bounties\Presentation\Http\Requests\ConfirmBountyRequest;
use App\Modules\Bounties\Presentation\Http\Requests\FulfillBountyRequest;
use App\Modules\Bounties\Presentation\Http\Requests\StoreBountyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// Controller điều phối luồng danh sách mong muốn (Wishlist) và săn thưởng tài liệu (Bounties)
class WishlistBountyController extends Controller
{
    // Danh sách yêu cầu tìm tài liệu (Wishlist) (GET /yeu-cau-tai-lieu)
    public function index(Request $request, GetWishlistBountyListQuery $query): Response|JsonResponse
    {
        $data = $query->execute($request);

        if ($request->wantsJson()) {
            return response()->json($data['requests']);
        }

        return Inertia::render('Wishlist/Index', [
            'requests' => $data['requests'],
            'filters'  => $data['filters'],
            'courses'  => $data['courses'],
        ]);
    }

    // Xem chi tiết yêu cầu & các bài nộp phản hồi (GET /yeu-cau-tai-lieu/{ma_yeu_cau})
    public function show(Request $request, int $ma_yeu_cau, GetWishlistBountyDetailQuery $query): Response|JsonResponse
    {
        $data = $query->execute($ma_yeu_cau, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'request'    => $data['item'],
                'is_creator' => $data['is_creator'],
            ]);
        }

        return Inertia::render('Wishlist/Show', [
            'item'       => $data['item'],
            'is_creator' => $data['is_creator'],
        ]);
    }

    // Tạo yêu cầu tìm tài liệu mới kèm đóng băng điểm thưởng (POST /yeu-cau-tai-lieu)
    public function store(StoreBountyRequest $request, CreateBountyRequestAction $action): RedirectResponse
    {
        $docRequest = $action->execute($request->user(), $request->validated());

        if ($request->input('redirect_to') === 'back' || $request->has('redirect_back')) {
            return back()->with('success', "Đã tạo yêu cầu tìm tài liệu và đóng băng {$docRequest->diem_thuong} điểm thưởng.");
        }

        return redirect()->route('document-requests.index')
            ->with('success', "Đã tạo yêu cầu tìm tài liệu và đóng băng {$docRequest->diem_thuong} điểm thưởng.");
    }

    // Đóng góp tài liệu giải quyết yêu cầu (POST /yeu-cau-tai-lieu/{ma_yeu_cau}/dong-gop)
    public function fulfill(FulfillBountyRequest $request, int $ma_yeu_cau, FulfillBountyRequestAction $action): RedirectResponse
    {
        $result = $action->execute($request->user(), $ma_yeu_cau, $request->validated(), $request->file('file'));

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    // Xác nhận tài liệu hợp lệ -> Giải ngân Bounty Points (POST /yeu-cau-tai-lieu/{ma_yeu_cau}/xac-nhan)
    public function confirm(ConfirmBountyRequest $request, int $ma_yeu_cau, ConfirmBountyFulfillmentAction $action): RedirectResponse
    {
        $result = $action->execute($request->user(), $ma_yeu_cau, (int) $request->input('ma_dap_ung'));

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    // Hủy yêu cầu và hoàn lại điểm thưởng vào ví (DELETE /yeu-cau-tai-lieu/{ma_yeu_cau})
    public function destroy(Request $request, int $ma_yeu_cau, CancelBountyRequestAction $action): RedirectResponse
    {
        $result = $action->execute($request->user(), $ma_yeu_cau);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        if ($request->input('redirect_to') === 'back' || $request->has('redirect_back')) {
            return back()->with('success', $result['message']);
        }

        return redirect()->route('document-requests.index')
            ->with('success', $result['message']);
    }
}

