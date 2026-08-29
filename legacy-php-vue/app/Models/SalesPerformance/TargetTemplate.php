<?php

namespace App\Models\SalesPerformance;

use App\Enums\TargetPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'period_type', 'description', 'is_active', 'created_by'])]
class TargetTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'period_type' => TargetPeriod::class,
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TargetTemplateLine::class)->orderBy('order_index');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}