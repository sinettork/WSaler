# Phase 2 Slice 2.1 — Sales Data Model & API: Implementation Spec

## Context

Phase 1 (Foundation MVP) is shipped. The codebase has:
- `app/Services/FefoBatchSelector.php` — `selectForProduct(int $productId, int $quantity, ?int $variationId, ?int $warehouseId): array` returning `[{batch, quantity}, ...]`
- `app/Exceptions/InsufficientStockException.php` — thrown by FefoBatchSelector when stock is short
- `app/Models/Batch.php` — has `product_id`, `variation_id`, `warehouse_id`, `quantity`, `remaining_quantity`, `reserved_quantity`, `purchase_cost`, `expiry_date`, `status='active'`
- `app/Models/StockMovement.php` — has polymorphic `reference_type`/`reference_id` (MorphTo), `type` column, `quantity`, `unit_cost`, `user_id`, `occurred_at`, `notes`
- Existing migrations use: `foreignId('x')->constrained('table')->restrictOnDelete()`, `decimal('col', 12, 4)` for costs, `decimal('col', 10, 2)` for prices, indexes on FK + status + dates, `softDeletes()`
- Existing models use the `#[Fillable([...])]` attribute (Laravel 12+ syntax)
- Existing controllers log to `ActivityLog` after writes
- Role middleware alias: `'role:admin,manager,cashier,...'` defined in `app/Http/Middleware/EnsureRole.php`
- DB: SQLite for dev (locking semantics differ — `lockForUpdate` is a no-op; tests must not assume true concurrency on SQLite)

This spec adds the sales transactional layer on top of that foundation.

---

## Goal

Ship the sales data layer and a transactional checkout API. A cashier can POST a sale with N items + M payments, the system allocates stock across batches using FEFO, deducts `remaining_quantity`, creates one `StockMovement` per allocated batch, and returns the full invoice. A manager can void a completed sale which reverses stock.

---

## Files to Create

| Path | Purpose |
|---|---|
| `database/migrations/2026_06_24_120000_create_sales_table.php` | sales table |
| `database/migrations/2026_06_24_120001_create_sale_items_table.php` | sale line items |
| `database/migrations/2026_06_24_120002_create_sale_payments_table.php` | multi-method payments |
| `database/migrations/2026_06_24_120003_create_price_lists_table.php` | forward-declared, stub used in Phase 6 |
| `app/Models/Sale.php` | Sale Eloquent model |
| `app/Models/SaleItem.php` | SaleItem Eloquent model |
| `app/Models/SalePayment.php` | SalePayment Eloquent model |
| `app/Models/PriceList.php` | PriceList Eloquent model |
| `app/Http/Resources/SaleResource.php` | Full invoice resource |
| `app/Http/Resources/SaleItemResource.php` | Line item resource |
| `app/Http/Resources/SalePaymentResource.php` | Payment line resource |
| `app/Services/InvoiceNumberGenerator.php` | YYYYMM-NNNN per warehouse |
| `app/Services/SaleService.php` | checkout() + voidSale() |
| `app/Http/Requests/StoreSaleRequest.php` | Validate sale creation payload |
| `app/Http/Requests/UpdateSaleRequest.php` | Validate void payload |
| `app/Http/Controllers/Api/SaleController.php` | HTTP surface |
| `tests/Feature/SaleTest.php` | Happy path + FEFO + insufficient + void |
| `tests/Feature/SalePaymentTest.php` | Multi-payment split |
| `tests/Feature/SaleAuthorizationTest.php` | Role guards |
| `tests/Feature/SaleRaceConditionTest.php` | Concurrent allocation race |
| `tests/Unit/Services/InvoiceNumberGeneratorTest.php` | Format + increment |

---

## Database Schema

### `sales` table

