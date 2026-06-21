<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MaintenanceLookupController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'purification_systems'  => config('maintenance.purification_systems', []),
                'stages_counts'       => config('maintenance.stages_counts', []),
                'primary_problem_types' => config('maintenance.primary_problem_types', []),
                'malfunction_types'     => config('maintenance.malfunction_types', []),
                'max_stages'            => config('maintenance.max_stages', 7),
            ],
        ]);
    }
}
