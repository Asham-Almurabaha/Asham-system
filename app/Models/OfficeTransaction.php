<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeTransaction extends Model
{
    protected $fillable = [
        'investor_id',
        'contract_id',
        'installment_id',
        'status_id',
        'amount',
        'transaction_date',
        'notes'
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
