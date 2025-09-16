<?php

namespace Modules\Lookups\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    use HasFactory;

    protected $table = 'transaction_types';

    protected $fillable = [
        'name',
        'description',
    ];

    public function statuses()
    {
        return $this->hasMany(TransactionStatus::class, 'transaction_type_id');
    }
}
