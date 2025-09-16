<?php

namespace App\Models\Lookups;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function transactionStatuses()
    {
        return $this->belongsToMany(TransactionStatus::class, 'category_transaction_status');
    }
}
