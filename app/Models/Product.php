<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    // كل منتج ممكن يكون له إدخالات أسعار متعددة
    public function entries()
    {
        return $this->hasMany(ProductEntry::class);
    }
}
