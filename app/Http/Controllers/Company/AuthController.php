<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'tax_number' => 'required|string',
            'password'   => 'required|string',
        ]);

        $company = Company::where('tax_number', $request->tax_number)->first();

        if (! $company || ! Hash::check($request->password, $company->password)) {
            throw ValidationException::withMessages([
                'tax_number' => ['البيانات المدخلة غير صحيحة.'],
            ]);
        }

        if (! $company->is_active) {
            return response()->json(['message' => 'حسابك موقوف. تواصل مع الإدارة.'], 403);
        }

        $token = $company->createToken('company-token', ['role:company'])->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'company' => new CompanyResource($company->load('governorate')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new CompanyResource($request->user()->load('governorate')),
        ]);
    }
}
