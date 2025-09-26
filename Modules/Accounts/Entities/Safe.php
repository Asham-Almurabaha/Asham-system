<?php

namespace Modules\Accounts\Entities;

use App\Traits\Auditable;
use Modules\Ledger\Entities\LedgerEntry;
use Illuminate\Database\Eloquent\Model;

class Safe extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'location',
        'opening_balance',
        'currency_code',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
