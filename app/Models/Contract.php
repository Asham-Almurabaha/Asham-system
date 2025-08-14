<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'customer_id',
        'guarantor_id',
        'contract_status_id',
        'contract_type_id',
        'products_count',
        'purchase_price',
        'sale_price',
        'contract_value',
        'investor_profit',
        'total_value',
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

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
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
}
