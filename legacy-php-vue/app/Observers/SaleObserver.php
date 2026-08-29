<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\SalesPerformance\TargetAchievementUpdater;
use Illuminate\Support\Facades\DB;

class SaleObserver
{
    public function created(Sale $sale): void
    {
        if ($sale->status === 'completed') {
            DB::transaction(function () use ($sale) {
                app(TargetAchievementUpdater::class)->applySale($sale);
            });
        }
    }

    public function updated(Sale $sale): void
    {
        if ($sale->isDirty('status')) {
            $originalStatus = $sale->getOriginal('status');
            $newStatus = $sale->status;

            if ($originalStatus === 'completed' && in_array($newStatus, ['voided', 'canceled'])) {
                DB::transaction(function () use ($sale) {
                    app(TargetAchievementUpdater::class)->reverseSale($sale);
                });
            }
        }
    }

    public function deleted(Sale $sale): void
    {
        if ($sale->status === 'completed') {
            DB::transaction(function () use ($sale) {
                app(TargetAchievementUpdater::class)->reverseSale($sale);
            });
        }
    }
}