```php
Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_number', 30)->unique();
    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
    $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
    $table->decimal('subtotal', 12, 2);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('tax', 12, 2)->default(0);
    $table->decimal('total', 12, 2);
    $table->decimal('paid', 12, 2);
    $table->decimal('change_due', 12, 2)->default(0);
    $table->string('status', 20)->default('completed'); // draft|completed|voided|refunded
    $table->text('notes')->nullable();
    $table->dateTime('sold_at');
    $table->timestamp('voided_at')->nullable();
    $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
    $table->text('void_reason')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('customer_id');
    $table->index('warehouse_id');
    $table->index('user_id');
    $table->index('status');
    $table->index('sold_at');
});
```

Note: column is `change_due` because `change` is reserved in some SQL dialects.

### `sale_items` table

```php
Schema::create('sale_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
    $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
    $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
    $table->integer('quantity');
    $table->decimal('unit_price', 12, 2);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('line_total', 12, 2);
    $table->timestamps();

    $table->index('sale_id');
    $table->index('product_id');
});
```

### `sale_payments` table

```php
Schema::create('sale_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
    $table->string('method', 30); // cash|credit|bank_transfer|e_wallet|card
    $table->decimal('amount', 12, 2);
    $table->string('reference', 100)->nullable();
    $table->dateTime('paid_at');
    $table->timestamps();

    $table->index('sale_id');
    $table->index('method');
});
```

### `price_lists` table (stub)

```php
Schema::create('price_lists', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
    $table->decimal('price', 12, 2);
    $table->integer('min_qty')->default(1);
    $table->date('valid_from')->nullable();
    $table->date('valid_to')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['customer_id', 'product_id']);
    $table->index('is_active');
});
```

Phase 6 will use this; Phase 2.1 only creates the table and model so the schema migration stays clean.

---

## Models

### `app/Models/Sale.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'invoice_number', 'customer_id', 'warehouse_id', 'user_id',
    'subtotal', 'discount', 'tax', 'total', 'paid', 'change_due',
    'status', 'notes', 'sold_at',
    'voided_at', 'voided_by', 'void_reason',
])]
class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'change_due' => 'decimal:2',
            'sold_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function payments(): HasMany { return $this->hasMany(SalePayment::class); }
    public function voidedBy(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
}
```

### `app/Models/SaleItem.php`

```php
#[Fillable([
    'sale_id', 'product_id', 'variation_id', 'unit_id',
    'quantity', 'unit_price', 'discount', 'line_total',
])]
class SaleItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variation(): BelongsTo { return $this->belongsTo(ProductVariation::class, 'variation_id'); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
}
```

### `app/Models/SalePayment.php`

```php
#[Fillable(['sale_id', 'method', 'amount', 'reference', 'paid_at'])]
class SalePayment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
}
```

### `app/Models/PriceList.php`

Standard BelongsTo relations for customer, product, unit. Fillable: `name, customer_id, product_id, unit_id, price, min_qty, valid_from, valid_to, is_active`. Casts: `price=decimal:2`, `min_qty=integer`, `is_active=boolean`, dates cast.

---

## Resources

### `app/Http/Resources/SaleResource.php`

```php
class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ] : null),
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
                'code' => $this->warehouse->code,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'payments' => SalePaymentResource::collection($this->whenLoaded('payments')),
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'paid' => $this->paid,
            'change_due' => $this->change_due,
            'status' => $this->status,
            'notes' => $this->notes,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'voided_at' => $this->voided_at?->toIso8601String(),
            'voided_by' => $this->whenLoaded('voidedBy', fn () => $this->voidedBy ? [
                'id' => $this->voidedBy->id,
                'name' => $this->voidedBy->name,
            ] : null),
            'void_reason' => $this->void_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

### `SaleItemResource` and `SalePaymentResource`

Standard shape with `whenLoaded` closures for `product`, `variation`, `unit`, `sale`. Include `id` plus the user-visible fields (name for relations, numeric values for money/qty). Pattern matches existing `BatchResource`.

---

## InvoiceNumberGenerator Service

