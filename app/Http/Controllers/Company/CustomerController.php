<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\ListCustomersRequest;
use App\Http\Resources\CustomerCompanyLinkResource;
use App\Models\CustomerCompanyLink;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function index(ListCustomersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', CustomerCompanyLink::class);

        $company = $request->user();

        $links = CustomerCompanyLink::query()
            ->with(['customer.profile.governorate'])
            ->where('company_id', $company->id)
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->string('search')->toString();

                    $query->whereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery
                            ->where('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('profile', fn ($profileQuery) => $profileQuery->where('full_name', 'like', "%{$search}%"));
                    });
                }
            )
            ->latest('linked_at')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'data' => CustomerCompanyLinkResource::collection($links->items()),
            'meta' => [
                'total'        => $links->total(),
                'current_page' => $links->currentPage(),
                'last_page'    => $links->lastPage(),
                'per_page'     => $links->perPage(),
            ],
        ]);
    }
}
