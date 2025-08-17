<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Safe extends Model
{
    protected $fillable = ['name'
        , 'location'
        , 'opening_balance'
        , 'currency_code'
        , 'is_active'
        , 'notes'
    ];

    public function ledgerEntries() { return $this->hasMany(LedgerEntry::class); }
}