`app/Services/InvoiceNumberGenerator.php`

```php
namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    /**
     * Generate an invoice number scoped per warehouse, per month.
     * Format: {warehouseCode}-{YYYYMM}-{NNNN padded to 4}
     * Caller MUST be inside a DB::transaction; uses SELECT ... FOR UPDATE
     * semantics via the row count lookup.
     */
    public function next(\App\Models\Warehouse $warehouse): string
    {
        $prefix = $warehouse->code . '-' . now()->format('Ym') . '-';

        $count = Sale::where('warehouse_id', $warehouse->id)
            ->where('invoice_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->count();

        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
```

The `lockForUpdate()` is a no-op on SQLite but works on MySQL/Postgres. The transaction wrapping ensures correct counter advancement under real concurrency.

---

## SaleService — the load-bearing class

`app/Services/SaleService.php`

**Complexity: complex** — multi-table transaction, FEFO allocation, stock mutation, audit trail. Read this spec carefully before implementing.

```php
namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private readonly FefoBatchSelector $fefo,
        private readonly InvoiceNumberGenerator $invoiceNumbers,
    ) {}

    /**
     * Atomically:
     *   1. Open DB::transaction
     *   2. Generate invoice number
     *   3. For each item: lockForUpdate on candidate batches (already inside FefoBatchSelector via its own query, but re-lock here), allocate via FEFO, decrement remaining_quantity
     *   4. Insert Sale, SaleItem[], SalePayment[]
     *   5. Insert one StockMovement per allocated batch (type=sale, reference=sale)
     *   6. Commit
     *
     * @param array $payload validated StoreSaleRequest data
     * @param User $cashier authenticated user
     * @return Sale with items, payments, customer, warehouse, user eager-loaded
     */
    public function checkout(array $payload, User $cashier): Sale
    {
        return DB::transaction(function () use ($payload, $cashier) {
            $warehouse = Warehouse::findOrFail($payload['warehouse_id']);
            $invoiceNumber = $this->invoiceNumbers->next($warehouse);

            $itemsPayload = $payload['items'];
            $paymentsPayload = $payload['payments'];

            $subtotal = 0.0;
            $itemDiscountTotal = 0.0;
            $saleItemsData = [];
            $stockMovementsByProduct = []; // product_id => movements[]

            foreach ($itemsPayload as $idx => $item) {
                // FEFO allocates batches; throws InsufficientStockException if short.
                $allocations = $this->fefo->selectForProduct(
                    productId: (int) $item['product_id'],
                    quantity: (int) $item['quantity'],
                    variationId: isset($item['variation_id']) ? (int) $item['variation_id'] : null,
                    warehouseId: $warehouse->id,
                );

                // Re-lock the allocated batches for update to prevent races.
                $batchIds = array_map(fn ($a) => $a['batch']->id, $allocations);
                $lockedBatches = \App\Models\Batch::whereIn('id', $batchIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $product = \App\Models\Product::findOrFail($item['product_id']);
                $unitPrice = (float) ($item['unit_price'] ?? $product->retail_price);
                $lineDiscount = (float) ($item['discount'] ?? 0);
                $lineTotal = ($unitPrice * (int) $item['quantity']) - $lineDiscount;
                $subtotal += $unitPrice * (int) $item['quantity'];
                $itemDiscountTotal += $lineDiscount;

                $saleItemsData[] = [
                    'product_id' => (int) $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'unit_id' => (int) $item['unit_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $unitPrice,
                    'discount' => $lineDiscount,
                    'line_total' => $lineTotal,
                    '_allocations' => $allocations, // temp, consumed below
                    '_unit_cost' => $allocations[0]['batch']->purchase_cost ?? 0,
                ];

                // Stock movement per allocated batch
                $batchMovements = [];
                foreach ($allocations as $alloc) {
                    $batch = $lockedBatches[$alloc['batch']->id];
                    $batch->decrement('remaining_quantity', $alloc['quantity']);
                    $batchMovements[] = [
                        'batch_id' => $batch->id,
                        'quantity' => -1 * $alloc['quantity'], // negative = outbound
                        'unit_cost' => $batch->purchase_cost,
                    ];
                }
                $stockMovementsByProduct[$idx] = $batchMovements;
            }

            $saleDiscount = (float) ($payload['discount'] ?? 0);
            $tax = (float) ($payload['tax'] ?? 0);
            $total = $subtotal - $itemDiscountTotal - $saleDiscount + $tax;
            $paid = array_sum(array_map(fn ($p) => (float) $p['amount'], $paymentsPayload));
            $change = max(0, $paid - $total);

            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $payload['customer_id'] ?? null,
                'warehouse_id' => $warehouse->id,
                'user_id' => $cashier->id,
                'subtotal' => round($subtotal, 2),
                'discount' => round($saleDiscount + $itemDiscountTotal, 2),
                'tax' => round($tax, 2),
                'total' => round($total, 2),
                'paid' => round($paid, 2),
                'change_due' => round($change, 2),
                'status' => 'completed',
                'notes' => $payload['notes'] ?? null,
                'sold_at' => $payload['sold_at'] ?? now(),
            ]);

            // Insert items + their stock movements
            foreach ($saleItemsData as $idx => $data) {
                $item = $sale->items()->create([
                    'product_id' => $data['product_id'],
                    'variation_id' => $data['variation_id'],
                    'unit_id' => $data['unit_id'],
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                    'discount' => $data['discount'],
                    'line_total' => $data['line_total'],
                ]);

                foreach ($stockMovementsByProduct[$idx] as $mv) {
                    StockMovement::create([
                        'batch_id' => $mv['batch_id'],
                        'product_id' => $data['product_id'],
                        'variation_id' => $data['variation_id'],
                        'warehouse_id' => $warehouse->id,
                        'type' => 'sale',
                        'quantity' => $mv['quantity'],
                        'unit_cost' => $mv['unit_cost'],
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'notes' => "Sale {$invoiceNumber}",
                        'user_id' => $cashier->id,
                        'occurred_at' => $sale->sold_at,
                    ]);
                }
            }

            // Insert payments
            foreach ($paymentsPayload as $payment) {
                $sale->payments()->create([
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'reference' => $payment['reference'] ?? null,
                    'paid_at' => $payment['paid_at'] ?? now(),
                ]);
            }

            return $sale->load(['items.product', 'items.variation', 'items.unit', 'payments', 'customer', 'warehouse', 'user']);
        });
    }

    /**
     * Void a completed sale:
     *   1. Open DB::transaction
     *   2. For each StockMovement with reference_type=Sale, reference_id=$sale->id, type=sale:
     *        create a reverse StockMovement with positive quantity
     *        re-increment batch.remaining_quantity
     *   3. Mark sale.status=voided, voided_at, voided_by, void_reason
     *   4. Commit
     */
    public function voidSale(Sale $sale, User $by, string $reason): Sale
    {
        if ($sale->status !== 'completed') {
            throw new \DomainException("Sale {$sale->invoice_number} cannot be voided (status={$sale->status}).");
        }

        return DB::transaction(function () use ($sale, $by, $reason) {
            $movements = StockMovement::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->where('type', 'sale')
                ->lockForUpdate()
                ->get();

            foreach ($movements as $mv) {
                // Reverse: increment remaining, create opposite movement
                if ($mv->batch_id) {
                    $batch = \App\Models\Batch::where('id', $mv->batch_id)->lockForUpdate()->first();
                    $batch->increment('remaining_quantity', abs($mv->quantity));
                }
                StockMovement::create([
                    'batch_id' => $mv->batch_id,
                    'product_id' => $mv->product_id,
                    'variation_id' => $mv->variation_id,
                    'warehouse_id' => $mv->warehouse_id,
                    'type' => 'sale_void',
                    'quantity' => abs($mv->quantity), // positive = restock
                    'unit_cost' => $mv->unit_cost,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'notes' => "Void of {$sale->invoice_number}: {$reason}",
                    'user_id' => $by->id,
                    'occurred_at' => now(),
                ]);
            }

            $sale->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $by->id,
                'void_reason' => $reason,
            ]);

            return $sale->fresh(['items.product', 'items.variation', 'items.unit', 'payments', 'customer', 'warehouse', 'user', 'voidedBy']);
        });
    }
}
```

