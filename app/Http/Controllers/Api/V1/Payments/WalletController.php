<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\BankAccount;
use App\Domain\Payments\Services\WithdrawalService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Payments\StoreWithdrawalRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WalletController extends Controller
{
    public function __construct(private readonly WithdrawalService $withdrawals) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($request->user()->wallet);
    }

    public function transactions(Request $request): JsonResponse
    {
        $transactions = $request->user()->wallet
            ->walletTransactions()
            ->with('bankAccount')
            ->latest()
            ->paginate(20);

        return ApiResponse::success($transactions);
    }

    public function withdraw(StoreWithdrawalRequest $request): JsonResponse
    {
        $bankAccount = BankAccount::where('uuid', $request->validated('bank_account_id'))->firstOrFail();

        if ($bankAccount->user_id !== $request->user()->id) {
            throw new NotFoundHttpException();
        }

        try {
            $transaction = $this->withdrawals->request($request->user(), $bankAccount, (float) $request->validated('amount'));
        } catch (PaymentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($transaction, 'Withdrawal requested.', 201);
    }
}
