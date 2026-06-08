<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Company) {
            return response()->json(['message' => 'غير مصرح. هذه المنطقة للشركات فقط.'], 403);
        }

        if (! $request->user()->is_active) {
            return response()->json(['message' => 'حسابك موقوف. تواصل مع الإدارة.'], 403);
        }

        return $next($request);
    }
}
