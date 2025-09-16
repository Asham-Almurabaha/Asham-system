<?php

namespace Modules\Lookups\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Contracts\Entities\Contract;

class ContractStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
