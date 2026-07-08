<?php

namespace Tests\Unit\SalesPerformance;

use App\Enums\TargetMetric;
use App\Enums\TargetPeriod;
use App\Enums\TargetStatus;
use App\Models\Customer;
use App\Models\SalesPerformance\SalesTarget;
use App\Models\SalesPerformance\SalesTargetLine;
use App\Models\User;
use App\Services\SalesPerformance\TargetAchievementUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetAchievementUpdaterTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_sale_increments_sales_amount_achievement(): void
    {
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);
        $customer = Customer::factory()->create();

        $target = SalesTarget::create([
            'salesperson_user_id' => $salesperson->id,
            'period_type' => TargetPeriod::Monthly->value,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'name' => 'Test Target',
            'status' => TargetStatus::Active->value,
            'created_by' => $salesperson->id,
        ]);

        $line = SalesTargetLine::create([
            'sales_target_id' => $target->id,
            'metric' => TargetMetric::SalesAmount->value,
            'target_value' => 10000,
        ]);

        $sale = $this->makeSale($salesperson, $customer, ['total' => 1500]);

        // Disable observers to test service in isolation
        \App\Models\Sale::withoutEvents(function () use ($sale) {
            app(TargetAchievementUpdater::class)->applySale($sale);
        });

        $achievement = $line->achievements()->first();
        $this->assertNotNull($achievement);
        $this->assertEquals(1500, (float) $achievement->achieved_value);
        $this->assertEquals(15.0, (float) $achievement->achievement_pct);
    }

    public function test_reverse_sale_decrements_achievement(): void
    {
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);
        $customer = Customer::factory()->create();

        $target = SalesTarget::create([
            'salesperson_user_id' => $salesperson->id,
            'period_type' => TargetPeriod::Monthly->value,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'name' => 'Test Target',
            'status' => TargetStatus::Active->value,
            'created_by' => $salesperson->id,
        ]);

        $line = SalesTargetLine::create([
            'sales_target_id' => $target->id,
            'metric' => TargetMetric::SalesAmount->value,
            'target_value' => 10000,
        ]);

        $sale = $this->makeSale($salesperson, $customer, ['total' => 1500]);

        \App\Models\Sale::withoutEvents(function () use ($sale) {
            app(TargetAchievementUpdater::class)->applySale($sale);
            app(TargetAchievementUpdater::class)->reverseSale($sale);
        });

        $achievement = $line->achievements()->first();
        $this->assertEquals(0, (float) $achievement->achieved_value);
    }

    private function makeSale(User $salesperson, Customer $customer, array $attrs = []): \App\Models\Sale
    {
        $warehouse = \App\Models\Warehouse::factory()->create();
        return \App\Models\Sale::withoutEvents(function () use ($salesperson, $customer, $warehouse, $attrs) {
            return \App\Models\Sale::create(array_merge([
                'invoice_number' => 'INV-' . uniqid(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $salesperson->id,
                'subtotal' => 1000,
                'discount' => 0,
                'tax' => 0,
                'total' => 1000,
                'paid' => 1000,
                'change_due' => 0,
                'status' => 'completed',
                'sold_at' => now(),
            ], $attrs));
        });
    }
}