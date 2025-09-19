<?php

namespace Modules\Investors\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Carbon\CarbonInterface;
use Modules\Investors\Entities\Investor;

final class ZakatDueInvestor implements Arrayable
{
    public function __construct(
        public Investor $investor,
        public CarbonInterface $dueDate,
        public float $amount,
        public float $base,
        public ?int $daysOverdue,
        public float $rate,
        public ?CarbonInterface $startDate = null,
        public ?CarbonInterface $lastPaymentDate = null,
        public string $currencySymbol = 'ر.س',
    ) {}

    public function toArray(): array
    {
        return [
            'investor_id' => $this->investor->getKey(),
            'investor_name' => $this->investor->name,
            'due_date' => $this->dueDate->toDateString(),
            'amount' => $this->amount,
            'base' => $this->base,
            'rate' => $this->rate,
            'days_overdue' => $this->daysOverdue,
            'start_date' => $this->startDate?->toDateString(),
            'last_payment_date' => $this->lastPaymentDate?->toDateString(),
            'currency_symbol' => $this->currencySymbol,
        ];
    }

    public function dueDateForHumans(): string
    {
        return $this->dueDate->locale(app()->getLocale())->translatedFormat('Y-m-d');
    }
}
