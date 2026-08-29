<?php

namespace App\Models\SalesPerformance;

use App\Enums\TargetPeriod;
use App\Enums\TargetStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'salesperson_user_id', 'period_type', 'period_start', 'period_end',
    'target_template_id', 'name', 'status', 'created_by', 'approved_by',
])]
class SalesTarget extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'period_type' => TargetPeriod::class,
            'status' => TargetStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesTargetLine::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TargetTemplate::class, 'target_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TargetStatus::Active->value);
    }
}