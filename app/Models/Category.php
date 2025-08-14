<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // علاقة مع TransactionStatus (many to many)
    public function transactionStatuses()
    {
        return $this->belongsToMany(TransactionStatus::class, 'category_transaction_status');
    }
}
