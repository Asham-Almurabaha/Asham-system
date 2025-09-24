<?php

namespace Modules\Lookups\Entities;

use Modules\Ledger\Entities\LedgerEntry;
use App\Models\OfficeTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Investors\Entities\InvestorTransaction;

class TransactionStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'transaction_type_id',
    ];

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class);
    }

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

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class, 'transaction_status_id');
    }
}
