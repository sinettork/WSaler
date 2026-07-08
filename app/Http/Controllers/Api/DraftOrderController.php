<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DraftOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DraftOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $drafts = DraftOrder::with(['customer:id,name,code', 'warehouse:id,name,code'])
            ->forUser($request->user()->id)
            ->recent()
            ->limit(100)
            ->get();

        return response()->json(['data' => $drafts]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:200'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.product_sku' => ['nullable', 'string'],
            'items.*.variation_id' => ['nullable', 'integer'],
            'items.*.variation_label' => ['nullable', 'string'],
            'items.*.unit_id' => ['nullable', 'integer'],
            'items.*.unit_name' => ['nullable', 'string'],
            'items.*.quantity_multiplier' => ['nullable', 'numeric'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['user_id'] = $request->user()->id;

        $draft = DraftOrder::create($data);
        $draft->load(['customer:id,name,code', 'warehouse:id,name,code']);

        return response()->json(['data' => $draft], 201);
    }

    public function show(DraftOrder $draftOrder): JsonResponse
    {
        $this->authorizeUserDraft($draftOrder);
        $draftOrder->load(['customer:id,name,code', 'warehouse:id,name,code']);

        return response()->json(['data' => $draftOrder]);
    }

    public function update(Request $request, DraftOrder $draftOrder): JsonResponse
    {
        $this->authorizeUserDraft($draftOrder);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:200'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'items' => ['required', 'array'],
            'payments' => ['nullable', 'array'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $draftOrder->update($data);
        $draftOrder->load(['customer:id,name,code', 'warehouse:id,name,code']);

        return response()->json(['data' => $draftOrder]);
    }

    public function destroy(DraftOrder $draftOrder): JsonResponse
    {
        $this->authorizeUserDraft($draftOrder);
        $draftOrder->delete();

        return response()->json(['message' => 'Draft order deleted.']);
    }

    private function authorizeUserDraft(DraftOrder $draftOrder): void
    {
        if ($draftOrder->user_id !== auth()->id()) {
            abort(403, 'You do not have permission to access this draft order.');
        }
    }
}
