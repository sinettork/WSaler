<?php

namespace App\Services\SalesPerformance;

use App\Enums\TargetMetric;
use App\Enums\TargetStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SalesPerformance\SalesTarget;
use App\Models\SalesPerformance\SalesTargetAchievement;
use App\Models\SalesPerformance\SalesTargetLine;
use Illuminate\Support\Facades\DB;

class TargetAchievementUpdater implements TargetAchievementUpdaterInterface
{
    public function applySale(Sale $sale): void
    {
        $this->applyOrReverse($sale, reverse: false);
    }

    public function reverseSale(Sale $sale): void
    {
        $this->applyOrReverse($sale, reverse: true);
    }

    private function applyOrReverse(Sale $sale, bool $reverse): void
    {
        $targets = SalesTarget::active()
            ->where('salesperson_user_id', $sale->user_id)
            ->where('period_start', '<=', $sale->sold_at)
            ->where('period_end', '>=', $sale->sold_at)
            ->get();

        if ($targets->isEmpty()) {
            return;
        }

        $contributions = $this->contributions($sale);
        $snapshotDate = $sale->sold_at->format('Y-m-d');

        DB::transaction(function () use ($targets, $contributions, $snapshotDate, $reverse) {
            foreach ($targets as $target) {
                foreach ($target->lines as $line) {
                    $metricValue = $line->metric->value;
                    if (! isset($contributions[$metricValue])) {
                        continue;
                    }

                    $delta = $reverse ? -$contributions[$metricValue] : $contributions[$metricValue];

                    // Use whereDate for proper date comparison (stored value may have time component)
                    $achievement = SalesTargetAchievement::where('sales_target_line_id', $line->id)
                        ->whereDate('snapshot_date', $snapshotDate)
                        ->first();

                    if (! $achievement) {
                        $achievement = new SalesTargetAchievement([
                            'sales_target_line_id' => $line->id,
                            'snapshot_date' => $snapshotDate,
                        ]);
                    }

                    $newValue = (float) ($achievement->achieved_value ?? 0) + $delta;
                    $achievement->achieved_value = $newValue;
                    $achievement->achievement_pct = $line->target_value > 0
                        ? ($newValue / (float) $line->target_value) * 100
                        : 0;
                    $achievement->computed_at = now();
                    $achievement->save();
                }
            }
        });
    }

    private function contributions(Sale $sale): array
    {
        $customer = $sale->customer;

        return [
            TargetMetric::SalesAmount->value => (float) $sale->total,
            TargetMetric::InvoiceCount->value => 1.0,
            TargetMetric::CustomerCount->value => $customer ? 1.0 : 0.0,
            TargetMetric::Quantity->value => (float) $sale->items->sum('quantity'),
            TargetMetric::GrossProfit->value => (float) $sale->items->sum(function ($item) {
                $cost = $item->cost ?? 0;
                return ($item->price - $cost) * $item->quantity;
            }),
            TargetMetric::CollectionAmount->value => (float) $sale->payments()
                ->where('status', 'completed')
                ->sum('amount'),
            TargetMetric::NewCustomerCount->value => $this->isNewCustomer($customer, $sale) ? 1.0 : 0.0,
        ];
    }

    private function isNewCustomer(?Customer $customer, Sale $sale): bool
    {
        if (! $customer) {
            return false;
        }
        // Use start of month as the period boundary for new customer check
        if ($customer->created_at < $sale->sold_at->copy()->startOfMonth()) {
            return false;
        }
        $previousSales = Sale::where('customer_id', $customer->id)
            ->where('user_id', $sale->user_id)
            ->where('id', '!=', $sale->id)
            ->where('sold_at', '<', $sale->sold_at)
            ->exists();
        return ! $previousSales;
    }
}