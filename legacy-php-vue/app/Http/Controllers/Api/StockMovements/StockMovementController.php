<?php

namespace App\Http\Controllers\Api\StockMovements;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovements\IndexStockMovementRequest;
use App\Models\StockMovement;
use App\Traits\DataScoping;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    use DataScoping;
    public function index(IndexStockMovementRequest $request): AnonymousResourceCollection
    {
        $query = StockMovement::with(['product', 'variation', 'warehouse', 'user'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        $query = $this->applyDataScoping($query);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->integer('batch_id'));
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->string('reference_type'));
        }
        if ($request->filled('reference_id')) {
            $query->where('reference_id', $request->integer('reference_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('occurred_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('occurred_at', '<=', $request->date('to'));
        }

        $perPage = min(200, max(1, (int) $request->integer('per_page', 50)));

        return StockMovementResource::collection($query->paginate($perPage));
    }
}
