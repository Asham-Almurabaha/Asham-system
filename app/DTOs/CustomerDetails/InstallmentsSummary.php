<?php

namespace App\DTOs\CustomerDetails;

class InstallmentsSummary
{
    public int $total_installments;
    public float $total_due_amount;
    public float $total_paid_amount;
    public float $total_unpaid_amount;
    public int $overdue_count;
    public float $overdue_amount;
    public ?string $next_due_date;   // 'Y-m-d'
    public ?string $last_payment_date;

    public function __construct(
        int $total_installments,
        float $total_due_amount,
        float $total_paid_amount,
        float $total_unpaid_amount,
        int $overdue_count,
        float $overdue_amount,
        ?string $next_due_date,
        ?string $last_payment_date
    ) {
        $this->total_installments = $total_installments;
        $this->total_due_amount = round($total_due_amount, 2);
        $this->total_paid_amount = round($total_paid_amount, 2);
        $this->total_unpaid_amount = round($total_unpaid_amount, 2);
        $this->overdue_count = $overdue_count;
        $this->overdue_amount = round($overdue_amount, 2);
        $this->next_due_date = $next_due_date;
        $this->last_payment_date = $last_payment_date;
    }

    public function toArray(): array
    {
        return [
            'total_installments'=>$this->total_installments,
            'total_due_amount'=>$this->total_due_amount,
            'total_paid_amount'=>$this->total_paid_amount,
            'total_unpaid_amount'=>$this->total_unpaid_amount,
            'overdue_count'=>$this->overdue_count,
            'overdue_amount'=>$this->overdue_amount,
            'next_due_date'=>$this->next_due_date,
            'last_payment_date'=>$this->last_payment_date,
            'formatted'=>[
                'due_amount'=>number_format($this->total_due_amount,2),
                'paid_amount'=>number_format($this->total_paid_amount,2),
                'unpaid_amount'=>number_format($this->total_unpaid_amount,2),
                'overdue_amount'=>number_format($this->overdue_amount,2),
            ],
        ];
    }
}
