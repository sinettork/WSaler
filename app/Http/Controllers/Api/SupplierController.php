<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $suppliers = $query->with(['province', 'district', 'commune', 'village'])->orderBy('name', 'asc')->paginate(15);

        return SupplierResource::collection($suppliers)->response();
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $data = $request->validated();
        $supplier = Supplier::create($data);
        $supplier->load(['province', 'district', 'commune', 'village']);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'created_supplier',
            'description' => "Created supplier {$supplier->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier->load(['province', 'district', 'commune', 'village']));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $data = $request->validated();
        $supplier->update($data);
        $supplier->load(['province', 'district', 'commune', 'village']);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated_supplier',
            'description' => "Updated supplier {$supplier->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return new SupplierResource($supplier);
    }

    public function destroy(Request $request, Supplier $supplier): Response
    {
        // F13: block deletion when batches reference the supplier (audit trail integrity).
        // (Purchase orders not yet implemented — Phase 3 — so we only check batches here.)
        $batchesCount = Batch::where('supplier_id', $supplier->id)->count();
        if ($batchesCount > 0) {
            return response()->json([
                'message' => 'Cannot delete supplier with associated batches.',
                'batches_count' => $batchesCount,
            ], 422);
        }

        $supplier->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted_supplier',
            'description' => "Deleted supplier {$supplier->name}",
            'module' => 'suppliers',
            'resource_type' => Supplier::class,
            'resource_id' => $supplier->id,
            'event' => 'deleted',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->noContent();
    }
}
