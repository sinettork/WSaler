<?php

namespace App\Models\SalesPerformance;

use App\Enums\TargetMetric;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['target_template_id', 'metric', 'default_value', 'order_index'])]
class TargetTemplateLine extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metric' => TargetMetric::class,
            'default_value' => 'decimal:4',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TargetTemplate::class, 'target_template_id');
    }
}