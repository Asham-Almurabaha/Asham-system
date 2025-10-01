<?php

namespace Modules\Companies\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Companies\Entities\CompanyDisbursementStatus;

class CompaniesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'قيد الصرف',
                'description' => 'عملية صرف لم تُسدَّد بعد بالكامل',
                'is_default' => true,
            ],
            [
                'name' => 'مسددة جزئياً',
                'description' => 'تم تحصيل جزء من المبلغ فقط',
                'is_default' => false,
            ],
            [
                'name' => 'مسددة بالكامل',
                'description' => 'تم تحصيل كامل المديونية',
                'is_default' => false,
            ],
        ];

        foreach ($statuses as $status) {
            CompanyDisbursementStatus::query()->updateOrCreate(
                ['name' => $status['name']],
                $status
            );
        }
    }
}
