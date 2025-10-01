<?php

namespace Modules\Companies\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Ledger\Entities\LedgerEntry;

class CompanyTransaction extends Model
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'reference',
        'transaction_date',
        'total_amount',
        'company_disbursement_status_id',
        'bank_account_id',
        'bank_amount',
        'safe_id',
        'safe_amount',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_amount' => 'decimal:2',
        'bank_amount' => 'decimal:2',
        'safe_amount' => 'decimal:2',
    ];

    public function status()
    {
        return $this->belongsTo(CompanyDisbursementStatus::class, 'company_disbursement_status_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function safe()
    {
        return $this->belongsTo(Safe::class);
    }

    public function allocations()
    {
        return $this->hasMany(CompanyTransactionAllocation::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class, 'company_transaction_id');
    }

    public function getDisbursedAmountAttribute(): float
    {
        return (float) $this->ledgerEntries()
            ->where('direction', 'out')
            ->sum('amount');
    }

    public function getRepaidAmountAttribute(): float
    {
        return (float) $this->ledgerEntries()
            ->where('direction', 'in')
            ->sum('amount');
    }

    public function getOutstandingAmountAttribute(): float
    {
        $balance = round($this->disbursed_amount - $this->repaid_amount, 2);

        return max($balance, 0.0);
    }

    public function refreshStatus(): void
    {
        $statuses = CompanyDisbursementStatus::automationStatuses();

        $status = $statuses['pending'] ?? null;

        if ($this->disbursed_amount <= 0.0 || $this->outstanding_amount <= 0.0) {
            $status = $statuses['settled'] ?? $status ?? $statuses['partial'] ?? null;
        } elseif ($this->repaid_amount > 0.0 && $this->outstanding_amount > 0.0) {
            $status = $statuses['partial'] ?? $status ?? $statuses['settled'] ?? null;
        }

        if ($status && $this->company_disbursement_status_id !== $status->id) {
            $this->forceFill(['company_disbursement_status_id' => $status->id])->save();
        }
    }
}
