<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'transaction_type_id',
    ];

    // العلاقة مع نوع العملية
    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class);
    }

    // علاقة مع Category (many to many)
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_transaction_status');
    }

    public function transactions()
    {
        return $this->hasMany(InvestorTransaction::class, 'status_id');
    }

    public function officeTransactions()
{
    return $this->hasMany(OfficeTransaction::class, 'status_id');
}

public function ledgerEntries() { 
    return $this->hasMany(LedgerEntry::class, 'status_id'); 
}

}
