<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    use HasFactory;

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
                        ->withPivot(['share_percentage', 'share_value'])
                  
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
public function ledgerEntries() {
    return $this->hasMany(LedgerEntry::class); 
}

}
