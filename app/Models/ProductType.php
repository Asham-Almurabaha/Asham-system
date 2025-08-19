<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductType extends Model
{
    use HasFactory;

    protected $table = 'product_types';

    protected $fillable = [
        'name',
        'description',
    ];

    // كل نوع له حركات بضائع
    public function productTransactions()
    {
        return $this->hasMany(ProductTransaction::class, 'product_type_id');
    }

    // كل نوع مرتبط بعقود (باستخدام product_type_id في contracts)
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'product_type_id');
    }
}
