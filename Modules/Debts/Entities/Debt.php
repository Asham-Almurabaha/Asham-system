<?php

namespace Modules\Debts\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Customers\Entities\Customer;
use Modules\Debts\Entities\DebtPayment;
use Modules\Investors\Entities\Investor;

class Debt extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'party_type',
        'customer_id',
        'investor_id',
        'counterparty_name',
        'principal_amount',
        'paid_amount',
        'bank_account_id',
        'safe_id',
        'issued_at',
        'due_at',
        'notes',
        'last_notified_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'principal_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'last_notified_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function safe()
    {
        return $this->belongsTo(Safe::class);
    }

    public function getOutstandingAmountAttribute(): float
    {
        $principal = (float) $this->principal_amount;
        $paid = (float) $this->paid_amount;

        return max(round($principal - $paid, 2), 0.0);
    }

    public function getStatusAttribute(): string
    {
        return $this->outstanding_amount <= 0.0 ? 'settled' : 'open';
    }

    public function refreshPaidAmount(): void
    {
        $total = (float) $this->payments()->sum('amount');

        $this->forceFill(['paid_amount' => $total])->save();
    }
}
