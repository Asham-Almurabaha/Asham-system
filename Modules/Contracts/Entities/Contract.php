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
                    ->withPivot('share_percentage', 'share_value')
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


}
