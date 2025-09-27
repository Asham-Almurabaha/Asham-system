<?php

namespace Modules\Expenses\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_type_id',
        'title',
        'amount',
        'currency_code',
        'due_date',
        'paid_at',
        'notes',
        'last_notified_at',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'date',
        'last_notified_at' => 'datetime',
    ];

    protected $attributes = [
        'currency_code' => 'SAR',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    public function scopeDueOrOverdue($query)
    {
        $today = Carbon::today();

        return $query->whereDate('due_date', '<=', $today);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->paid_at !== null;
    }

    public function markNotified(): void
    {
        $this->forceFill([
            'last_notified_at' => now(),
        ])->save();
    }
}
