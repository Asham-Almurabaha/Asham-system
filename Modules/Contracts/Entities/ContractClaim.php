<?php

namespace Modules\Contracts\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractClaim extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'contract_id',
        'filed_in_party',
        'filed_against_party',
        'claim_amount',
        'claim_date',
        'document_number',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'claim_amount' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
