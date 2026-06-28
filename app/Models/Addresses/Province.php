<?php

namespace App\Models\Addresses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = ['code', 'name_en', 'name_km', 'type', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'km' ? $this->name_km : $this->name_en;
    }
}
