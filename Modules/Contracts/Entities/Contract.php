<?php

namespace Modules\Contracts\Entities;

use Modules\Customers\Entities\Customer;
use Modules\Guarantors\Entities\Guarantor;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\InstallmentType;
use App\Models\OfficeTransaction;
use Modules\Lookups\Entities\ProductType;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Entities\InvestorTransaction;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Contracts\Entities\ContractClaimPayment;
use Modules\Contracts\Entities\ContractNote;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'contract_number',
        'customer_id',
        'guarantor_id',
        'contract_status_id',
        'product_type_id',
        'products_count',
        'purchase_price',
        'sale_price',
        'contract_value',
        'investor_profit',
        'total_value',
        'discount_amount',
        'installment_type_id',
        'installment_value',
        'installments_count',
        'start_date',
        'first_installment_date',
        'contract_image',
        'contract_customer_image',
        'contract_guarantor_image',
    ];

    protected $casts  = [
        'start_date' => 'date',
        'first_installment_date' => 'date',
        ];

    // protected static function booted()
    // {
    //     static::saving(function (self $contract) {
    //         $contract->total_value = max(
    //             0,
    //             (float) $contract->contract_value
    //           + (float) $contract->investor_profit
    //           - (float) $contract->discount_amount
    //         );
    //     });
    // }

    // العلاقات
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function guarantor()
    {
        return $this->belongsTo(Guarantor::class);
    }

    public function contractStatus()
    {
        return $this->belongsTo(ContractStatus::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function installmentType()
    {
        return $this->belongsTo(InstallmentType::class);
    }

    // علاقة المستثمرين (Many-to-Many مع بيانات إضافية في الـ pivot)
    public function investors()
    {
        return $this->belongsToMany(Investor::class, 'contract_investor')
                    ->withPivot('share_percentage', 'share_value', 'office_share_percentage')
                    ->withTimestamps();
    }

    // علاقة الأقساط
    public function installments()
    {
        return $this->hasMany(ContractInstallment::class);
    }

    public function transactions()
    {
        return $this->hasMany(InvestorTransaction::class);
    }

    public function officeTransactions()
    {
        return $this->hasMany(OfficeTransaction::class, 'contract_id');
    }

    public function claims()
    {
        return $this->hasMany(ContractClaim::class);
    }

    public function notes()
    {
        return $this->hasMany(ContractNote::class)
            ->orderByDesc('note_date')
            ->orderByDesc('id');
    }

    /**
     * Calculate the remaining outstanding amount on the contract after
     * accounting for installment payments, claim payments, and applied discounts.
     */
    public function outstandingAmount(): float
    {
        $this->loadMissing([
            'installments:id,contract_id,payment_amount',
            'claims:id,contract_id,discount_amount',
            'claims.payments:id,contract_claim_id,amount',
        ]);

        $totalValue = round((float) ($this->total_value ?? 0), 2);

        $installmentPayments = 0.0;
        if ($this->relationLoaded('installments')) {
            $installmentPayments = (float) $this->installments
                ->sum(fn ($installment) => (float) ($installment->payment_amount ?? 0));
        } else {
            $installmentPayments = (float) $this->installments()->sum('payment_amount');
        }

        $claimPayments = 0.0;
        $claimDiscounts = 0.0;

        if ($this->relationLoaded('claims')) {
            foreach ($this->claims as $contractClaim) {
                $claimDiscounts += (float) ($contractClaim->discount_amount ?? 0);

                if ($contractClaim->relationLoaded('payments')) {
                    $claimPayments += (float) $contractClaim->payments
                        ->sum(fn ($payment) => (float) ($payment->amount ?? 0));
                } else {
                    $claimPayments += (float) $contractClaim->payments()->sum('amount');
                }
            }
        } else {
            $claimPayments = (float) ContractClaimPayment::query()
                ->whereHas('claim', fn ($query) => $query->where('contract_id', $this->id))
                ->sum('amount');

            $claimDiscounts = (float) ContractClaim::query()
                ->where('contract_id', $this->id)
                ->sum('discount_amount');
        }

        $outstanding = round($totalValue - $installmentPayments - $claimPayments - $claimDiscounts, 2);

        return $outstanding > 0 ? $outstanding : 0.0;
    }
}
