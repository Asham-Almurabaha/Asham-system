<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTransaction extends Model
{
    use HasFactory;
    use Auditable;

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