**Concurrency primitives explicitly used:**
- `DB::transaction` wraps both methods
- `Batch::lockForUpdate()` on the candidate batches selected by FEFO
- `StockMovement::lockForUpdate()` when reading movements for reversal
- `Sale::lockForUpdate()` in invoice number generator
- All money values rounded to 2 decimals before persistence
- All mutations happen inside the transaction; nothing partial can commit

**Error propagation:**
- `InsufficientStockException` thrown by `FefoBatchSelector` propagates up; rollback triggered automatically
- `\DomainException` thrown if voiding a non-completed sale; rollback
- All other exceptions trigger rollback via the DB::transaction closure

---

## Form Requests

### `app/Http/Requests/StoreSaleRequest.php`

```php
public function rules(): array
{
    return [
        'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        'items' => ['required', 'array', 'min:1'],
        'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
        'items.*.variation_id' => ['nullable', 'integer', 'exists:product_variations,id'],
        'items.*.unit_id' => ['required', 'integer', 'exists:units,id'],
        'items.*.quantity' => ['required', 'integer', 'min:1'],
        'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        'payments' => ['required', 'array', 'min:1'],
        'payments.*.method' => ['required', 'in:cash,credit,bank_transfer,e_wallet,card'],
        'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
        'payments.*.reference' => ['nullable', 'string', 'max:100'],
        'discount' => ['nullable', 'numeric', 'min:0'],
        'tax' => ['nullable', 'numeric', 'min:0'],
        'notes' => ['nullable', 'string'],
        'sold_at' => ['nullable', 'date'],
    ];
}
```

