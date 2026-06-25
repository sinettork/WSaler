<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'short_code', 'base', 'conversion_factor_to_base'])]
class Unit extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'base' => 'boolean',
            'conversion_factor_to_base' => 'decimal:4',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Unit $unit) {
            if ($unit->base) {
                $unit->conversion_factor_to_base = 1;
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'base_unit_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->name} ({$this->short_code})";
    }
}
