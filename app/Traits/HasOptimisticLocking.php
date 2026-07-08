<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Trait for implementing optimistic locking on Eloquent models.
 * 
 * Usage:
 * 1. Add migration to include 'version' column (unsigned integer, default 1)
 * 2. Use this trait in your model
 * 3. The version will automatically increment on each update
 * 4. Concurrent updates will throw OptimisticLockException
 * 
 * Example:
 * ```php
 * class Batch extends Model
 * {
 *     use HasOptimisticLocking;
 * }
 * ```
 */
trait HasOptimisticLocking
{
    /**
     * Boot the optimistic locking trait for a model.
     */
    protected static function bootHasOptimisticLocking(): void
    {
        static::updating(function (Model $model) {
            // Store the current version before update
            $model->_originalVersion = $model->getOriginal('version');
        });

        static::updated(function (Model $model) {
            // Check if the update actually happened (affected rows > 0)
            // If version mismatch occurred, the update would affect 0 rows
            if (!$model->wasChanged()) {
                throw new RuntimeException(
                    'Optimistic lock failed: The record was modified by another process. Please refresh and try again.'
                );
            }
        });
    }

    /**
     * Perform a model update operation with version check.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performUpdate($query)
    {
        // If the model has versioning enabled
        if ($this->usesTimestamps() && property_exists($this, '_originalVersion')) {
            $this->updateTimestamps();
        }

        // Get the dirty attributes
        $dirty = $this->getDirtyForUpdate();

        if (count($dirty) > 0) {
            // Increment the version
            $dirty['version'] = ($this->getOriginal('version') ?? 1) + 1;

            // Add WHERE clause to check version
            $originalVersion = $this->getOriginal('version') ?? 1;
            
            $affected = $this->setKeysForSaveQuery($query)
                ->where('version', $originalVersion)
                ->update($dirty);

            if ($affected === 0) {
                throw new RuntimeException(
                    'Optimistic lock failed: The record was modified by another process. Please refresh and try again.'
                );
            }

            $this->syncChanges();

            // Update the model's version to the new value
            $this->setAttribute('version', $dirty['version']);
            $this->syncOriginal();

            $this->fireModelEvent('updated', false);

            return true;
        }

        return true;
    }

    /**
     * Get the version column name.
     *
     * @return string
     */
    public function getVersionColumn(): string
    {
        return 'version';
    }

    /**
     * Get the current version of the model.
     *
     * @return int
     */
    public function getVersion(): int
    {
        return (int) $this->getAttribute($this->getVersionColumn());
    }
}
