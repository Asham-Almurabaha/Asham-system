<?php

namespace Modules\Lookups\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Expenses\Entities\Expense;

class ExpenseType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'default_amount',
        'currency_code',
        'is_recurring',
        'expense_recurrence_period_id',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function recurrencePeriod(): BelongsTo
    {
        return $this->belongsTo(ExpenseRecurrencePeriod::class, 'expense_recurrence_period_id');
    }
}
