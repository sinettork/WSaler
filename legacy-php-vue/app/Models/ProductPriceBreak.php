<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'customer_type', 'min_quantity', 'max_quantity', 'unit_price'])]
class ProductPriceBreak extends Model
{
    use HasFactory;

    protected $table = 'product_price_breaks';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
