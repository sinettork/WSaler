<?php

namespace App\Services;

use App\Models\Batch;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BatchReservationService
{
    /**
     * Reserve stock for draft orders or pending transactions
     * 
     * @param array $allocations Array of ['batch_id' => quantity] pairs
     * @param string $referenceType Type of reference (e.g., 'draft_order', 'quote')
     * @param int $referenceId ID of the referencing record
     * @return void
     * @throws RuntimeException
     */
    public function reserveStock(array $allocations, string $referenceType, int $referenceId): void
    {
        DB::transaction(function () use ($allocations, $referenceType, $referenceId) {
            foreach ($allocations as $batchId => $quantity) {
                $batch = Batch::lockForUpdate()->find($batchId);

                if (!$batch) {
                    throw new RuntimeException("Batch {$batchId} not found");
                }

                $availableToReserve = $batch->remaining_quantity - ($batch->reserved_quantity ?? 0);

                if ($availableToReserve < $quantity) {
                    throw new RuntimeException(
                        "Cannot reserve {$quantity} units from batch {$batchId}. " .
                        "Only {$availableToReserve} units available for reservation."
                    );
                }

                // Increment reserved quantity
                $batch->increment('reserved_quantity', $quantity);

                // Log the reservation
                \Log::info("Stock reserved", [
                    'batch_id' => $batchId,
                    'quantity' => $quantity,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'new_reserved_quantity' => $batch->reserved_quantity + $quantity,
                ]);
            }
        });
    }

    /**
     * Release reserved stock (when draft order is cancelled or converted to sale)
     * 
     * @param array $allocations Array of ['batch_id' => quantity] pairs
     * @param string $referenceType Type of reference
     * @param int $referenceId ID of the referencing record
     * @return void
     * @throws RuntimeException
     */
    public function releaseReservation(array $allocations, string $referenceType, int $referenceId): void
    {
        DB::transaction(function () use ($allocations, $referenceType, $referenceId) {
            foreach ($allocations as $batchId => $quantity) {
                $batch = Batch::lockForUpdate()->find($batchId);

                if (!$batch) {
                    throw new RuntimeException("Batch {$batchId} not found");
                }

                if (($batch->reserved_quantity ?? 0) < $quantity) {
                    \Log::warning("Attempting to release more than reserved", [
                        'batch_id' => $batchId,
                        'requested_release' => $quantity,
                        'current_reserved' => $batch->reserved_quantity ?? 0,
                        'reference_type' => $referenceType,
                        'reference_id' => $referenceId,
                    ]);
                    
                    // Release what's actually reserved to avoid negative values
                    $quantity = $batch->reserved_quantity ?? 0;
                }

                if ($quantity > 0) {
                    // Decrement reserved quantity
                    $batch->decrement('reserved_quantity', $quantity);

                    // Log the release
                    \Log::info("Stock reservation released", [
                        'batch_id' => $batchId,
                        'quantity' => $quantity,
                        'reference_type' => $referenceType,
                        'reference_id' => $referenceId,
                        'new_reserved_quantity' => max(0, $batch->reserved_quantity - $quantity),
                    ]);
                }
            }
        });
    }

    /**
     * Convert reservation to actual sale (decrement both reserved and remaining quantities)
     * 
     * @param array $allocations Array of ['batch_id' => quantity] pairs
     * @param string $referenceType Type of reference
     * @param int $referenceId ID of the referencing record
     * @return void
     * @throws RuntimeException
     */
    public function convertReservationToSale(array $allocations, string $referenceType, int $referenceId): void
    {
        DB::transaction(function () use ($allocations, $referenceType, $referenceId) {
            foreach ($allocations as $batchId => $quantity) {
                $batch = Batch::lockForUpdate()->find($batchId);

                if (!$batch) {
                    throw new RuntimeException("Batch {$batchId} not found");
                }

                // Validate we have enough reserved and remaining stock
                if (($batch->reserved_quantity ?? 0) < $quantity) {
                    throw new RuntimeException(
                        "Cannot convert reservation for batch {$batchId}. " .
                        "Required: {$quantity}, Reserved: {$batch->reserved_quantity}"
                    );
                }

                if ($batch->remaining_quantity < $quantity) {
                    throw new RuntimeException(
                        "Cannot convert reservation for batch {$batchId}. " .
                        "Required: {$quantity}, Remaining: {$batch->remaining_quantity}"
                    );
                }

                // Decrement both reserved and remaining quantities
                $batch->decrement('reserved_quantity', $quantity);
                $batch->decrement('remaining_quantity', $quantity);

                // Log the conversion
                \Log::info("Reservation converted to sale", [
                    'batch_id' => $batchId,
                    'quantity' => $quantity,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'new_reserved_quantity' => $batch->reserved_quantity - $quantity,
                    'new_remaining_quantity' => $batch->remaining_quantity - $quantity,
                ]);
            }
        });
    }

    /**
     * Get total reserved quantity for a product across all batches
     * 
     * @param int $productId
     * @param int|null $warehouseId
     * @return int
     */
    public function getTotalReserved(int $productId, ?int $warehouseId = null): int
    {
        $query = Batch::where('product_id', $productId)
            ->where('status', 'active');

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (int) $query->sum('reserved_quantity');
    }

    /**
     * Get available quantity (remaining - reserved) for a product
     * 
     * @param int $productId
     * @param int|null $warehouseId
     * @return int
     */
    public function getAvailableQuantity(int $productId, ?int $warehouseId = null): int
    {
        $query = Batch::where('product_id', $productId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', today());
            });

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        $batches = $query->get();

        return $batches->sum(function ($batch) {
            return $batch->remaining_quantity - ($batch->reserved_quantity ?? 0);
        });
    }

    /**
     * Clean up stale reservations (for draft orders older than X days)
     * This should be run periodically via a scheduled job
     * 
     * @param int $daysOld Number of days to consider stale
     * @return int Number of reservations cleaned
     */
    public function cleanupStaleReservations(int $daysOld = 7): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        // Find draft orders older than the cutoff
        $staleDraftOrders = DB::table('draft_orders')
            ->where('status', 'draft')
            ->where('created_at', '<', $cutoffDate)
            ->pluck('id');

        if ($staleDraftOrders->isEmpty()) {
            return 0;
        }

        $cleanedCount = 0;

        foreach ($staleDraftOrders as $draftOrderId) {
            // Get batch allocations for this draft order
            $allocations = DB::table('draft_order_items')
                ->where('draft_order_id', $draftOrderId)
                ->get()
                ->pluck('quantity', 'batch_id')
                ->toArray();

            if (!empty($allocations)) {
                try {
                    $this->releaseReservation($allocations, 'draft_order', $draftOrderId);
                    $cleanedCount++;

                    \Log::info("Cleaned up stale reservation", [
                        'draft_order_id' => $draftOrderId,
                        'batches_affected' => count($allocations),
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Failed to cleanup stale reservation", [
                        'draft_order_id' => $draftOrderId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $cleanedCount;
    }
}
