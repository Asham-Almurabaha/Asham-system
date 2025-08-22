<?php

namespace App\DTOs\CustomerDetails;

class CustomerDetailsResult
{
    public CustomerBasic $customer;

    /** @var ContractBrief[] */
    public array $active = [];
    /** @var ContractBrief[] */
    public array $finished = [];
    /** @var ContractBrief[] */
    public array $other = [];

    public int $total_contracts = 0;
    public int $active_count = 0;
    public int $finished_count = 0;
    public int $other_count = 0;

    /** @var array<int,array{id:?int,name:string,count:int,total_value_sum:float,formatted:array<string,string>}> */
    public array $statuses_breakdown = [];

    public InstallmentsSummary $installments_summary;

    public function toArray(): array
    {
        $map = fn(ContractBrief $c)=>$c->toArray();

        return [
            'customer' => $this->customer->toArray(),
            'contracts'=>[
                'active'=>array_map($map, $this->active),
                'finished'=>array_map($map, $this->finished),
                'other'=>array_map($map, $this->other),
            ],
            'contracts_summary'=>[
                'total'=>$this->total_contracts,
                'active'=>$this->active_count,
                'finished'=>$this->finished_count,
                'other'=>$this->other_count,
            ],
            'statuses_breakdown'=>$this->statuses_breakdown,
            'installments_summary'=>$this->installments_summary->toArray(),
        ];
    }
}
