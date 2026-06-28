<?php

namespace App\Models;

use App\Models\Addresses\Commune;
use App\Models\Addresses\District;
use App\Models\Addresses\Province;
use App\Models\Addresses\Village;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'contact_person', 'email', 'phone', 'address', 'province_id', 'district_id', 'commune_id', 'village_id', 'type', 'credit_limit', 'current_balance', 'payment_terms', 'notes', 'is_active'])]
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
            'current_balance' => 'decimal:2',
        ];
    }

    protected function getTypeAttribute(): string
    {
        return $this->attributes['type'] ?? 'retail';
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
