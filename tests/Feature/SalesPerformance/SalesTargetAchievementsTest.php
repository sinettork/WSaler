<?php

namespace Tests\Feature\SalesPerformance;

use App\Enums\TargetMetric;
use App\Enums\TargetPeriod;
use App\Enums\TargetStatus;
use App\Models\Customer;
use App\Models\SalesPerformance\SalesTarget;
use App\Models\SalesPerformance\SalesTargetLine;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTargetAchievementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_sale_updates_target_achievement(): void
    {
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $target = SalesTarget::create([
            'salesperson_user_id' => $salesperson->id,
            'period_type' => TargetPeriod::Monthly->value,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'name' => 'Monthly Target',
            'status' => TargetStatus::Active->value,
            'created_by' => $salesperson->id,
        ]);

        $line = SalesTargetLine::create([
            'sales_target_id' => $target->id,
            'metric' => TargetMetric::SalesAmount->value,
            'target_value' => 10000,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-' . uniqid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $salesperson->id,
            'subtotal' => 1500,
            'discount' => 0,
            'tax' => 0,
            'total' => 1500,
            'paid' => 1500,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ]);

        $achievement = $line->fresh()->achievements()->first();
        $this->assertNotNull($achievement);
        $this->assertEquals(1500, (float) $achievement->achieved_value);
        $this->assertEquals(15.0, (float) $achievement->achievement_pct);
    }

    public function test_voiding_a_sale_reverses_target_achievement(): void
    {
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $target = SalesTarget::create([
            'salesperson_user_id' => $salesperson->id,
            'period_type' => TargetPeriod::Monthly->value,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'name' => 'Monthly Target',
            'status' => TargetStatus::Active->value,
            'created_by' => $salesperson->id,
        ]);

        $line = SalesTargetLine::create([
            'sales_target_id' => $target->id,
            'metric' => TargetMetric::SalesAmount->value,
            'target_value' => 10000,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-' . uniqid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $salesperson->id,
            'subtotal' => 1500,
            'discount' => 0,
            'tax' => 0,
            'total' => 1500,
            'paid' => 1500,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ]);

        // Verify initial achievement
        $achievement = $line->fresh()->achievements()->first();
        $this->assertEquals(1500, (float) $achievement->achieved_value);

        // Void the sale
        $sale->update(['status' => 'voided']);

        // Verify achievement reversed
        $achievement = $line->fresh()->achievements()->first();
        $this->assertEquals(0, (float) $achievement->achieved_value);
    }

    public function test_multiple_sales_accumulate_achievement(): void
    {
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $target = SalesTarget::create([
            'salesperson_user_id' => $salesperson->id,
            'period_type' => TargetPeriod::Monthly->value,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'name' => 'Monthly Target',
            'status' => TargetStatus::Active->value,
            'created_by' => $salesperson->id,
        ]);

        $line = SalesTargetLine::create([
            'sales_target_id' => $target->id,
            'metric' => TargetMetric::SalesAmount->value,
            'target_value' => 10000,
        ]);

        // First sale
        Sale::create([
            'invoice_number' => 'INV-' . uniqid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $salesperson->id,
            'subtotal' => 1500,
            'discount' => 0,
            'tax' => 0,
            'total' => 1500,
            'paid' => 1500,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ]);

        // Second sale
        Sale::create([
            'invoice_number' => 'INV-' . uniqid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $salesperson->id,
            'subtotal' => 2000,
            'discount' => 0,
            'tax' => 0,
            'total' => 2000,
            'paid' => 2000,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ]);

        $achievement = $line->fresh()->achievements()->first();
        $this->assertEquals(3500, (float) $achievement->achieved_value);
        $this->assertEquals(35.0, (float) $achievement->achievement_pct);
    }

    public function test_sale_outside_target_period_does_not_update_achievement(): void
    {
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $target = SalesTarget::create([
            'salesperson_user_id' => $salesperson->id,
            'period_type' => TargetPeriod::Monthly->value,
            'period_start' => now()->addMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->addMonth()->endOfMonth()->toDateString(),
            'name' => 'Next Month Target',
            'status' => TargetStatus::Active->value,
            'created_by' => $salesperson->id,
        ]);

        $line = SalesTargetLine::create([
            'sales_target_id' => $target->id,
            'metric' => TargetMetric::SalesAmount->value,
            'target_value' => 10000,
        ]);

        // Sale in current month, target is for next month
        Sale::create([
            'invoice_number' => 'INV-' . uniqid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $salesperson->id,
            'subtotal' => 1500,
            'discount' => 0,
            'tax' => 0,
            'total' => 1500,
            'paid' => 1500,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ]);

        $achievement = $line->fresh()->achievements()->first();
        $this->assertNull($achievement);
    }
}