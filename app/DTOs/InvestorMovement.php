<?php

namespace App\DTOs;

final class InvestorMovement
{
    public function __construct(
        public int $investorId,
        public string $investorName,
        public float $cashIn,          // إضافة سيولة
        public float $cashOut,         // سحب
        public int $contractsAdded,    // عقود مضافة
        public float $installmentsPaid // أقساط سُددت
    ) {}
}
