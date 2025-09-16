<?php

namespace App\Models\Lookups;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Contracts\Entities\ContractInstallment;
use Modules\Investors\Entities\InvestorTransaction;

class InstallmentStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function installments()
    {
        return $this->hasMany(ContractInstallment::class);
    }

    public function transactions()
    {
        return $this->hasMany(InvestorTransaction::class, 'status_id');
    }
}
