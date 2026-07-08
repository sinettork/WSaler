<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    public function indexPurchaseReceipts(Request $request)
    {
        $query = PurchaseReceipt::query()->with(['supplier', 'warehouse', 'user', 'purchaseOrder'])->latest('received_at');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', $search)
                    ->orWhereHas('supplier', fn($supplierQuery) => $supplierQuery->where('name', 'like', $search));
            });
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    public function indexRefunds(Request $request)
    {
        $query = Refund::query()->with(['customer', 'warehouse', 'user'])->latest('refunded_at');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', $search)
                    ->orWhereHas('customer', fn($customerQuery) => $customerQuery->where('name', 'like', $search));
            });
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    public function indexStockTransfers(Request $request)
    {
        $query = StockTransfer::query()->with(['sourceWarehouse', 'destinationWarehouse', 'user'])->latest('transferred_at');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where('reference_number', 'like', $search);
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    public function indexStockAdjustments(Request $request)
    {
        $query = StockAdjustment::query()->with(['warehouse', 'user'])->latest('adjusted_at');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', $search)
                    ->orWhere('reason', 'like', $search);
            });
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    // Purchase Orders
    public function indexPurchaseOrders(Request $request)
    {
        $query = PurchaseOrder::query()->with(['supplier', 'warehouse', 'user', 'items.product', 'items.variation'])->latest('order_date');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', $search)
                    ->orWhereHas('supplier', fn($supplierQuery) => $supplierQuery->where('name', 'like', $search));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    public function showPurchaseOrder(Request $request, PurchaseOrder $purchaseOrder)
    {
        return response()->json(['data' => $purchaseOrder->load(['supplier', 'warehouse', 'user', 'items.product', 'items.variation', 'receipts', 'payments', 'approval'])]);
    }

    public function storePurchaseOrder(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variation_id' => ['nullable', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $purchaseOrder = DB::transaction(function () use ($data, $request) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += (float) $item['quantity'] * (float) $item['unit_cost'];
            }

            $taxAmount = (float) ($data['tax_amount'] ?? 0);
            $discountAmount = (float) ($data['discount_amount'] ?? 0);
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => $request->user()->id,
                'reference_number' => $data['reference_number'] ?? 'PO-' . Str::upper(Str::random(6)),
                'status' => 'draft',
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ]);

            foreach ($data['items'] as $item) {
                $purchaseOrder->items()->create([
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => (float) $item['quantity'] * (float) $item['unit_cost'],
                    'received_quantity' => 0,
                ]);
            }

            return $purchaseOrder->load('items.product', 'items.variation', 'supplier', 'warehouse', 'user');
        });

        return response()->json(['data' => $purchaseOrder], 201);
    }

    public function updatePurchaseOrder(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return response()->json(['message' => 'Cannot edit purchase order in current status'], 422);
        }

        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'order_date' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variation_id' => ['nullable', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $purchaseOrder = DB::transaction(function () use ($data, $purchaseOrder) {
            if (isset($data['items'])) {
                $purchaseOrder->items()->delete();
                $subtotal = 0;
                foreach ($data['items'] as $item) {
                    $subtotal += (float) $item['quantity'] * (float) $item['unit_cost'];
                    $purchaseOrder->items()->create([
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'line_total' => (float) $item['quantity'] * (float) $item['unit_cost'],
                        'received_quantity' => 0,
                    ]);
                }
                $taxAmount = (float) ($data['tax_amount'] ?? 0);
                $discountAmount = (float) ($data['discount_amount'] ?? 0);
                $totalAmount = $subtotal + $taxAmount - $discountAmount;
                $purchaseOrder->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                ]);
            }

            $purchaseOrder->update($data);

            return $purchaseOrder->load('items.product', 'items.variation', 'supplier', 'warehouse', 'user');
        });

        return response()->json(['data' => $purchaseOrder]);
    }

    public function submitForApproval(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft purchase orders can be submitted for approval'], 422);
        }

        if ($purchaseOrder->items->isEmpty()) {
            return response()->json(['message' => 'Cannot submit empty purchase order for approval'], 422);
        }

        // Check if approval is required based on amount
        $approvalThreshold = config('approval.purchase_order_threshold', 10000);
        $requiredLevel = $purchaseOrder->total_amount >= $approvalThreshold ? 'manager' : 'supervisor';

        $purchaseOrder->update(['status' => 'pending']);

        $approval = $purchaseOrder->approval()->create([
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $purchaseOrder->id,
            'requested_by' => $request->user()->id,
            'approver_id' => null, // Will be assigned by approval service
            'status' => 'pending',
            'required_level' => $requiredLevel,
            'metadata' => [
                'total_amount' => $purchaseOrder->total_amount,
                'supplier' => $purchaseOrder->supplier->name,
            ],
        ]);

        return response()->json(['data' => $purchaseOrder->load('approval')]);
    }

    public function convertToReceipt(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['approved', 'ordered', 'partially_received'])) {
            return response()->json(['message' => 'Purchase order must be approved or ordered to create receipt'], 422);
        }

        $data = $request->validate([
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $receipt = DB::transaction(function () use ($data, $request, $purchaseOrder) {
            $receipt = PurchaseReceipt::create([
                'supplier_id' => $purchaseOrder->supplier_id,
                'warehouse_id' => $purchaseOrder->warehouse_id,
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => $request->user()->id,
                'reference_number' => 'PR-' . Str::upper(Str::random(6)),
                'status' => 'completed',
                'received_at' => $data['received_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            $allFullyReceived = true;

            foreach ($data['items'] as $item) {
                $poItem = PurchaseOrderItem::findOrFail($item['purchase_order_item_id']);

                if ($poItem->purchase_order_id !== $purchaseOrder->id) {
                    throw new \RuntimeException('Item does not belong to this purchase order');
                }

                $remainingToReceive = $poItem->quantity - $poItem->received_quantity;
                if ($item['quantity'] > $remainingToReceive) {
                    throw new \RuntimeException("Cannot receive more than ordered for product {$poItem->product->name}");
                }

                $receipt->items()->create([
                    'product_id' => $poItem->product_id,
                    'variation_id' => $poItem->variation_id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $poItem->unit_cost,
                    'line_total' => (float) $item['quantity'] * (float) $poItem->unit_cost,
                ]);

                $batch = Batch::create([
                    'batch_number' => $this->generateBatchNumber(),
                    'product_id' => $poItem->product_id,
                    'variation_id' => $poItem->variation_id,
                    'warehouse_id' => $purchaseOrder->warehouse_id,
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'quantity' => $item['quantity'],
                    'remaining_quantity' => $item['quantity'],
                    'purchase_cost' => $poItem->unit_cost,
                    'received_date' => now()->toDateString(),
                    'status' => 'active',
                    'notes' => 'Receipt ' . $receipt->reference_number . ' (PO: ' . $purchaseOrder->reference_number . ')',
                ]);

                StockMovement::create([
                    'batch_id' => $batch->id,
                    'product_id' => $poItem->product_id,
                    'variation_id' => $poItem->variation_id,
                    'warehouse_id' => $purchaseOrder->warehouse_id,
                    'type' => 'stock_in',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $poItem->unit_cost,
                    'reference_type' => PurchaseReceipt::class,
                    'reference_id' => $receipt->id,
                    'notes' => 'Purchase receipt from PO ' . $purchaseOrder->reference_number,
                    'user_id' => $request->user()->id,
                    'occurred_at' => $receipt->received_at,
                ]);

                $poItem->increment('received_quantity', $item['quantity']);

                if (!$poItem->is_fully_received) {
                    $allFullyReceived = false;
                }
            }

            $purchaseOrder->update([
                'status' => $allFullyReceived ? 'received' : 'partially_received',
            ]);

            return $receipt->load('items.product', 'supplier', 'warehouse', 'user', 'purchaseOrder');
        });

        return response()->json(['data' => $receipt], 201);
    }

    public function destroyPurchaseOrder(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['draft', 'cancelled'])) {
            return response()->json(['message' => 'Cannot delete purchase order in current status'], 422);
        }

        $purchaseOrder->delete();

        return response()->json(['message' => 'Purchase order deleted']);
    }

    // Supplier Payments
    public function indexSupplierPayments(Request $request)
    {
        $query = SupplierPayment::query()->with(['supplier', 'purchaseOrder', 'purchaseReceipt', 'user'])->latest('payment_date');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', $search)
                    ->orWhereHas('supplier', fn($supplierQuery) => $supplierQuery->where('name', 'like', $search));
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    public function showSupplierPayment(Request $request, SupplierPayment $supplierPayment)
    {
        return response()->json(['data' => $supplierPayment->load(['supplier', 'purchaseOrder', 'purchaseReceipt', 'user'])]);
    }

    public function storeSupplierPayment(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'purchase_receipt_id' => ['nullable', 'exists:purchase_receipts,id'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bank_transfer,check,card,mobile_money,other'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,completed,failed,refunded'],
        ]);

        $payment = DB::transaction(function () use ($data, $request) {
            $payment = SupplierPayment::create([
                'supplier_id' => $data['supplier_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'purchase_receipt_id' => $data['purchase_receipt_id'] ?? null,
                'user_id' => $request->user()->id,
                'reference_number' => $data['reference_number'] ?? 'SP-' . Str::upper(Str::random(6)),
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'completed',
            ]);

            return $payment->load('supplier', 'purchaseOrder', 'purchaseReceipt', 'user');
        });

        return response()->json(['data' => $payment], 201);
    }

    public function updateSupplierPayment(Request $request, SupplierPayment $supplierPayment)
    {
        if ($supplierPayment->status !== 'pending') {
            return response()->json(['message' => 'Can only edit pending payments'], 422);
        }

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,check,card,mobile_money,other'],
            'payment_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,completed,failed,refunded'],
        ]);

        $supplierPayment->update($data);

        return response()->json(['data' => $supplierPayment->load('supplier', 'purchaseOrder', 'purchaseReceipt', 'user')]);
    }

    public function destroySupplierPayment(Request $request, SupplierPayment $supplierPayment)
    {
        if ($supplierPayment->status !== 'pending') {
            return response()->json(['message' => 'Can only delete pending payments'], 422);
        }

        $supplierPayment->delete();

        return response()->json(['message' => 'Payment deleted']);
    }

    public function storePurchaseReceipt(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variation_id' => ['nullable', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric'],
        ]);

        $receipt = DB::transaction(function () use ($data, $request) {
            $receipt = PurchaseReceipt::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => $request->user()->id,
                'reference_number' => $data['reference_number'] ?? 'PR-' . Str::upper(Str::random(6)),
                'status' => 'completed',
                'received_at' => $data['received_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $receiptItem = $receipt->items()->create([
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => (float) $item['quantity'] * (float) $item['unit_cost'],
                ]);

                $batch = Batch::create([
                    'batch_number' => $this->generateBatchNumber(),
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'warehouse_id' => $data['warehouse_id'],
                    'supplier_id' => $data['supplier_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'remaining_quantity' => $item['quantity'],
                    'purchase_cost' => $item['unit_cost'],
                    'received_date' => now()->toDateString(),
                    'status' => 'active',
                    'notes' => 'Receipt ' . $receipt->reference_number,
                ]);

                StockMovement::create([
                    'batch_id' => $batch->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'warehouse_id' => $data['warehouse_id'],
                    'type' => 'stock_in',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'reference_type' => PurchaseReceipt::class,
                    'reference_id' => $receipt->id,
                    'notes' => 'Purchase receipt',
                    'user_id' => $request->user()->id,
                    'occurred_at' => $receipt->received_at,
                ]);
            }

            return $receipt->load('items.product', 'supplier', 'warehouse', 'user');
        });

        return response()->json(['data' => $receipt], 201);
    }

    public function storeRefund(Request $request)
    {
        $data = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'reason' => ['nullable', 'string'],
            'refund_amount' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variation_id' => ['nullable', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric'],
        ]);

        $refund = DB::transaction(function () use ($data, $request) {
            $refund = Refund::create([
                'sale_id' => $data['sale_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'user_id' => $request->user()->id,
                'reference_number' => 'REF-' . Str::upper(Str::random(6)),
                'refund_amount' => $data['refund_amount'],
                'reason' => $data['reason'] ?? null,
                'status' => 'completed',
                'refunded_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $refund->items()->create([
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => (float) $item['quantity'] * (float) $item['unit_cost'],
                ]);

                $warehouseId = $data['warehouse_id'] ?? null;
                $batch = Batch::query()
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $warehouseId ?? 0)
                    ->where('status', 'active')
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('expiry_date', 'asc')
                    ->first();

                if ($warehouseId) {
                    $batch = Batch::query()
                        ->where('product_id', $item['product_id'])
                        ->where('warehouse_id', $warehouseId)
                        ->where('status', 'active')
                        ->where('remaining_quantity', '>', 0)
                        ->orderBy('expiry_date', 'asc')
                        ->first();
                }

                if ($batch) {
                    $batch->increment('remaining_quantity', $item['quantity']);
                    StockMovement::create([
                        'batch_id' => $batch->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'warehouse_id' => $batch->warehouse_id,
                        'type' => 'refund_stock_in',
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'reference_type' => Refund::class,
                        'reference_id' => $refund->id,
                        'notes' => 'Refund stock return',
                        'user_id' => $request->user()->id,
                        'occurred_at' => now(),
                    ]);
                }
            }

            return $refund->load('items.product', 'customer', 'warehouse', 'user');
        });

        return response()->json(['data' => $refund], 201);
    }

    public function storeStockTransfer(Request $request)
    {
        $data = $request->validate([
            'source_warehouse_id' => ['required', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'exists:warehouses,id', 'different:source_warehouse_id'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variation_id' => ['nullable', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $transfer = DB::transaction(function () use ($data, $request) {
            $transfer = StockTransfer::create([
                'source_warehouse_id' => $data['source_warehouse_id'],
                'destination_warehouse_id' => $data['destination_warehouse_id'],
                'user_id' => $request->user()->id,
                'reference_number' => $data['reference_number'] ?? 'TR-' . Str::upper(Str::random(6)),
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'transferred_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                ]);

                $sourceBatch = Batch::query()
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['source_warehouse_id'])
                    ->where('status', 'active')
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('expiry_date', 'asc')
                    ->first();

                if (!$sourceBatch) {
                    throw new \RuntimeException('Insufficient stock for transfer');
                }

                $sourceBatch->decrement('remaining_quantity', $item['quantity']);
                StockMovement::create([
                    'batch_id' => $sourceBatch->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'warehouse_id' => $data['source_warehouse_id'],
                    'type' => 'stock_transfer_out',
                    'quantity' => -$item['quantity'],
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'notes' => 'Transfer out',
                    'user_id' => $request->user()->id,
                    'occurred_at' => now(),
                ]);

                $destinationBatch = Batch::query()
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['destination_warehouse_id'])
                    ->where('status', 'active')
                    ->first();

                if (!$destinationBatch) {
                    $destinationBatch = Batch::create([
                        'batch_number' => $this->generateBatchNumber(),
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'warehouse_id' => $data['destination_warehouse_id'],
                        'quantity' => $item['quantity'],
                        'remaining_quantity' => $item['quantity'],
                        'purchase_cost' => 0,
                        'received_date' => now()->toDateString(),
                        'status' => 'active',
                        'notes' => 'Transfer in',
                    ]);
                } else {
                    $destinationBatch->increment('remaining_quantity', $item['quantity']);
                }

                StockMovement::create([
                    'batch_id' => $destinationBatch->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'warehouse_id' => $data['destination_warehouse_id'],
                    'type' => 'stock_transfer_in',
                    'quantity' => $item['quantity'],
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'notes' => 'Transfer in',
                    'user_id' => $request->user()->id,
                    'occurred_at' => now(),
                ]);
            }

            return $transfer->load('items.product', 'sourceWarehouse', 'destinationWarehouse', 'user');
        });

        return response()->json(['data' => $transfer], 201);
    }

    public function storeStockAdjustment(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'reason' => ['required', 'string'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variation_id' => ['nullable', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.type' => ['required', 'in:increase,decrease'],
        ]);

        $adjustment = DB::transaction(function () use ($data, $request) {
            $adjustment = StockAdjustment::create([
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => $request->user()->id,
                'reference_number' => 'ADJ-' . Str::upper(Str::random(6)),
                'reason' => $data['reason'],
                'status' => 'completed',
                'adjusted_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $adjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'type' => $item['type'],
                ]);

                $batch = Batch::query()
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->where('status', 'active')
                    ->orderBy('expiry_date', 'asc')
                    ->first();

                if (!$batch) {
                    throw new \RuntimeException('No batch available for adjustment');
                }

                if ($item['type'] === 'decrease') {
                    $batch->decrement('remaining_quantity', $item['quantity']);
                    StockMovement::create([
                        'batch_id' => $batch->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'warehouse_id' => $data['warehouse_id'],
                        'type' => 'stock_adjustment_out',
                        'quantity' => -$item['quantity'],
                        'reference_type' => StockAdjustment::class,
                        'reference_id' => $adjustment->id,
                        'notes' => $data['reason'],
                        'user_id' => $request->user()->id,
                        'occurred_at' => now(),
                    ]);
                } else {
                    $batch->increment('remaining_quantity', $item['quantity']);
                    StockMovement::create([
                        'batch_id' => $batch->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'warehouse_id' => $data['warehouse_id'],
                        'type' => 'stock_adjustment_in',
                        'quantity' => $item['quantity'],
                        'reference_type' => StockAdjustment::class,
                        'reference_id' => $adjustment->id,
                        'notes' => $data['reason'],
                        'user_id' => $request->user()->id,
                        'occurred_at' => now(),
                    ]);
                }
            }

            return $adjustment->load('items.product', 'warehouse', 'user');
        });

        return response()->json(['data' => $adjustment], 201);
    }

    private function generateBatchNumber(): string
    {
        $today = now()->format('ymd');
        $prefix = 'BATCH-' . $today . '-';
        $count = Batch::where('batch_number', 'like', $prefix . '%')->count();

        return $prefix . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}