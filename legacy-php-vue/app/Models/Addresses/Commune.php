<?php

namespace App\Models\Addresses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commune extends Model
{
    protected $fillable = ['code', 'district_id', 'name_en', 'name_km', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'km' ? $this->name_km : $this->name_en;
    }
}
