<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleService
{
    public function __construct(
        private BatchPickerService $batchPicker,
    ) {}

    /**
     * Atomically create a sale with all related records:
     *  - sale
     *  - sale_items (one per cart line)
     *  - sale_payments (one per payment method)
     *  - stock_movements (one per FEFO batch allocation, negative qty)
     *  - batch.remaining_quantity decrements
     *  - customer.current_balance increment for credit payments
     *
     * When a cart line references a product variation, the variation's
     * quantity_multiplier is applied to convert sold units to base units
     * for stock deduction. E.g. selling 1 of a "24-Pack" variation deducts
     * 24 base units from batches.
     *
     * Rolls back the entire transaction on any failure.
     */
    public function createSale(User $user, array $data): Sale
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. Normalise and compute line totals
            $items = collect($data['items'])->map(function (array $raw) {
                $qty = (int) $raw['quantity'];
                $price = (float) $raw['unit_price'];
                $discount = (float) ($raw['discount'] ?? 0);
                if ($qty < 1) {
                    throw new RuntimeException('Quantity must be at least 1.');
                }
                if ($price < 0) {
                    throw new RuntimeException('Unit price cannot be negative.');
                }
                return [
                    'product_id' => (int) $raw['product_id'],
                    'variation_id' => isset($raw['variation_id']) ? (int) $raw['variation_id'] : null,
                    'unit_id' => (int) $raw['unit_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount' => $discount,
                    'line_total' => round(($qty * $price) - $discount, 2),
                ];
            });

            // 2. Customer + credit check
            $customerId = $data['customer_id'] ?? null;
            $customer = $customerId ? Customer::lockForUpdate()->find($customerId) : null;
            $creditAmount = (float) collect($data['payments'])
                ->where('method', 'credit')
                ->sum('amount');

            if ($creditAmount > 0 && !$customer) {
                throw new RuntimeException('Credit payment requires a customer.');
            }
            if ($customer && $creditAmount > 0) {
                // Calculate available credit including pending draft orders
                $currentBalance = (float) $customer->current_balance;
                
                // Sum credit amounts from draft orders (pending/on-hold)
                $pendingCreditTotal = (float) DB::table('draft_orders')
                    ->join('draft_order_payments', 'draft_orders.id', '=', 'draft_order_payments.draft_order_id')
                    ->where('draft_orders.customer_id', $customer->id)
                    ->where('draft_orders.status', 'draft')
                    ->where('draft_order_payments.method', 'credit')
                    ->sum('draft_order_payments.amount');
                
                $totalUtilizedCredit = $currentBalance + $pendingCreditTotal + $creditAmount;
                $limit = (float) $customer->credit_limit;
                
                if ($limit > 0 && $totalUtilizedCredit > $limit) {
                    $remaining = max(0, $limit - $currentBalance - $pendingCreditTotal);
                    throw new RuntimeException(
                        "Credit sale exceeds customer's credit limit. Available credit: " . number_format($remaining, 2) . 
                        " (Current balance: " . number_format($currentBalance, 2) . 
                        ", Pending orders: " . number_format($pendingCreditTotal, 2) . ")"
                    );
                }
            }

            // 3. Load variation multipliers in one query
            $variationIds = $items->pluck('variation_id')->filter()->unique()->values()->all();
            $multipliersByVariation = [];
            if (!empty($variationIds)) {
                $multipliersByVariation = ProductVariation::whereIn('id', $variationIds)
                    ->pluck('quantity_multiplier', 'id')
                    ->map(fn ($v) => max(1, (int) $v))
                    ->toArray();
            }

            // 4. FEFO batch allocation per line (apply multiplier for stock deduction)
            $warehouseId = (int) $data['warehouse_id'];
            $movementSpecs = [];
            foreach ($items as $item) {
                $multiplier = $item['variation_id']
                    ? ($multipliersByVariation[$item['variation_id']] ?? 1)
                    : 1;
                $deductionQty = $item['quantity'] * $multiplier;

                $allocations = $this->batchPicker->pickBatches(
                    $item['product_id'],
                    $item['variation_id'],
                    $warehouseId,
                    $deductionQty
                );
                foreach ($allocations as $alloc) {
                    $movementSpecs[] = [
                        'batch_id' => $alloc['batch_id'],
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'],
                        'warehouse_id' => $warehouseId,
                        'type' => 'sale',
                        'quantity' => -$alloc['quantity'],
                        'multiplier_applied' => $multiplier,
                    ];
                }
            }

            $subtotal = round((float) $items->sum('line_total'), 2);
            $saleDiscount = (float) ($data['discount'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);
            $total = round($subtotal - $saleDiscount + $tax, 2);
            $paid = round((float) collect($data['payments'])->sum('amount'), 2);
            $change = round(max(0, $paid - $total), 2);

            // 5. Create sale
            $sale = Sale::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount' => $saleDiscount,
                'tax' => $tax,
                'total' => $total,
                'paid' => $paid,
                'change_due' => $change,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'sold_at' => $data['sold_at'] ?? now(),
            ]);

            // 6. Sale items
            foreach ($items as $item) {
                $sale->items()->create($item);
            }

            // 7. Sale payments
            foreach ($data['payments'] as $p) {
                $sale->payments()->create([
                    'method' => $p['method'],
                    'amount' => (float) $p['amount'],
                    'reference' => $p['reference'] ?? null,
                    'paid_at' => now(),
                ]);
            }

            // 8. Stock movements (linked to the sale)
            foreach ($movementSpecs as $spec) {
                StockMovement::create(array_merge($spec, [
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'user_id' => $user->id,
                    'occurred_at' => now(),
                ]));
            }

            // 9. Decrement batch remaining_quantity
            foreach ($movementSpecs as $spec) {
                Batch::where('id', $spec['batch_id'])
                    ->decrement('remaining_quantity', abs($spec['quantity']));
            }

            // 10. Customer credit balance
            if ($customer && $creditAmount > 0) {
                $customer->increment('current_balance', $creditAmount);
            }

            return $sale->fresh(['items.product', 'items.variation', 'items.unit', 'payments', 'customer', 'warehouse', 'user']);
        });
    }

    /**
     * Void a completed sale. Restores stock and reverses customer credit.
     * Stock movements are reversed using the original signed quantities,
     * which already encode any variation multipliers as negative base-unit values.
     */
    public function voidSale(Sale $sale, User $user, ?string $reason = null): Sale
    {
        return DB::transaction(function () use ($sale, $user, $reason) {
            if ($sale->status === 'voided') {
                throw new RuntimeException('Sale is already voided.');
            }
            if ($sale->status !== 'completed') {
                throw new RuntimeException("Cannot void a sale with status '{$sale->status}'.");
            }

            $movements = StockMovement::where('reference_type', 'sale')
                ->where('reference_id', $sale->id)
                ->lockForUpdate()
                ->get();

            foreach ($movements as $m) {
                StockMovement::create([
                    'batch_id' => $m->batch_id,
                    'product_id' => $m->product_id,
                    'variation_id' => $m->variation_id,
                    'warehouse_id' => $m->warehouse_id,
                    'type' => 'sale_void',
                    'quantity' => -$m->quantity,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'user_id' => $user->id,
                    'occurred_at' => now(),
                    'notes' => 'Reversed by void of invoice ' . $sale->invoice_number,
                ]);
                Batch::where('id', $m->batch_id)
                    ->increment('remaining_quantity', abs($m->quantity));
            }

            $creditPayments = $sale->payments()->where('method', 'credit')->get();
            if ($creditPayments->isNotEmpty() && $sale->customer_id) {
                $creditTotal = (float) $creditPayments->sum('amount');
                Customer::where('id', $sale->customer_id)
                    ->decrement('current_balance', $creditTotal);
            }

            $sale->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);

            return $sale->fresh(['items.product', 'items.variation', 'items.unit', 'payments', 'customer', 'warehouse', 'user', 'voidedBy']);
        });
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $last = Sale::withTrashed()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('invoice_number');
        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
