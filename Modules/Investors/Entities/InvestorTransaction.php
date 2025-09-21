<?php

namespace Modules\Investors\Entities;

use Modules\Lookups\Entities\TransactionStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Contracts\Entities\ContractClaimPayment;
use Modules\Contracts\Entities\ContractInstallment;

class InvestorTransaction extends Model
{
    use Auditable;

    protected $fillable = [
        'investor_id',
        'contract_id',
        'contract_claim_id',
        'contract_claim_payment_id',
        'installment_id',
        'status_id',
        'amount',
        'transaction_date',
        'notes'
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    /** العلاقة مع المستثمر */
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    /** العلاقة مع العقد */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /** ربط بالمطالبة */
    public function claim()
    {
        return $this->belongsTo(ContractClaim::class, 'contract_claim_id');
    }

    /** ربط بسداد المطالبة */
    public function claimPayment()
    {
        return $this->belongsTo(ContractClaimPayment::class, 'contract_claim_payment_id');
    }

    /** العلاقة مع القسط */
    public function installment()
    {
        return $this->belongsTo(ContractInstallment::class);
    }

    /** العلاقة مع الحالة */
    public function status()
    {
        return $this->belongsTo(TransactionStatus::class, 'status_id');
    }
}
