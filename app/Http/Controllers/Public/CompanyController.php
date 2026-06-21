<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicCompanyResource;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'governorate_id' => 'required|integer|exists:governorates,id',
        ]);

        $customerId = $this->customerId();

        $companies = Company::query()
            ->with('governorate')
            ->withPublicStats($customerId)
            ->where('is_active', true)
            ->where('governorate_id', $request->governorate_id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => PublicCompanyResource::collection($companies->items()),
            'meta' => [
                'total'        => $companies->total(),
                'current_page' => $companies->currentPage(),
                'last_page'    => $companies->lastPage(),
                'per_page'     => $companies->perPage(),
            ],
        ]);
    }

    public function show(Company $company): JsonResponse
    {
        if (! $company->is_active) {
            return response()->json(['message' => 'هذه الشركة غير متاحة حاليًا'], 404);
        }

        $customerId = $this->customerId();

        $company = Company::withPublicStats($customerId)
            ->with('governorate')
            ->where('id', $company->id)
            ->firstOrFail();

        return response()->json([
            'data' => new PublicCompanyResource($company),
        ]);
    }

    private function customerId(): ?int
    {
        $user = auth('sanctum')->user();

        return $user instanceof Customer ? $user->id : null;
    }
}