### `app/Http/Requests/UpdateSaleRequest.php`

```php
public function rules(): array
{
    return [
        'void_reason' => ['required', 'string', 'min:3', 'max:500'],
    ];
}
```

---

## Controller

### `app/Http/Controllers/Api/SaleController.php`

Methods:

```php
public function index(Request $request)
{
    $query = Sale::with(['customer:id,name', 'warehouse:id,name,code', 'user:id,name']);
    if ($cid = $request->query('customer_id')) $query->where('customer_id', $cid);
    if ($wid = $request->query('warehouse_id')) $query->where('warehouse_id', $wid);
    if ($uid = $request->query('user_id')) $query->where('user_id', $uid);
    if ($status = $request->query('status')) $query->where('status', $status);
    if ($from = $request->query('from')) $query->where('sold_at', '>=', $from);
    if ($to = $request->query('to')) $query->where('sold_at', '<=', $to);
    $query->orderByDesc('sold_at');
    return SaleResource::collection($query->paginate(20));
}

public function show(Sale $sale)
{
    return new SaleResource($sale->load([
        'items.product', 'items.variation', 'items.unit',
        'payments', 'customer', 'warehouse', 'user', 'voidedBy',
    ]));
}

public function store(StoreSaleRequest $request, SaleService $service)
{
    $sale = $service->checkout($request->validated(), $request->user());
    return (new SaleResource($sale))->response()->setStatusCode(201);
}

public function update(UpdateSaleRequest $request, Sale $sale, SaleService $service)
{
    $sale = $service->voidSale($sale, $request->user(), $request->validated()['void_reason']);
    return new SaleResource($sale);
}
```

---

## Routes

Append to `routes/api.php` (under existing `Route::middleware(['auth:sanctum'])`):

