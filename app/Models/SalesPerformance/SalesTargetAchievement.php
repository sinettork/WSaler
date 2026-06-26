<?php

namespace App\Models\SalesPerformance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sales_target_line_id', 'snapshot_date',
    'achieved_value', 'achievement_pct', 'computed_at',
])]
class SalesTargetAchievement extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'achieved_value' => 'decimal:4',
            'achievement_pct' => 'decimal:4',
            'computed_at' => 'datetime',
        ];
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(SalesTargetLine::class, 'sales_target_line_id');
    }
}