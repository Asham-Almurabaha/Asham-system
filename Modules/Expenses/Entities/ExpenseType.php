<?php

namespace Modules\Expenses\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'default_amount',
        'currency_code',
        'is_recurring',
        'recurrence_interval',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