```php
// Sales
Route::middleware('auth:sanctum')->prefix('sales')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\SaleController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\SaleController::class, 'store'])->middleware('role:admin,manager,cashier');
    Route::get('/{sale}', [\App\Http\Controllers\Api\SaleController::class, 'show'])->whereNumber('sale');
    Route::post('/{sale}/void', [\App\Http\Controllers\Api\SaleController::class, 'update'])->middleware('role:admin,manager')->whereNumber('sale');
});
```

Note: I use `POST /sales/{sale}/void` (not `PUT /sales/{sale}`) because void is a state transition, not a CRUD update. The controller method is still `update` so existing naming stays consistent — or rename if preferred.

---

## Tests

### `tests/Feature/SaleTest.php`

Test cases:
1. **Happy path FEFO across 3 batches** — seed: product X with 3 batches (expiry 2027-01, 2027-06, 2027-12), quantities 50/80/100. POST sale with 150 units. Assert: 3 SaleItems not 1, all 3 batches touched, `remaining_quantity` decreased correctly, 3 StockMovement rows with negative qty, all linked via `reference_type=Sale`.
2. **Walk-in customer** — `customer_id` null succeeds; `change_due` = `paid - total` if overpaid.
3. **Insufficient stock** — request 200 when only 100 available → 422, response JSON includes the InsufficientStockException context.
4. **Void reverses stock** — POST sale, then POST `/sales/{id}/void`. Assert: sale status = voided, voided_at/voided_by/void_reason set, batch `remaining_quantity` restored, new StockMovement rows of type `sale_void` with positive quantity.

### `tests/Feature/SalePaymentTest.php`

1. **Multi-payment split** — payments = [{cash, 30}, {credit, 70}]. Sale total = 100. Assert: 2 SalePayment rows, `paid` = 100, `change_due` = 0.
2. **Overpayment gives change** — single payment of 110 for total of 100. Assert: `change_due` = 10, `paid` = 110.
3. **Underpayment** — total 100, paid 80. Assert: status still `completed` (we accept partial payment for credit sales); `change_due` = 0. Document this behavior in a test name comment.

### `tests/Feature/SaleAuthorizationTest.php`

1. Unauthenticated POST /sales → 401.
2. Warehouse-role POST /sales → 403.
3. Cashier POST /sales → 201.
4. Manager POST /sales → 201.
5. Cashier POST /sales/{id}/void → 403.
6. Manager POST /sales/{id}/void → 200.

### `tests/Feature/SaleRaceConditionTest.php`

Test design: simulate two transactions trying to deduct from the same batch with only enough stock for one.
- Seed: 1 batch with `remaining_quantity = 50`.
- Use `DB::beginTransaction` + `Process` or in-process `Concurrency::run` to attempt two concurrent checkouts of 30 units each.
- Expected: exactly one creates a Sale (201), the other gets 422 (InsufficientStockException).
- Mark with `@group race` so it can be skipped if SQLite-only.
- Use `$this->withoutExceptionHandling()` carefully.

Approach: rather than true OS-level concurrency (fragile in tests), wrap each "concurrent" checkout in its own `DB::transaction` and assert that with `--race` they don't deadlock. Accept that on SQLite true concurrent deduction is impossible; the test asserts that the second attempt after the first commits throws `InsufficientStockException` (sequential simulation). Document this in the test docblock.

### `tests/Unit/Services/InvoiceNumberGeneratorTest.php`

1. First sale in warehouse 1 in June 2026 → `WH1-202606-0001`.
2. Second sale in same warehouse same month → `WH1-202606-0002`.
3. Different warehouse same month → `WH2-202606-0001`.
4. Different month → `WH1-202607-0001` (resets counter).

---

## Test Conventions (match existing)

