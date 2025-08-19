<?php

namespace App\DTOs;

final class AccountMovement
{
    public function __construct(
        public string $accountType, // bank/safe/other
        public int $accountId,
        public string $accountName,
        public float $debit,
        public float $credit,
        public float $balance // credit - debit (أو حسب نظامك)
    ) {}
}

