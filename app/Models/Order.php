<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'status', 'payment_method', 'total_amount', 'shipping_amount', 'shipping_address', 'billing_address', 'stripe_checkout_session_id', 'stripe_payment_intent_id', 'is_test'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'shipping_address' => 'array',
            'billing_address' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shippingFullName(): ?string
    {
        $a = $this->shipping_address;

        return $a ? trim(($a['first_name'] ?? '').' '.($a['last_name'] ?? '')) : null;
    }

    public function shippingStreetWithNumber(): ?string
    {
        $a = $this->shipping_address;

        return $a ? trim(($a['street'] ?? '').' '.($a['house_number'] ?? '')) : null;
    }
}
