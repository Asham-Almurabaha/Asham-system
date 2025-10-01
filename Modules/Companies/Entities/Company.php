<?php

namespace Modules\Companies\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function allocations()
    {
        return $this->hasMany(CompanyTransactionAllocation::class);
    }

    public function transactions()
    {
        return $this->belongsToMany(CompanyTransaction::class, 'company_transaction_allocations')
            ->withPivot(['share_percentage', 'share_amount', 'notes'])
            ->withTimestamps();
    }
}
