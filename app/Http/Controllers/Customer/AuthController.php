<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\LoginCustomerRequest;
use App\Http\Requests\Customer\RegisterCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerProfileRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\Customer\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(
        RegisterCustomerRequest $request,
        CustomerService $customerService
    ): JsonResponse {
        $customer = $customerService->register($request->validated());

        $token = $customer->createToken('customer-token', ['role:customer'])->plainTextToken;

        return response()->json([
            'message'  => 'تم إنشاء الحساب بنجاح',
            'token'    => $token,
            'customer' => new CustomerResource($customer),
        ], 201);
    }

    public function login(LoginCustomerRequest $request): JsonResponse
    {
        $customer = Customer::where('phone', $request->phone)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'phone' => ['البيانات المدخلة غير صحيحة.'],
            ]);
        }

        if (! $customer->is_active) {
            return response()->json(['message' => 'حسابك موقوف. تواصل مع الإدارة.'], 403);
        }

        $token = $customer->createToken('customer-token', ['role:customer'])->plainTextToken;

        return response()->json([
            'message'  => 'تم تسجيل الدخول بنجاح',
            'token'    => $token,
            'customer' => new CustomerResource($customer->load('profile.governorate')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    public function me(Request $request): JsonResponse
    {
        $customer = $request->user()->load('profile.governorate');

        $this->authorize('viewProfile', $customer);

        return response()->json([
            'data' => new CustomerResource($customer),
        ]);
    }

    public function updateProfile(
        UpdateCustomerProfileRequest $request,
        CustomerService $customerService
    ): JsonResponse {
        $customer = $request->user();

        $this->authorize('updateProfile', $customer);

        $customer = $customerService->updateProfile($customer, $request->validated());

        return response()->json([
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data'    => new CustomerResource($customer),
        ]);
    }
}
