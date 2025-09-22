<?php

namespace Modules\Investors\Entities;

use App\Models\LedgerEntry;
use Modules\Lookups\Entities\Nationality;
use App\Models\OfficeTransaction;
use Modules\Lookups\Entities\Title;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Contracts\Entities\Contract;

class Investor extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'name',
        'national_id',
        'phone',
        'email',
        'address',
        'nationality_id',
        'title_id',
        'id_card_image',
        'contract_image',
        'office_share_percentage',
        'investment_start_date',
        'zakat_last_notified_at',
        'zakat_last_notified_due_date',
    ];

    protected $casts = [
        'investment_start_date' => 'date',
        'zakat_last_notified_at' => 'datetime',
        'zakat_last_notified_due_date' => 'date',
    ];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function title()
    {
        return $this->belongsTo(Title::class);
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class, 'contract_investor')
                        ->withPivot(['share_percentage', 'share_value', 'office_share_percentage'])
                        ->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany(InvestorTransaction::class);
    }

    public function officeTransactions()
    {
        return $this->hasMany(OfficeTransaction::class, 'investor_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
