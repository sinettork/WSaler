<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    public function __construct(
        private SaleService $saleService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Sale::with(['customer', 'warehouse', 'user', 'items'])
            ->orderByDesc('sold_at')
            ->orderByDesc('id');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('sold_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('sold_at', '<=', $request->date('to'));
        }
        if ($request->filled('q')) {
            $term = '%' . $request->string('q') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', $term)
                  ->orWhere('notes', 'like', $term);
            });
        }

        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        return SaleResource::collection($query->paginate($perPage));
    }

    public function show(Sale $sale): SaleResource
    {
        return new SaleResource(
            $sale->load(['items.product', 'items.variation', 'items.unit', 'payments', 'customer', 'warehouse', 'user', 'voidedBy'])
        );
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->createSale(
            $request->user(),
            $request->validated()
        );

        return (new SaleResource($sale))
            ->response()
            ->setStatusCode(201);
    }

    public function void(Request $request, Sale $sale): SaleResource
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $voided = $this->saleService->voidSale($sale, $request->user(), $data['reason'] ?? null);

        return new SaleResource($voided);
    }
}