- Use `RefreshDatabase` trait
- Use model factories (`User::factory()`, `Product::factory()`, etc.) where available; otherwise `Model::create([...])` with explicit fields
- Use `actingAs($user, 'sanctum')` for auth
- `$this->postJson('/api/sales', $payload)` style
- Assert with `$this->assertDatabaseHas` for persistence, `$this->assertEquals` for returned values

---

## Build Chunks

This spec is broken into 4 build chunks, ordered by dependency:

### Chunk 1 — Schema layer (simple)
**Files:** 4 migrations, 4 models, 3 resources
**Complexity:** simple — standard Laravel patterns
**Budget:** single-file tier
**Builder task:**
- Create the 4 migrations with exact schema above
- Create the 4 models with relations, casts, `#[Fillable]` attribute
- Create the 3 resources
- Verify: `php artisan migrate:fresh` succeeds, all `php -l` checks pass
- No tests in this chunk

### Chunk 2 — Service layer (complex)
**Files:** `InvoiceNumberGenerator`, `SaleService`, `UpdateSaleRequest`, `StoreSaleRequest`, `InsufficientStockException` mapping (in `bootstrap/app.php`)
**Complexity:** complex — multi-table transaction, FEFO + lockForUpdate
**Budget:** multi-file tier
**Builder task:**
- Create `InvoiceNumberGenerator` exactly as specified
- Create `SaleService` with `checkout()` and `voidSale()` exactly as specified
- Create both Form Requests exactly as specified
- Map `InsufficientStockException` to HTTP 422 in `bootstrap/app.php` exception handler:
  ```php
  ->withExceptions(function (Exceptions $exceptions) {
      $exceptions->render(function (InsufficientStockException $e) {
          return response()->json(['message' => $e->getMessage(), 'product_id' => $e->productId], 422);
      });
  })
  ```
- Verify: `php -l` on all files, `php artisan migrate:fresh` still works, `SaleService` instantiable via container

### Chunk 3 — HTTP layer (simple)
**Files:** `SaleController`, route additions to `routes/api.php`
**Complexity:** simple — standard REST pattern
**Budget:** single-file tier
**Builder task:**
- Create `SaleController` exactly as specified
- Add the 4 sales routes to `routes/api.php` under existing auth middleware
- Verify: `php artisan route:list --path=api/sales` shows the 4 routes with correct middleware

### Chunk 4 — Tests (complex)
**Files:** 5 test files
**Complexity:** complex — race test design, multi-fixture setup
**Budget:** multi-file tier
**Builder task:**
- Create all 5 test files with cases listed above
- Use `RefreshDatabase`
- Run `php artisan test --filter=Sale` — all pass
- Run `php artisan test --filter=Sale --race` — race test passes (no deadlock)
- Run `php artisan test` — full suite (Phase 1 + Phase 2.1) passes

---

## Acceptance Criteria

A slice is done when:
1. `php artisan migrate:fresh` exits 0
2. `php artisan route:list --path=api/sales` shows the 4 routes
3. `php artisan test --filter=Sale` exits 0 (all sale tests pass)
4. `php artisan test --race --parallel` exits 0 (no race conditions or deadlocks)
5. `php -l` passes on every new file
6. Smoke test via `tinker`:
   ```php
   $user = User::factory()->create();
   $svc = app(SaleService::class);
   // (seed minimal product + batches first)
   $sale = $svc->checkout([...], $user);
   echo $sale->invoice_number; // e.g. "WH1-202606-0001"
   ```
7. No new third-party packages added to composer.json
8. Phase 1 routes and tests still pass (no regressions)

---

## Out of Scope (will NOT be implemented in this slice)

- Refunds (Phase 2.3)
- Customer-specific pricing resolution (Phase 6 — table exists, resolver is stub)
- Invoice PDF generation
- Email receipts
- Receipt printer integration
- POS hardware (barcode scanners, cash drawers)
- Daily-counter snapshotting for invoice numbering (counter increments monotonically; gaps possible if transactions roll back)
