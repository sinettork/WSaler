<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale' => $this->whenLoaded('sale', fn () => [
                'id' => $this->sale->id,
                'invoice_number' => $this->sale->invoice_number,
            ]),
            'method' => $this->method,
            'amount' => $this->amount,
            'reference' => $this->reference,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
