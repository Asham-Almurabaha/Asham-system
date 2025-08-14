<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'transaction_type_id',
    ];

    // العلاقة مع نوع العملية
    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class);
    }

    // علاقة مع Category (many to many)
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_transaction_status');
    }
}
