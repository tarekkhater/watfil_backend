<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCompanyRatingRequest;
use App\Http\Resources\PublicCompanyResource;
use App\Models\Company;
use App\Models\CompanyLike;
use App\Models\CompanyRating;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyEngagementController extends Controller
{
    public function like(Request $request, Company $company): JsonResponse
    {
        $this->ensureActiveCompany($company);

        $customer = $request->user();

        $exists = CompanyLike::where('customer_id', $customer->id)
            ->where('company_id', $company->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'أعجبت بهذه الشركة بالفعل'], 422);
        }

        CompanyLike::create([
            'customer_id' => $customer->id,
            'company_id'  => $company->id,
        ]);

        return response()->json([
            'message' => 'تم تسجيل الإعجاب بنجاح',
            'data'    => $this->companyWithStats($company, $customer),
        ], 201);
    }

    public function unlike(Request $request, Company $company): JsonResponse
    {
        $this->ensureActiveCompany($company);

        $customer = $request->user();

        $deleted = CompanyLike::where('customer_id', $customer->id)
            ->where('company_id', $company->id)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'لم تعجب بهذه الشركة من قبل'], 422);
        }

        return response()->json([
            'message' => 'تم إلغاء الإعجاب بنجاح',
            'data'    => $this->companyWithStats($company->fresh(), $customer),
        ]);
    }

    public function rate(StoreCompanyRatingRequest $request, Company $company): JsonResponse
    {
        $this->ensureActiveCompany($company);

        $customer = $request->user();

        CompanyRating::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'company_id'  => $company->id,
            ],
            [
                'rating'  => $request->rating,
                'comment' => $request->comment,
            ]
        );

        return response()->json([
            'message' => 'تم حفظ التقييم بنجاح',
            'data'    => $this->companyWithStats($company->fresh(), $customer),
        ]);
    }

    public function removeRating(Request $request, Company $company): JsonResponse
    {
        $this->ensureActiveCompany($company);

        $customer = $request->user();

        $deleted = CompanyRating::where('customer_id', $customer->id)
            ->where('company_id', $company->id)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'لم تقيّم هذه الشركة من قبل'], 422);
        }

        return response()->json([
            'message' => 'تم حذف التقييم بنجاح',
            'data'    => $this->companyWithStats($company->fresh(), $customer),
        ]);
    }

    private function ensureActiveCompany(Company $company): void
    {
        if (! $company->is_active) {
            abort(404, 'هذه الشركة غير متاحة حاليًا');
        }
    }

    private function companyWithStats(Company $company, Customer $customer): PublicCompanyResource
    {
        $company = Company::withPublicStats($customer->id)
            ->with('governorate')
            ->where('id', $company->id)
            ->firstOrFail();

        return new PublicCompanyResource($company);
    }
}
