<?php

namespace App\Http\Requests\Api\V1\Trust;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'in:listing,user'],
            'reportable_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
