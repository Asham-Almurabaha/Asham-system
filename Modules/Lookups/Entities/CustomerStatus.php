<?php

namespace Modules\Lookups\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected $casts = [
        'is_protected' => 'boolean',
    ];
}
