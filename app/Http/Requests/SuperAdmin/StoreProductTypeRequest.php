<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:45|unique:product_types,name',
            'name_ar' => 'required|string|max:45',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'اسم النوع (EN) مطلوب',
            'name.unique'      => 'اسم النوع مستخدم بالفعل',
            'name_ar.required' => 'اسم النوع (AR) مطلوب',
        ];
    }
}
