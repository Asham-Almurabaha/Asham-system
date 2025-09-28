<?php

namespace Modules\\Expenses\\Entities;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Modules\\Accounts\\Entities\\BankAccount;
use Modules\\Accounts\\Entities\\Safe;

class ExpensePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'amount',
        'paid_at',
        'bank_account_id',
        'safe_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function safe(): BelongsTo
    {
        return $this->belongsTo(Safe::class);
    }
}
