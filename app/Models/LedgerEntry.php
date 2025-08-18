<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LedgerEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entry_date',
        'investor_id',
        'is_office',
        'transaction_status_id',
        'transaction_type_id',
        'bank_account_id',
        'safe_id',
        'contract_id',
        'installment_id',
        'amount',
        'direction',
        'ref',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'is_office'  => 'bool',
        'amount'     => 'decimal:2',
    ];

    // علاقات
    public function investor()         { return $this->belongsTo(Investor::class); }
    public function contract()         { return $this->belongsTo(Contract::class); }
    public function installment()      { return $this->belongsTo(ContractInstallment::class, 'installment_id'); }
    public function bankAccount()      { return $this->belongsTo(BankAccount::class); }
    public function safe()             { return $this->belongsTo(Safe::class); }
    public function status()           { return $this->belongsTo(TransactionStatus::class, 'transaction_status_id'); }
    public function type()             { return $this->belongsTo(TransactionType::class, 'transaction_type_id'); }
    public function productTransactions(){return $this->hasMany(ProductTransaction::class);}


    // مقدار موقّع (مفيد للعرض)
    public function getSignedAmountAttribute(): string
    {
        $sign = $this->direction === 'out' ? -1 : 1;
        return number_format($sign * (float)$this->amount, 2);
    }

    
    // سكوبات مفيدة
    public function scopeOfInvestor($q, $investorId) { return $q->where('investor_id', $investorId); }
    public function scopeOfContract($q, $contractId) { return $q->where('contract_id', $contractId); }

}
