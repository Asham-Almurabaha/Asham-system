<?php

namespace Modules\Companies\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyTransactionAllocation extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'company_transaction_id',
        'company_id',
        'share_percentage',
        'share_amount',
        'notes',
    ];

    protected $casts = [
        'share_percentage' => 'decimal:2',
        'share_amount' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(CompanyTransaction::class, 'company_transaction_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
