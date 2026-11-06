<?php

namespace App\Modules\Bounties\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bounties\Application\Queries\GetWalletDataQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// Controller điều phối giao diện hiển thị ví điểm cá nhân và lịch sử biến động
class WalletController extends Controller
{
    // Hiển thị giao diện Ví điểm cá nhân
    public function index(Request $request, GetWalletDataQuery $query): Response
    {
        return Inertia::render('Wallet/Index', $query->execute($request->user(), $request->all()));
    }
}
