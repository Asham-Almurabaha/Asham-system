<?php

namespace App\DTOs;

use Carbon\Carbon;

final class RecentOfficeTx
{
    public function __construct(
        public int $id,
        public Carbon $date,
        public string $description,
        public float $profit,     // الربح
        public float $maktabah,   // المكاتبة
        public float $entries,    // القيود (عدد أو مبلغ — غيّره حسب نظامك)
        public float $saleDiff    // فرق البيع
    ) {}
}
