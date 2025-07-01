<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VendorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isVendor()) {
            return response()->json(['message' => 'Unauthorized. Vendor access required.'], 403);
        }

        if (!$request->user()->vendor || !$request->user()->vendor->isApproved()) {
            return response()->json(['message' => 'Vendor account not approved.'], 403);
        }

        return $next($request);
    }
}
