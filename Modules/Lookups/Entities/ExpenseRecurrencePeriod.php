<?php

namespace Modules\Lookups\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseRecurrencePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_protected',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
    ];

    public function expenseTypes(): HasMany
    {
        return $this->hasMany(ExpenseType::class);
    }
}
