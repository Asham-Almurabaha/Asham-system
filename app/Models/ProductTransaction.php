<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type_id',
        'ledger_entry_id',
        'quantity',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
    ];


    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function ledgerEntry()
    {
        return $this->belongsTo(LedgerEntry::class);
    }
}
