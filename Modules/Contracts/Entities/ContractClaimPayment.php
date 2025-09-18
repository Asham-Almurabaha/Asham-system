<?php

namespace Modules\Contracts\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Lookups\Entities\ClaimPayer;

class ContractClaimPayment extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'contract_claim_id',
        'claim_payer_id',
        'amount',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(ContractClaim::class, 'contract_claim_id');
    }

    public function claimPayer(): BelongsTo
    {
        return $this->belongsTo(ClaimPayer::class);
    }
}
