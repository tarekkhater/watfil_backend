<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class RejectWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:1000',
            'note'   => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'سبب الرفض مطلوب.',
        ];
    }
}
