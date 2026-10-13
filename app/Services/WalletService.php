<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Lớp DUY NHẤT được phép đổi số dư ví. Mọi thay đổi:
 *  - chạy trong giao dịch CSDL và KHÓA dòng người dùng (hai thao tác cùng lúc không làm sai số dư);
 *  - ghi một dòng vào sổ cái kèm số dư sau khi đổi.
 */
class WalletService
{
    /** Cộng tiền vào ví. $opts: order_id, refund_request_id, note, created_by */
    public function credit(int $userId, int $amount, string $type, array $opts = []): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Số tiền cộng vào ví phải lớn hơn 0.');
        }

        return $this->change($userId, $amount, $type, $opts);
    }

    /** Trừ tiền ví; không đủ số dư thì ném InsufficientWalletBalance. */
    public function debit(int $userId, int $amount, string $type, array $opts = []): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Số tiền trừ khỏi ví phải lớn hơn 0.');
        }

        return $this->change($userId, -$amount, $type, $opts);
    }

    public function balance(int $userId): int
    {
        return (int) User::whereKey($userId)->value('wallet_balance');
    }

    /** Tổng sổ cái của một người - phải luôn bằng wallet_balance (dùng để kiểm tra chéo) */
    public function ledgerSum(int $userId): int
    {
        return (int) WalletTransaction::where('user_id', $userId)->sum('amount');
    }

    private function change(int $userId, int $delta, string $type, array $opts): WalletTransaction
    {
        return DB::transaction(function () use ($userId, $delta, $type, $opts) {
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $new = (int) $user->wallet_balance + $delta;

            if ($new < 0) {
                throw new InsufficientWalletBalance('Số dư ví không đủ.');
            }

            User::whereKey($userId)->update(['wallet_balance' => $new]);

            return WalletTransaction::create([
                'user_id' => $userId,
                'type' => $type,
                'amount' => $delta,
                'balance_after' => $new,
                'order_id' => $opts['order_id'] ?? null,
                'refund_request_id' => $opts['refund_request_id'] ?? null,
                'note' => isset($opts['note']) ? mb_substr($opts['note'], 0, 255) : null,
                'created_by' => $opts['created_by'] ?? null,
            ]);
        });
    }
}
