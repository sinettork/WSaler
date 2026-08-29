<?php

namespace App\Models\SalesPerformance;

use App\Models\Approval;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'customer_id', 'salesperson_user_id', 'status',
    'valid_from', 'valid_to', 'notes',
    'assigned_by', 'approval_id',
])]
class CustomerAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_user_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }
}