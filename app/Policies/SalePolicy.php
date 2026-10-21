<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function view(User $user, Sale $sale): bool
    {
        return $user->isStaff() || $sale->customer_id === $user->id;
    }

    public function cancel(User $user, Sale $sale): bool
    {
        return $sale->customer_id === $user->id && $sale->status === 'pending';
    }
}