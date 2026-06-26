<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\UserRole;
use App\Models\SalesPerformance\Team;
use App\Models\SalesPerformance\CustomerAssignment;
use App\Models\SalesPerformance\Territory;
use App\Traits\HasPermissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'branch_id', 'warehouse_ids', 'employment_status', 'team_id', 'two_factor_secret', 'two_factor_enabled', 'password_changed_at', 'login_attempts', 'locked_until'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasPermissions;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'warehouse_ids' => 'array',
            'employment_status' => EmploymentStatus::class,
            'two_factor_enabled' => 'boolean',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'branch_id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'id', 'warehouse_ids');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function territories(): BelongsToMany
    {
        return $this->belongsToMany(Territory::class, 'territory_user')
            ->withPivot(['assigned_at', 'assigned_by', 'valid_from', 'valid_to'])
            ->withTimestamps();
    }

    public function customerAssignments(): HasMany
    {
        return $this->hasMany(CustomerAssignment::class, 'salesperson_user_id');
    }

    public function scopeSalespeople($query)
    {
        return $query->where('role', UserRole::Salesperson);
    }

    public function scopeActive($query)
    {
        return $query->where('employment_status', EmploymentStatus::Active->value);
    }
}