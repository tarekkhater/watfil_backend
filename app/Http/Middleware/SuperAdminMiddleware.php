<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SuperAdmin;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof SuperAdmin) {
            return response()->json(['message' => 'غير مصرح. هذه المنطقة للسوبر أدمن فقط.'], 403);
        }

        return $next($request);
    }
}
