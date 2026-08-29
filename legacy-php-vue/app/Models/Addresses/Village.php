<?php

namespace App\Models\Addresses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Village extends Model
{
    protected $fillable = ['code', 'commune_id', 'name_en', 'name_km', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'km' ? $this->name_km : $this->name_en;
    }
}
