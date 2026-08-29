<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'code' => $this->customer->code,
                'current_balance' => (float) $this->customer->current_balance,
                'credit_limit' => (float) $this->customer->credit_limit,
            ] : null),
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
                'code' => $this->warehouse->code,
            ] : null),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null),
            'voided_by' => $this->whenLoaded('voidedBy', fn () => $this->voidedBy ? [
                'id' => $this->voidedBy->id,
                'name' => $this->voidedBy->name,
            ] : null),
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'paid' => (float) $this->paid,
            'change_due' => (float) $this->change_due,
            'status' => $this->status,
            'notes' => $this->notes,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'voided_at' => $this->voided_at?->toIso8601String(),
            'void_reason' => $this->void_reason,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'product' => $i->relationLoaded('product') && $i->product ? [
                    'id' => $i->product->id,
                    'name' => $i->product->name,
                    'sku' => $i->product->sku,
                ] : null,
                'variation_id' => $i->variation_id,
                'unit_id' => $i->unit_id,
                'unit' => $i->relationLoaded('unit') && $i->unit ? [
                    'id' => $i->unit->id,
                    'name' => $i->unit->name,
                    'short_code' => $i->unit->short_code,
                ] : null,
                'quantity' => (int) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'discount' => (float) $i->discount,
                'line_total' => (float) $i->line_total,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($p) => [
                'id' => $p->id,
                'method' => $p->method,
                'amount' => (float) $p->amount,
                'reference' => $p->reference,
                'paid_at' => $p->paid_at?->toIso8601String(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
