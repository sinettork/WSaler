<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\ActivityLog;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->with(['province', 'district', 'commune', 'village'])->withCount('batches')->orderBy('name');

        return WarehouseResource::collection($query->paginate(20));
    }

    public function store(StoreWarehouseRequest $request)
    {
        $data = $request->validated();
        if (empty($data['code'])) {
            $data['code'] = $this->generateCode();
        }

        $warehouse = DB::transaction(function () use ($data, $request) {
            if (!empty($data['is_default'])) {
                Warehouse::where('is_default', true)->update(['is_default' => false]);
            }
            return Warehouse::create($data);
        });
        $warehouse->load(['province', 'district', 'commune', 'village']);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created_warehouse',
            'description' => "Created warehouse: {$warehouse->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return (new WarehouseResource($warehouse))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Warehouse $warehouse)
    {
        return new WarehouseResource(
            $warehouse->loadCount('batches')->load(['province', 'district', 'commune', 'village'])
        );
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $warehouse) {
            if (!empty($data['is_default'])) {
                Warehouse::where('id', '!=', $warehouse->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
            $warehouse->update($data);
        });
        $warehouse->load(['province', 'district', 'commune', 'village']);

        return new WarehouseResource($warehouse);
    }

    public function destroy(Request $request, Warehouse $warehouse)
    {
        if ($warehouse->is_default) {
            return response()->json([
                'message' => 'Cannot delete the default warehouse.',
            ], 422);
        }

        if ($warehouse->batches()->exists()) {
            return response()->json([
                'message' => 'Cannot delete warehouse with associated batches.',
                'batches_count' => $warehouse->batches()->count(),
            ], 422);
        }

        // F5: stock_movements reference warehouses via restrictOnDelete FK.
        // Pre-check surfaces a clean 422 instead of an opaque 500.
        $movementsCount = \App\Models\StockMovement::where('warehouse_id', $warehouse->id)->count();
        if ($movementsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete warehouse referenced by stock_movements.',
                'stock_movements_count' => $movementsCount,
            ], 422);
        }

        try {
            $warehouse->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Cannot delete warehouse: rows in another table still reference it.',
                ], 422);
            }
            throw $e;
        }

        return response()->noContent();
    }

    private function generateCode(): string
    {
        $count = Warehouse::withTrashed()->count() + 1;
        return 'WH-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
