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
            ->with(['company.governorate', 'governorate'])
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

        $data = $request->validated();
        $stagesCount = (int) $data['stages_count'];

        $stageDates = collect($data['last_stage_change_dates'] ?? [])
            ->only(collect(range(1, $stagesCount))->map(fn (int $i) => "stage_{$i}")->all())
            ->all();

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('maintenance', 'public');
        }

        $maintenanceRequest = $request->user()->maintenanceRequests()->create([
            'company_id'              => $company->id,
            'full_name'               => $data['full_name'],
            'phone'                   => $data['phone'],
            'governorate_id'          => $data['governorate_id'],
            'city'                    => $data['city'],
            'area'                    => $data['area'],
            'address_details'         => $data['address_details'] ?? null,
            'device_details'          => $data['device_details'],
            'purification_system'     => $data['purification_system'],
            'stages_count'            => $stagesCount,
            'last_stage_change_dates' => $stageDates,
            'primary_problem_type'    => $data['primary_problem_type'],
            'malfunction_type'        => $data['malfunction_type'],
            'notes'                   => $data['notes'] ?? null,
            'description'             => $data['device_details'],
            'address'                 => $this->buildLegacyAddress($data),
            'image'                   => $imagePath,
            'status'                  => MaintenanceRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب الصيانة بنجاح',
            'data'    => new MaintenanceRequestResource(
                $maintenanceRequest->load(['company.governorate', 'governorate'])
            ),
        ], 201);
    }

    public function show(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $this->authorizeRequest($request, $maintenanceRequest);

        return response()->json([
            'data' => new MaintenanceRequestResource(
                $maintenanceRequest->load(['company.governorate', 'governorate'])
            ),
        ]);
    }

    private function authorizeRequest(Request $request, MaintenanceRequest $maintenanceRequest): void
    {
        if ($maintenanceRequest->customer_id !== $request->user()->id) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }
    }

    /** @param array<string, mixed> $data */
    private function buildLegacyAddress(array $data): string
    {
        $parts = array_filter([
            $data['city'] ?? null,
            $data['area'] ?? null,
            $data['address_details'] ?? null,
        ]);

        return implode(' — ', $parts);
    }
}
