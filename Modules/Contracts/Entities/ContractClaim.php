<?php

namespace Modules\Contracts\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Lookups\Entities\ClaimFirstParty;
use Modules\Lookups\Entities\ClaimStatus;

class ContractClaim extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'contract_id',
        'claim_first_party_id',
        'filed_party_role',
        'claim_amount',
        'discount_amount',
        'claim_date',
        'document_number',
        'claim_status_id',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'claim_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public const FILED_PARTY_CUSTOMER = 'customer';
    public const FILED_PARTY_GUARANTOR = 'guarantor';

    public const FILED_PARTY_ROLES = [
        self::FILED_PARTY_CUSTOMER,
        self::FILED_PARTY_GUARANTOR,
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function claimFirstParty(): BelongsTo
    {
        return $this->belongsTo(ClaimFirstParty::class);
    }

    public function claimStatus(): BelongsTo
    {
        return $this->belongsTo(ClaimStatus::class);
    }

    public function getFiledPartyNameAttribute(): ?string
    {
        $contract = $this->contract;

        if (! $contract) {
            return null;
        }

        $contract->loadMissing('customer:id,name', 'guarantor:id,name');

        return match ($this->filed_party_role) {
            self::FILED_PARTY_CUSTOMER => optional($contract->customer)->name,
            self::FILED_PARTY_GUARANTOR => optional($contract->guarantor)->name,
            default => null,
        };
    }
}
