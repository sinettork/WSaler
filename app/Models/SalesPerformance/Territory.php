<?php

namespace App\Models\SalesPerformance;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'region', 'description', 'is_active'])]
class Territory extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function salespeople(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'territory_user')
            ->withPivot(['assigned_at', 'assigned_by', 'valid_from', 'valid_to'])
            ->withTimestamps();
    }
}