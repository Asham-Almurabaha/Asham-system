<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class InvestorTransaction extends Model

{
    use Auditable;
    
    protected $fillable = [
        'investor_id',
        'contract_id',
        'installment_id',
        'status_id',
        'amount',
        'transaction_date',
        'notes'
    ];
    protected $casts = [
        'transaction_date' => 'date',
    ];

    /**
     * العلاقة مع المستثمر
     */
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    /**
     * العلاقة مع العقد
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * العلاقة مع القسط
     */
    public function installment()
    {
        return $this->belongsTo(ContractInstallment::class);
    }

    /**
     * العلاقة مع الحالة
     */
    public function status()
    {
        return $this->belongsTo(TransactionStatus::class, 'status_id');
    }
}
