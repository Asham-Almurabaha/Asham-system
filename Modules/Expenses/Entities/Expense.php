<?php

namespace Modules\Expenses\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'notes',
        'last_notified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'last_notified_at' => 'datetime',
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

    public function markNotified(): void
    {
        $this->forceFill([
            'last_notified_at' => now(),
        ])->save();
    }
}
