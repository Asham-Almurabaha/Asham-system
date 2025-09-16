<?php

namespace Modules\Accounts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        if (Schema::hasTable('bank_accounts')) {
            $this->seedBankAccounts($now);
        }

        if (Schema::hasTable('safes')) {
            $this->seedSafes($now);
        }
    }

    private function seedBankAccounts(Carbon $now): void
    {
        $records = [
            [
                'name'            => 'الحساب الرئيسي',
                'bank_name'       => 'البنك الراجحي',
                'account_number'  => '1234567890',
                'iban'            => 'SA1234567890123456789012',
                'opening_balance' => 0,
                'currency_code'   => 'SAR',
                'is_active'       => true,
                'notes'           => null,
            ],
            [
                'name'            => 'حساب الفرع الثاني',
                'bank_name'       => 'البنك العربي',
                'account_number'  => '0987654321',
                'iban'            => 'SA0987654321098765432109',
                'opening_balance' => 0,
                'currency_code'   => 'SAR',
                'is_active'       => true,
                'notes'           => null,
            ],
            [
                'name'            => 'حساب احتياطي',
                'bank_name'       => null,
                'account_number'  => null,
                'iban'            => null,
                'opening_balance' => 0,
                'currency_code'   => 'SAR',
                'is_active'       => false,
                'notes'           => null,
            ],
        ];

        foreach ($records as $record) {
            $existing = DB::table('bank_accounts')->where('name', $record['name'])->first();

            $payload = array_merge($record, ['updated_at' => $now]);

            if ($existing) {
                DB::table('bank_accounts')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('bank_accounts')->insert(array_merge($payload, ['created_at' => $now]));
        }
    }

    private function seedSafes(Carbon $now): void
    {
        $records = [
            [
                'name'            => 'الخزنة الرئيسية',
                'location'        => 'المكتب الرئيسي',
                'opening_balance' => 0,
                'currency_code'   => 'SAR',
                'is_active'       => true,
                'notes'           => null,
            ],
            [
                'name'            => 'خزنة الفرع الثاني',
                'location'        => 'فرع الرياض',
                'opening_balance' => 0,
                'currency_code'   => 'SAR',
                'is_active'       => true,
                'notes'           => null,
            ],
            [
                'name'            => 'خزنة احتياطية',
                'location'        => null,
                'opening_balance' => 0,
                'currency_code'   => 'SAR',
                'is_active'       => false,
                'notes'           => null,
            ],
        ];

        foreach ($records as $record) {
            $existing = DB::table('safes')->where('name', $record['name'])->first();

            $payload = array_merge($record, ['updated_at' => $now]);

            if ($existing) {
                DB::table('safes')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('safes')->insert(array_merge($payload, ['created_at' => $now]));
        }
    }
}
