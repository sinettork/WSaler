<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id', 'name', 'value', 'sku_suffix', 'barcode',
    'additional_price', 'quantity_multiplier', 'is_active',
])]
class ProductVariation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'additional_price' => 'decimal:2',
            'quantity_multiplier' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
