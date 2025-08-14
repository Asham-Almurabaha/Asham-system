<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'purchase_price',
        'entry_date',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
