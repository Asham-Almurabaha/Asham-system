<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
