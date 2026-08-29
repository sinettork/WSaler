<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_category_id',
        'warehouse_id',
        'user_id',
        'reference_number',
        'status',
        'expense_date',
        'amount',
        'currency',
        'exchange_rate',
        'payment_method',
        'payment_date',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'payment_date' => 'date',
            'amount' => 'decimal:4',
            'exchange_rate' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approval(): MorphTo
    {
        return $this->morphOne(Approval::class, 'approvable');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public function getAmountInBaseCurrencyAttribute(): float
    {
        return (float) $this->amount * (float) $this->exchange_rate;
    }
}