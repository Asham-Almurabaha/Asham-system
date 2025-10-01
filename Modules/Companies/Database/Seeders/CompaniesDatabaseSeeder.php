<?php

namespace Modules\Companies\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Companies\Entities\CompanyDisbursementStatus;

class CompaniesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'قيد الصرف',
            'مسددة جزئياً',
            'مسددة بالكامل',
        ];

        foreach ($statuses as $name) {
            CompanyDisbursementStatus::query()->firstOrCreate([
                'name' => $name,
            ]);
        }
    }
}
