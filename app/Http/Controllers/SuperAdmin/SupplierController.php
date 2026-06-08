<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreSupplierRequest;
use App\Http\Requests\SuperAdmin\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SupplierController extends Controller
{
    public function index(): JsonResponse
    {
        $suppliers = Supplier::withCount('products')->latest()->paginate(15);

        return response()->json([
            'data' => SupplierResource::collection($suppliers->items()),
            'meta' => [
                'total'        => $suppliers->total(),
                'current_page' => $suppliers->currentPage(),
                'last_page'    => $suppliers->lastPage(),
                'per_page'     => $suppliers->perPage(),
            ],
        ]);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos/suppliers', 'public');
        }

        $supplier = Supplier::create($data);

        return response()->json([
            'message' => 'تم إنشاء المورد بنجاح',
            'data'    => new SupplierResource($supplier),
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json([
            'data' => new SupplierResource($supplier->loadCount('products')),
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($supplier->logo) {
                Storage::disk('public')->delete($supplier->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos/suppliers', 'public');
        }

        $supplier->update($data);

        return response()->json([
            'message' => 'تم تحديث المورد بنجاح',
            'data'    => new SupplierResource($supplier->fresh()),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($supplier->logo) {
            Storage::disk('public')->delete($supplier->logo);
        }

        $supplier->delete();

        return response()->json(['message' => 'تم حذف المورد بنجاح']);
    }
}
