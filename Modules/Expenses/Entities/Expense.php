<?php

namespace Modules\Expenses\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Lookups\Entities\ExpenseType;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_type_id',
        'title',
        'amount',
        'due_date',
        'manual_paid_amount',
        'manual_outstanding_amount',
        'notes',
        'last_notified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'last_notified_at' => 'datetime',
        'manual_paid_amount' => 'decimal:2',
        'manual_outstanding_amount' => 'decimal:2',
    ];

    protected $appends = [
        'paid_amount',
        'outstanding_amount',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class);
    }

    public function scopeDueOrOverdue($query)
    {
        $today = Carbon::today();

        return $query->whereDate('due_date', '<=', $today);
    }

    public function getPaidAmountAttribute(): float
    {
        if (array_key_exists('manual_paid_amount', $this->attributes)
            && $this->attributes['manual_paid_amount'] !== null) {
            return (float) $this->attributes['manual_paid_amount'];
        }

        if (array_key_exists('payments_total', $this->attributes)) {
            return (float) $this->attributes['payments_total'];
        }

        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }

        return 0.0;
    }

    public function getOutstandingAmountAttribute(): float
    {
        if (array_key_exists('manual_outstanding_amount', $this->attributes)
            && $this->attributes['manual_outstanding_amount'] !== null) {
            return (float) $this->attributes['manual_outstanding_amount'];
        }

        $amount = (float) $this->amount;
        $paid = $this->paid_amount;

        return max(round($amount - $paid, 2), 0.0);
    }

    public function markNotified(): void
    {
        $this->forceFill([
            'last_notified_at' => now(),
        ])->save();
    }
}
