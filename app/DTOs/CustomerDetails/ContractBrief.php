<?php

namespace App\DTOs\CustomerDetails;

class ContractBrief
{
    public int $id;
    public string $contract_number;
    public ?string $start_date; // 'Y-m-d'
    public ?int $status_id;
    public ?string $status_name;
    public int $product_type_id;
    public ?string $product_type_name;

    public int $products_count;
    public float $purchase_price;
    public float $sale_price;
    public float $contract_value;
    public float $investor_profit;
    public float $total_value;
    public float $discount_amount;

    // installments slice
    public int $installments_count;
    public int $paid_count;
    public int $unpaid_count;
    public float $due_sum;
    public float $paid_sum;
    public float $unpaid_sum;
    public int $overdue_count;
    public float $overdue_sum;
    public ?string $next_due_date;
    public ?string $last_payment_date;

    public float $remaining_amount; // = unpaid_sum

    public function __construct(
        int $id,
        string $contract_number,
        ?string $start_date,
        ?int $status_id,
        ?string $status_name,
        int $product_type_id,
        ?string $product_type_name,
        int $products_count,
        float $purchase_price,
        float $sale_price,
        float $contract_value,
        float $investor_profit,
        float $total_value,
        float $discount_amount,
        int $installments_count,
        int $paid_count,
        int $unpaid_count,
        float $due_sum,
        float $paid_sum,
        float $unpaid_sum,
        int $overdue_count,
        float $overdue_sum,
        ?string $next_due_date,
        ?string $last_payment_date
    ) {
        $this->id=$id; $this->contract_number=$contract_number; $this->start_date=$start_date;
        $this->status_id=$status_id; $this->status_name=$status_name;
        $this->product_type_id=$product_type_id; $this->product_type_name=$product_type_name;

        $this->products_count=$products_count;
        $this->purchase_price=round($purchase_price,2);
        $this->sale_price=round($sale_price,2);
        $this->contract_value=round($contract_value,2);
        $this->investor_profit=round($investor_profit,2);
        $this->total_value=round($total_value,2);
        $this->discount_amount=round($discount_amount,2);

        $this->installments_count=$installments_count;
        $this->paid_count=$paid_count;
        $this->unpaid_count=$unpaid_count;

        $this->due_sum=round($due_sum,2);
        $this->paid_sum=round($paid_sum,2);
        $this->unpaid_sum=round($unpaid_sum,2);
        $this->overdue_count=$overdue_count;
        $this->overdue_sum=round($overdue_sum,2);
        $this->next_due_date=$next_due_date;
        $this->last_payment_date=$last_payment_date;

        $this->remaining_amount = $this->unpaid_sum;
    }

    public function toArray(): array
    {
        return [
            'id'=>$this->id,
            'contract_number'=>$this->contract_number,
            'start_date'=>$this->start_date,
            'status'=>['id'=>$this->status_id,'name'=>$this->status_name],
            'product_type'=>['id'=>$this->product_type_id,'name'=>$this->product_type_name],

            'products_count'=>$this->products_count,
            'purchase_price'=>$this->purchase_price,
            'sale_price'=>$this->sale_price,
            'contract_value'=>$this->contract_value,
            'investor_profit'=>$this->investor_profit,
            'total_value'=>$this->total_value,
            'discount_amount'=>$this->discount_amount,

            'installments'=>[
                'count'=>$this->installments_count,
                'paid_count'=>$this->paid_count,
                'unpaid_count'=>$this->unpaid_count,
                'due_sum'=>$this->due_sum,
                'paid_sum'=>$this->paid_sum,
                'unpaid_sum'=>$this->unpaid_sum,
                'overdue_count'=>$this->overdue_count,
                'overdue_sum'=>$this->overdue_sum,
                'next_due_date'=>$this->next_due_date,
                'last_payment_date'=>$this->last_payment_date,
                'formatted'=>[
                    'due_sum'=>number_format($this->due_sum,2),
                    'paid_sum'=>number_format($this->paid_sum,2),
                    'unpaid_sum'=>number_format($this->unpaid_sum,2),
                    'overdue_sum'=>number_format($this->overdue_sum,2),
                ],
            ],

            'remaining_amount'=>$this->remaining_amount,
            'remaining_formatted'=>number_format($this->remaining_amount,2),
        ];
    }
}
