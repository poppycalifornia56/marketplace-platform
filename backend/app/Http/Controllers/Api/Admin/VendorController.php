<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('business_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
        }

        $vendors = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($vendors);
    }

    public function show(Vendor $vendor)
    {
        return response()->json($vendor->load(['user', 'products']));
    }

    public function approve(Vendor $vendor)
    {
        $vendor->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Vendor approved successfully',
            'vendor' => $vendor
        ]);
    }

    public function reject(Vendor $vendor)
    {
        $vendor->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Vendor rejected',
            'vendor' => $vendor
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'commission_rate' => 'numeric|min:0|max:100',
            'status' => 'in:pending,approved,rejected,suspended',
        ]);

        $vendor->update($request->only(['commission_rate', 'status']));

        return response()->json($vendor);
    }
}
