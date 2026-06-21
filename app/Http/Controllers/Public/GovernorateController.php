<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\GovernorateResource;
use App\Models\Governorate;
use Illuminate\Http\JsonResponse;

class GovernorateController extends Controller
{
    public function index(): JsonResponse
    {
        $governorates = Governorate::orderBy('name_ar')->get();

        return response()->json([
            'data' => GovernorateResource::collection($governorates),
        ]);
    }
}
