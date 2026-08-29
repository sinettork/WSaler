<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'sku', 'barcode', 'description', 'brand_id', 'category_id', 'base_unit_id', 'image', 'retail_price', 'wholesale_price', 'distributor_price', 'cost_price', 'status', 'track_stock'])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'retail_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'distributor_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'track_stock' => 'boolean',
        ];
    }

    /**
     * Boot method to handle model events
     */
    protected static function booted(): void
    {
        // Invalidate cache when product is updated
        static::updated(function (Product $product) {
            app(CacheService::class)->invalidateProduct($product->id);
            app(CacheService::class)->invalidateProductStock($product->id);
        });

        // Invalidate cache when product is deleted
        static::deleted(function (Product $product) {
            app(CacheService::class)->invalidateProduct($product->id);
            app(CacheService::class)->invalidateProductStock($product->id);
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function priceBreaks(): HasMany
    {
        return $this->hasMany(ProductPriceBreak::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    /**
     * Get total available stock using cache service
     * Note: This attribute should be used sparingly. For list views,
     * prefer eager loading with a dedicated query or use the cache service directly.
     */
    public function getTotalStockAttribute(): int
    {
        // Use cache service if available, fallback to direct query
        if (app()->bound(CacheService::class)) {
            return app(CacheService::class)->getProductStock($this->id);
        }

        return (int) $this->batches()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', today());
            })
            ->sum('remaining_quantity');
    }

    /**
     * Get near-expiry stock (within 90 days)
     * Cached with 5-minute TTL
     */
    public function getNearExpiryStockAttribute(): int
    {
        return (int) $this->batches()
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [today(), today()->addDays(90)])
            ->sum('remaining_quantity');
    }

    /**
     * Scope for eager loading stock counts efficiently
     * Use this in controllers instead of the accessor to avoid N+1
     */
    public function scopeWithStockCounts($query)
    {
        return $query->addSelect([
            'total_stock' => Batch::selectRaw('COALESCE(SUM(remaining_quantity), 0)')
                ->whereColumn('batches.product_id', 'products.id')
                ->where('batches.status', 'active')
                ->where(function ($q) {
                    $q->whereNull('batches.expiry_date')
                      ->orWhere('batches.expiry_date', '>', today());
                })
        ]);
    }

    /**
     * Scope for filtering active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for searching products by name, SKU, or barcode
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%");
        });
    }
}
