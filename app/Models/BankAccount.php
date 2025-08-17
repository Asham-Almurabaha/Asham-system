<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'iban',
        'opening_balance',
        'currency_code',
        'is_active',
        'notes'
    ];

    public function ledgerEntries() { return $this->hasMany(LedgerEntry::class); }
}