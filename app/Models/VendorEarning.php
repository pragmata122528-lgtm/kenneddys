<?php

namespace App\Models;

use Database\Factories\VendorEarningFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEarning extends Model
{
    /** @use HasFactory<VendorEarningFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'order_id',
        'order_amount',
        'commission_fee',
        'vendor_earning',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_amount' => 'decimal:2',
            'commission_fee' => 'decimal:2',
            'vendor_earning' => 'decimal:2',
        ];
    }

    /**
     * Get the vendor who earned this payout.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the order that generated this earning.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
