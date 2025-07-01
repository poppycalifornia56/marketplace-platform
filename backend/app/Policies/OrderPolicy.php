<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order)
    {
        return $user->isAdmin() ||
               $user->id === $order->user_id ||
               ($user->isVendor() && $order->items()->where('vendor_id', $user->vendor->id)->exists());
    }
}
