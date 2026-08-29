<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['batch_number', 'product_id', 'variation_id', 'warehouse_id', 'supplier_id', 'quantity', 'remaining_quantity', 'reserved_quantity', 'purchase_cost', 'manufacture_date', 'expiry_date', 'received_date', 'notes', 'status'])]
class Batch extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'remaining_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'purchase_cost' => 'decimal:4',
            'manufacture_date' => 'date',
            'expiry_date' => 'date',
            'received_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isBefore(today());
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if ($this->expiry_date === null) {
            return null;
        }

        return (int) today()->diffInDays($this->expiry_date, false);
    }

    public function getExpiryStatusAttribute(): string
    {
        if ($this->is_expired) {
            return 'expired';
        }

        $days = $this->days_until_expiry;

        if ($days === null) {
            return 'good';
        }

        if ($days <= 7) {
            return 'critical';
        }

        if ($days <= 30) {
            return 'warning';
        }

        if ($days <= 90) {
            return 'notice';
        }

        return 'good';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringWithin($query, int $days)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', today()->addDays($days))
            ->where('expiry_date', '>=', today());
    }

    public function scopeForProduct($query, int $id)
    {
        return $query->where('product_id', $id);
    }

    public function scopeForWarehouse($query, int $id)
    {
        return $query->where('warehouse_id', $id);
    }
}
