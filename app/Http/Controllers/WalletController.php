<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Auth;

/** Trang Ví ToyLand của khách: số dư, lịch sử biến động, các yêu cầu hoàn tiền/trả hàng */
class WalletController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $transactions = WalletTransaction::where('user_id', $user->id)->orderByDesc('id')->paginate(15);
        $requests = RefundRequest::with('order')->where('user_id', $user->id)->orderByDesc('id')->limit(20)->get();

        return view('wallet.index', [
            'balance' => (int) $user->fresh()->wallet_balance,
            'transactions' => $transactions,
            'requests' => $requests,
        ]);
    }
}
