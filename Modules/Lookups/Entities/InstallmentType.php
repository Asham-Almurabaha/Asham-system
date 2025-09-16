<?php

namespace Modules\Lookups\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Contracts\Entities\ContractInstallment;

class InstallmentType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function installments()
    {
        return $this->hasMany(ContractInstallment::class);
    }
}
