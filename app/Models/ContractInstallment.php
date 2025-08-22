<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractInstallment extends Model
{
    use HasFactory;
    use Auditable;

    protected $table = 'contract_installments';

    protected $fillable = [
        'contract_id',
        'installment_number',
        'due_date',
        'due_amount',
        'payment_date',
        'payment_amount',
        'installment_status_id',
        'notes',
    ];
        protected $casts  = [
        'due_date' => 'date',
        'payment_date' => 'date',
        ];

    // علاقة العقد
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    // علاقة السداد
    public function transactions()
    {
        return $this->hasMany(InvestorTransaction::class, 'installment_id');
    }

    // علاقة حالة القسط
    public function installmentStatus()
    {
        return $this->belongsTo(InstallmentStatus::class);
    }

    

    public function officeTransactions()
    {
        return $this->hasMany(OfficeTransaction::class, 'installment_id');
    }


}
