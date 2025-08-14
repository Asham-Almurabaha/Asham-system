<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentPayment extends Model
{
    use HasFactory;

    protected $table = 'installment_payments';

    protected $fillable = [
        'contract_installment_id',
        'amount',
        'due_date',
        'paid_amount', 
        'payment_date',
        'notes',
    ];

    protected $casts  = [
        'due_date' => 'date',
        'payment_date' => 'date',
        ];

    /**
     * القسط المرتبط بالدفع
     */
    public function installment()
    {
        return $this->belongsTo(ContractInstallment::class, 'contract_installment_id');
    }
}
