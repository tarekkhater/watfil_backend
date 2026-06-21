<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreMaintenanceRequestRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\Company;
use App\Models\MaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requests = $request->user()
            ->maintenanceRequests()
            ->with('company.governorate')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => MaintenanceRequestResource::collection($requests->items()),
            'meta' => [
                'total'        => $requests->total(),
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'per_page'     => $requests->perPage(),
            ],
        ]);
    }

    public function store(StoreMaintenanceRequestRequest $request): JsonResponse
    {
        $company = Company::findOrFail($request->company_id);

        if (! $company->is_active) {
            return response()->json(['message' => 'هذه الشركة غير متاحة حاليًا'], 422);
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('maintenance', 'public');
        }

        $maintenanceRequest = $request->user()->maintenanceRequests()->create([
            'company_id'  => $company->id,
            'description' => $request->description,
            'address'     => $request->address,
            'image'       => $imagePath,
            'status'      => MaintenanceRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب الصيانة بنجاح',
            'data'    => new MaintenanceRequestResource($maintenanceRequest->load('company.governorate')),
        ], 201);
    }

    public function show(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $this->authorizeRequest($request, $maintenanceRequest);

        return response()->json([
            'data' => new MaintenanceRequestResource($maintenanceRequest->load('company.governorate')),
        ]);
    }

    private function authorizeRequest(Request $request, MaintenanceRequest $maintenanceRequest): void
    {
        if ($maintenanceRequest->customer_id !== $request->user()->id) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }
    }
}
