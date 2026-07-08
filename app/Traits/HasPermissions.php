<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasPermissions
{
    /**
     * A model may have multiple roles.
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles')
            ->withTimestamps();
    }

    /**
     * A model may have multiple direct permissions.
     */
    public function permissions(): MorphToMany
    {
        return $this->morphToMany(Permission::class, 'model', 'model_has_permissions')
            ->withTimestamps();
    }

    /**
     * Assign a role to the model.
     */
    public function assignRole(string|array|Role $roles): void
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $role = Role::where('name', $role)->firstOrFail();
            }
            if (! $this->hasRole($role->name)) {
                $this->roles()->save($role);
            }
        }
    }

    /**
     * Remove a role from the model.
     */
    public function removeRole(string|array|Role $roles): void
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $role = Role::where('name', $role)->first();
            }
            if ($role) {
                $this->roles()->detach($role->id);
            }
        }
    }

    /**
     * Check if the model has a role.
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles()
            ->whereIn('name', $roles)
            ->exists();
    }

    /**
     * Check if the model has any of the given roles.
     */
    public function hasAnyRole(string|array $roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Check if the model has all of the given roles.
     */
    public function hasAllRoles(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles()
            ->whereIn('name', $roles)
            ->count() === count($roles);
    }

    /**
     * Assign a permission directly to the model.
     */
    public function givePermissionTo(string|array|Permission $permissions): void
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = Permission::where('name', $permission)->firstOrFail();
            }
            if (! $this->hasPermissionTo($permission->name)) {
                $this->permissions()->save($permission);
            }
        }
    }

    /**
     * Remove a permission from the model.
     */
    public function revokePermissionTo(string|array|Permission $permissions): void
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = Permission::where('name', $permission)->first();
            }
            if ($permission) {
                $this->permissions()->detach($permission->id);
            }
        }
    }

    /**
     * Check if the model has a specific permission (direct or via role).
     */
    public function hasPermissionTo(string|array $permissions): bool
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        // Check direct permissions
        if ($this->permissions()
            ->whereIn('name', $permissions)
            ->exists()) {
            return true;
        }

        // Check role permissions
        $rolePermissions = $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap->permissions
            ->pluck('name')
            ->unique();

        return collect($permissions)->intersect($rolePermissions)->isNotEmpty();
    }

    /**
     * Sync permissions for the model.
     */
    public function syncPermissions(string|array $permissions): void
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Sync roles for the model.
     */
    public function syncRoles(string|array $roles): void
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $roleIds = Role::whereIn('name', $roles)->pluck('id');
        $this->roles()->sync($roleIds);
    }

    /**
     * Get all permissions for the model (direct + via roles).
     */
    public function getAllPermissions(): array
    {
        $direct = $this->permissions->pluck('name')->toArray();
        $viaRoles = $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap->permissions
            ->pluck('name')
            ->unique()
            ->toArray();

        return array_unique(array_merge($direct, $viaRoles));
    }

    /**
     * Check if the model can access a resource based on branch/warehouse scoping.
     */
    public function canAccessBranch(int $branchId): bool
    {
        if (! property_exists($this, 'branch_id')) {
            return true; // No scoping configured
        }
        return $this->branch_id === $branchId;
    }

    public function canAccessWarehouse(int $warehouseId): bool
    {
        if (! property_exists($this, 'warehouse_ids') || empty($this->warehouse_ids)) {
            return true; // No scoping configured
        }
        return in_array($warehouseId, $this->warehouse_ids);
    }

    public function canAccessCustomer(int $customerId): bool
    {
        if (! property_exists($this, 'customer_ids') || empty($this->customer_ids)) {
            return true; // No scoping configured
        }
        return in_array($customerId, $this->customer_ids);
    }
}
