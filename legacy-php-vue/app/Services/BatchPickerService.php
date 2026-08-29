<?php

namespace App\Services;

use App\Models\Batch;
use RuntimeException;

class BatchPickerService
{
    /**
     * Pick batches in FEFO (First-Expiry-First-Out) order for a product.
     *
     * Batches are ordered by:
     *   1. expiry_date ASC (NULLs last — non-perishable first), then
     *   2. received_date ASC, then
     *   3. id ASC (tiebreaker)
     *
     * Returns an array of allocations: [['batch_id' => int, 'quantity' => int], ...]
     * that sum to $quantity. Throws if available stock is insufficient.
     *
     * @return array<int, array{batch_id:int, quantity:int}>
     */
    public function pickBatches(int $productId, ?int $variationId, int $warehouseId, int $quantity): array
    {
        $batches = Batch::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0)
            ->when($variationId, fn ($q) => $q->where('variation_id', $variationId))
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->orderBy('received_date', 'ASC')
            ->orderBy('id', 'ASC')
            ->lockForUpdate()
            ->get();

        $allocations = [];
        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, (int) $batch->remaining_quantity);
            if ($take <= 0) {
                continue;
            }
            $allocations[] = [
                'batch_id' => (int) $batch->id,
                'quantity' => $take,
            ];
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new RuntimeException(
                "Insufficient stock for product #{$productId}: short by {$remaining} units."
            );
        }

        return $allocations;
    }
}
