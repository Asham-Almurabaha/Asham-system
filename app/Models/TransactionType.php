<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $table = 'transaction_types';

    protected $fillable = [
        'name',
        'description',
    ];

    // لو حابب تضيف علاقات لاحقاً تكتبها هنا
}
