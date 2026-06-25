<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Http\Resources\BatchResource;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::with([
            'product:id,name,sku',
            'variation:id,value',
            'warehouse:id,name,code',
            'supplier:id,name',
        ]);

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($supplierId = $request->query('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($request->boolean('expired')) {
            $query->whereNotNull('expiry_date')->where('expiry_date', '<', Carbon::today());
        }

        if ($days = $request->query('expiring_within')) {
            $today = Carbon::today();
            $endDate = $today->copy()->addDays((int) $days);
            $query->whereNotNull('expiry_date')
                  ->whereBetween('expiry_date', [$today, $endDate]);
        }

        $query->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC');

        return BatchResource::collection($query->paginate(20));
    }

    public function expiringSoon(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $today = Carbon::today();
        $endDate = $today->copy()->addDays($days);

        $batches = Batch::with(['product:id,name,sku', 'warehouse:id,name,code'])
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $endDate])
            ->orderBy('expiry_date')
            ->get();

        return BatchResource::collection($batches);
    }

    public function expired(Request $request)
    {
        $batches = Batch::with(['product:id,name,sku', 'warehouse:id,name,code'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::today())
            ->orderBy('expiry_date')
            ->get();

        return BatchResource::collection($batches);
    }

    public function store(StoreBatchRequest $request)
    {
        $data = $request->validated();

        if (empty($data['batch_number'])) {
            $data['batch_number'] = $this->generateBatchNumber();
        }

        if (!isset($data['remaining_quantity'])) {
            $data['remaining_quantity'] = $data['quantity'];
        }

        $batch = DB::transaction(function () use ($data, $request) {
            $batch = Batch::create($data);

            StockMovement::create([
                'batch_id' => $batch->id,
                'product_id' => $batch->product_id,
                'variation_id' => $batch->variation_id,
                'warehouse_id' => $batch->warehouse_id,
                'type' => 'stock_in',
                'quantity' => $batch->quantity,
                'unit_cost' => $batch->purchase_cost,
                'reference_type' => Batch::class,
                'reference_id' => $batch->id,
                'notes' => 'Initial stock receipt',
                'user_id' => $request->user()->id,
                'occurred_at' => $batch->received_date ?? now(),
            ]);

            return $batch;
        });

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created_batch',
            'description' => "Created batch {$batch->batch_number} for {$batch->product->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return (new BatchResource($batch->load(['product', 'variation', 'warehouse', 'supplier'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Batch $batch)
    {
        return new BatchResource($batch->load(['product', 'variation', 'warehouse', 'supplier']));
    }

    public function update(UpdateBatchRequest $request, Batch $batch)
    {
        $batch->update($request->validated());

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated_batch',
            'description' => "Updated batch {$batch->batch_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return new BatchResource($batch);
    }

    public function destroy(Request $request, Batch $batch)
    {
        if ($batch->remaining_quantity !== $batch->quantity) {
            return response()->json([
                'message' => 'Cannot delete batch with consumed stock. Reverse movements first.',
            ], 422);
        }

        $batch->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted_batch',
            'description' => "Deleted batch {$batch->batch_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->noContent();
    }

    private function generateBatchNumber(): string
    {
        $today = Carbon::today();
        $prefix = 'BATCH-' . $today->format('ymd') . '-';
        $count = Batch::where('batch_number', 'like', $prefix . '%')->count();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
