<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'sale_id', 'provider', 'order_id', 'transaction_id', 'method',
        'status', 'amount', 'snap_token', 'payload', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'paid_at' => 'datetime'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}