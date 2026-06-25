<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Batch;
use Carbon\Carbon;

class FefoBatchSelector
{
    /**
     * @return array<int, array{batch: Batch, quantity: int}>
     */
    public function selectForProduct(int $productId, int $quantity, ?int $variationId = null, ?int $warehouseId = null): array
    {
        $query = Batch::query()
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', today());
            });

        if ($variationId !== null) {
            $query->where('variation_id', $variationId);
        }

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Order by expiry_date ASC, NULLS LAST
        $query->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC');

        $batches = $query->get();

        if ($batches->isEmpty()) {
            return [];
        }

        $allocations = [];
        $remainingNeeded = $quantity;
        $totalAvailable = 0;

        foreach ($batches as $batch) {
            $available = $batch->remaining_quantity;
            $totalAvailable += $available;

            if ($remainingNeeded <= 0) {
                break;
            }

            $take = min($available, $remainingNeeded);
            $allocations[] = [
                'batch' => $batch,
                'quantity' => $take,
            ];
            $remainingNeeded -= $take;
        }

        if ($remainingNeeded > 0) {
            throw new InsufficientStockException(
                "Insufficient stock for product {$productId}: requested {$quantity}, available {$totalAvailable}"
            );
        }

        return $allocations;
    }
}
