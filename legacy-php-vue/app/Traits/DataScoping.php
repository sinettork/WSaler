<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait DataScoping
{
    /**
     * Apply branch scoping to a query.
     */
    protected function scopeBranch(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query;
        }

        // Super admin and administrators bypass scoping
        if ($user->hasAnyRole(['super_admin', 'administrator'])) {
            return $query;
        }

        // If user has branch_id, scope to that branch
        if ($user->branch_id) {
            $table = $query->getModel()->getTable();
            $query->where("{$table}.branch_id", $user->branch_id);
        }

        return $query;
    }

    /**
     * Apply warehouse scoping to a query.
     */
    protected function scopeWarehouse(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->hasAnyRole(['super_admin', 'administrator'])) {
            return $query;
        }

        if (! empty($user->warehouse_ids)) {
            $table = $query->getModel()->getTable();
            $query->whereIn("{$table}.warehouse_id", $user->warehouse_ids);
        }

        return $query;
    }

    /**
     * Apply customer scoping to a query.
     */
    protected function scopeCustomer(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->hasAnyRole(['super_admin', 'administrator'])) {
            return $query;
        }

        if (! empty($user->customer_ids)) {
            $table = $query->getModel()->getTable();
            $query->whereIn("{$table}.customer_id", $user->customer_ids);
        }

        return $query;
    }

    /**
     * Apply all available scoping based on model relationships.
     */
    public function applyDataScoping(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query;
        }

        $model = $query->getModel();
        $table = $model->getTable();

        // Check for branch_id column
        if ($user->branch_id && $model->getConnection()->getSchemaBuilder()->hasColumn($table, 'branch_id')) {
            $query->where("{$table}.branch_id", $user->branch_id);
        }

        // Check for warehouse_id column
        if (! empty($user->warehouse_ids) && $model->getConnection()->getSchemaBuilder()->hasColumn($table, 'warehouse_id')) {
            $query->whereIn("{$table}.warehouse_id", $user->warehouse_ids);
        }

        // Check for customer_id column
        if (! empty($user->customer_ids) && $model->getConnection()->getSchemaBuilder()->hasColumn($table, 'customer_id')) {
            $query->whereIn("{$table}.customer_id", $user->customer_ids);
        }

        return $query;
    }
}
