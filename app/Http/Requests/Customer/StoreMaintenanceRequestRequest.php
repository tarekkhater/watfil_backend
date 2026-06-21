<?php

namespace App\Http\Requests\Customer;

use App\Support\MaintenanceLookups;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxStages = (int) config('maintenance.max_stages', 7);

        return [
            'company_id'                              => 'required|integer|exists:companies,id',
            'full_name'                               => 'required|string|max:255',
            'phone'                                   => 'required|string|max:20|regex:/^01[0-9]{9}$/',
            'governorate_id'                          => 'required|integer|exists:governorates,id',
            'city'                                    => 'required|string|max:255',
            'area'                                    => 'required|string|max:255',
            'address_details'                         => 'nullable|string|max:1000',
            'device_details'                          => 'required|string|max:2000',
            'purification_system'                     => ['required', 'string', Rule::in(MaintenanceLookups::values('purification_systems'))],
            'stages_count'                            => ['required', 'integer', Rule::in(config('maintenance.stages_counts', [1, 2, 3, 4, 5, 6, 7]))],
            'last_stage_change_dates'                 => 'required|array',
            'last_stage_change_dates.stage_1'         => 'nullable|date',
            'last_stage_change_dates.stage_2'         => 'nullable|date',
            'last_stage_change_dates.stage_3'         => 'nullable|date',
            'last_stage_change_dates.stage_4'         => 'nullable|date',
            'last_stage_change_dates.stage_5'         => 'nullable|date',
            'last_stage_change_dates.stage_6'         => 'nullable|date',
            'last_stage_change_dates.stage_7'         => 'nullable|date',
            'primary_problem_type'                    => ['required', 'string', Rule::in(MaintenanceLookups::values('primary_problem_types'))],
            'malfunction_type'                          => ['required', 'string', Rule::in(MaintenanceLookups::values('malfunction_types'))],
            'notes'                                     => 'nullable|string|max:2000',
            'image'                                     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $stagesCount = (int) $this->input('stages_count', 0);
            $dates       = $this->input('last_stage_change_dates', []);

            for ($i = 1; $i <= $stagesCount; $i++) {
                $key = "stage_{$i}";

                if (blank($dates[$key] ?? null)) {
                    $validator->errors()->add(
                        "last_stage_change_dates.{$key}",
                        "تاريخ آخر تغيير للمرحلة {$i} مطلوب."
                    );
                }
            }

            $maxStages = (int) config('maintenance.max_stages', 7);

            for ($i = $stagesCount + 1; $i <= $maxStages; $i++) {
                $key = "stage_{$i}";

                if (! blank($dates[$key] ?? null)) {
                    $validator->errors()->add(
                        "last_stage_change_dates.{$key}",
                        'لا يمكن إرسال تواريخ لمراحل أكثر من عدد المراحل المحدد.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_id.required'           => 'الشركة مطلوبة',
            'company_id.exists'             => 'الشركة غير موجودة',
            'full_name.required'            => 'اسم العميل مطلوب',
            'phone.required'                => 'رقم التليفون مطلوب',
            'phone.regex'                   => 'رقم التليفون غير صالح',
            'governorate_id.required'       => 'المحافظة مطلوبة',
            'governorate_id.exists'         => 'المحافظة غير موجودة',
            'city.required'                 => 'المدينة مطلوبة',
            'area.required'                 => 'المنطقة / الحي مطلوب',
            'device_details.required'       => 'بيانات الجهاز مطلوبة',
            'purification_system.required'  => 'نظام التنقية مطلوب',
            'purification_system.in'        => 'نظام التنقية غير صالح',
            'stages_count.required'         => 'عدد المراحل مطلوب',
            'stages_count.in'               => 'عدد المراحل غير صالح',
            'last_stage_change_dates.required' => 'تواريخ تغيير المراحل مطلوبة',
            'primary_problem_type.required' => 'نوع المشكلة الأساسية مطلوب',
            'primary_problem_type.in'       => 'نوع المشكلة الأساسية غير صالح',
            'malfunction_type.required'     => 'نوع العطل مطلوب',
            'malfunction_type.in'           => 'نوع العطل غير صالح',
            'image.image'                   => 'الملف يجب أن يكون صورة',
            'image.max'                     => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ];
    }
}
