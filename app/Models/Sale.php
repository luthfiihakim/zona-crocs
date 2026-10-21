<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'number', 'customer_id', 'user_id', 'channel', 'status',
        'payment_method', 'total', 'shipping_address', 'notes', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusLabel(): string
    {
        return [
            'pending' => 'Menunggu bayar',
            'paid' => 'Lunas',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kedaluwarsa',
        ][$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return [
            'pending' => 'low',
            'paid' => 'ok',
        ][$this->status] ?? 'off';
    }

    public const PAY_WINDOW_MINUTES = 60;

    public function expiresAt()
    {
        return $this->created_at->copy()->addMinutes(self::PAY_WINDOW_MINUTES);
    }
}