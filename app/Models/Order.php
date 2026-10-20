<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'order_number',
        'total_amount',
        'status',
        'payment_method',
        'payment_status',
        'shipping_amount',
    ];

    /**
     * Auto-generate an order number if none was provided.
     */
    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-'.strtoupper(Str::random(10));
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the user (customer) that placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the line items associated with this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all vendor commission/earnings records tied to this order.
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(VendorEarning::class);
    }

    /**
     * Scope query for a specific buyer.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeForBuyer(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope query with eager loaded relationships to prevent N+1 queries.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWithDetails(Builder $query): Builder
    {
        return $query->with(['items.product.vendor', 'user']);
    }
}
