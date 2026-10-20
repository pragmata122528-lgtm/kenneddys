<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'description',
        'image',
        'price',
        'stock_quantity',
        'total_sold',
        'is_active',
        'sort_order',
        'flat_discount',
        'percentage_discount',
    ];

    /**
     * The "booted" method of the model.
     * Automatically generates a unique slug from the product name when created.
     */
    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (empty($product->slug) && ! empty($product->name)) {
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });

        static::updating(function (Product $product): void {
            if (empty($product->slug) && ! empty($product->name)) {
                $product->slug = static::generateUniqueSlug($product->name, $product->id);
            }
        });
    }

    /**
     * Ensure any manually assigned slug is formatted cleanly.
     */
    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => ! empty($value) ? Str::slug($value) : null,
        );
    }

    /**
     * Generate a unique slug for the product, preventing duplicates.
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Use the slug for route-model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'flat_discount' => 'decimal:2',
            'stock_quantity' => 'integer',
            'total_sold' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'percentage_discount' => 'integer',
        ];
    }

    /**
     * Get the vendor that owns this product.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the order items associated with this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope query to only include active products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to only include in-stock products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Scope query for a specific vendor.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeForVendor(Builder $query, int $vendorId): Builder
    {
        return $query->where('vendor_id', $vendorId);
    }
}
