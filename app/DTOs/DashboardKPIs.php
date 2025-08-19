<?php

namespace App\DTOs;

final class DashboardKPIs
{
    public function __construct(
        public int $contractsCount,
        public int $activeContracts,
        public float $contractsTotalValue,
        public float $contractsPaid,
        public float $contractsRemaining,
        public float $overdueInstallmentsAmount,
        public int $overdueInstallmentsCount,
        public float $upcomingInstallmentsAmount,
        public int $upcomingInstallmentsCount,
        public float $inventoryTotalAvailable, // مجموع المتاح في المخزون
        public float $profitTotal,             // إجمالي الربح لفترة العرض
        public float $maktabahTotal,           // إجمالي المكاتبة من الحالات (حسب تعريفك)
        public float $saleDiffTotal            // إجمالي فرق البيع
    ) {}
}
