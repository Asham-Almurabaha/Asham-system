<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractInvestor extends Model
{
    use HasFactory;

    protected $table = 'contract_investor';

    protected $fillable = [
        'contract_id',
        'investor_id',
        'share_percentage',
        'share_value',
    ];

    // علاقة العقد
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    // علاقة المستثمر
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
}
