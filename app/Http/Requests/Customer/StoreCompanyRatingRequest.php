<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'التقييم مطلوب',
            'rating.integer'  => 'التقييم يجب أن يكون رقمًا',
            'rating.min'      => 'التقييم يجب أن يكون من 1 إلى 5',
            'rating.max'      => 'التقييم يجب أن يكون من 1 إلى 5',
        ];
    }
}
