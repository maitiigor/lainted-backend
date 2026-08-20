<?php

namespace App\Http\Requests\Api\V1\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'string', 'exists:bank_accounts,uuid'],
            // The real minimum (WithdrawalService::MIN_WITHDRAWAL_AMOUNT)
            // is enforced there, not here - that's a business rule, not a
            // format check, same reasoning as StoreCheckoutRequest's email.
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
