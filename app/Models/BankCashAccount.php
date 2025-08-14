<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankCashAccount extends Model
{
    protected $table = 'bank_cash_accounts';

    protected $fillable = [
        'name',
        'type',
        'account_number',
        'branch',
        'balance',
        'notes',
        'active',
    ];

    // لو حابب تضيف توابع أو علاقات هنا بعدين
}

