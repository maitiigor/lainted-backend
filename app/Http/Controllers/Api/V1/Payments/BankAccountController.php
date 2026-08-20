<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\BankAccount;
use App\Domain\Payments\Services\PaystackTransferService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Payments\ResolveBankAccountRequest;
use App\Http\Requests\Api\V1\Payments\StoreBankAccountRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BankAccountController extends Controller
{
    public function __construct(private readonly PaystackTransferService $transfers) {}

    public function banks(): JsonResponse
    {
        try {
            $banks = $this->transfers->listBanks();
        } catch (PaymentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($banks);
    }

    public function resolve(ResolveBankAccountRequest $request): JsonResponse
    {
        try {
            $resolved = $this->transfers->resolveAccount(
                $request->validated('account_number'),
                $request->validated('bank_code'),
            );
        } catch (PaymentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($resolved);
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            BankAccount::where('user_id', $request->user()->id)->latest()->get(),
        );
    }

    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        $account = DB::transaction(function () use ($request) {
            $user = $request->user();

            // The column defaults to true, which would leave a user with
            // two "default" accounts - always computed explicitly here,
            // true only for a user's first account.
            $isFirst = ! BankAccount::where('user_id', $user->id)->exists();

            return BankAccount::create([
                'user_id' => $user->id,
                'bank_name' => $request->validated('bank_name'),
                'bank_code' => $request->validated('bank_code'),
                'account_number_encrypted' => $request->validated('account_number'),
                'account_name' => $request->validated('account_name'),
                'is_default' => $isFirst,
            ]);
        });

        return ApiResponse::success($account, 'Bank account added.', 201);
    }

    public function destroy(Request $request, BankAccount $bankAccount): JsonResponse
    {
        if ($bankAccount->user_id !== $request->user()->id) {
            throw new NotFoundHttpException();
        }

        $bankAccount->delete();

        return ApiResponse::success(null, 'Bank account removed.');
    }
}
