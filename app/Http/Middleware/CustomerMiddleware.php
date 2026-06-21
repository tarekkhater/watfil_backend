<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Customer) {
            return response()->json(['message' => 'غير مصرح. هذه المنطقة للعملاء فقط.'], 403);
        }

        return $next($request);
    }
}
