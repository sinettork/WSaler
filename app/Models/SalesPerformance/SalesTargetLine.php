<?php

namespace App\Models\SalesPerformance;

use App\Enums\TargetMetric;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sales_target_id', 'metric', 'target_value'])]
class SalesTargetLine extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metric' => TargetMetric::class,
            'target_value' => 'decimal:4',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class, 'sales_target_id');
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(SalesTargetAchievement::class);
    }

    public function latestAchievement()
    {
        return $this->hasOne(SalesTargetAchievement::class)->latestOfMany('snapshot_date');
    }
}