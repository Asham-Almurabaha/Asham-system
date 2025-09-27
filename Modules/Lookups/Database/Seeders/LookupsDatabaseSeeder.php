<?php

namespace Modules\Lookups\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LookupsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $this->syncNamedRecords('nationalities', [
            ['name' => 'مصري'],
            ['name' => 'سعودي'],
            ['name' => 'سوري'],
            ['name' => 'سوداني'],
            ['name' => 'باكستاني'],
        ], $now);

        $this->syncNamedRecords('titles', [
            ['name' => 'حكومي'],
            ['name' => 'عسكري'],
            ['name' => 'قطاع خاص'],
            ['name' => 'مقيم'],
            ['name' => 'لا يعمل'],
        ], $now);

        $this->syncNamedRecords('contract_statuses', [
            ['name' => 'بدون مستثمر', 'is_protected' => true],
            ['name' => 'معلق', 'is_protected' => true],
            ['name' => 'جديد', 'is_protected' => true],
            ['name' => 'منتهي', 'is_protected' => true],
            ['name' => 'سداد مبكر', 'is_protected' => true],
            ['name' => 'مطلوب', 'is_protected' => true],
            ['name' => 'منتظم', 'is_protected' => true],
            ['name' => 'غير منتظم', 'is_protected' => true],
            ['name' => 'متأخر', 'is_protected' => true],
            ['name' => 'متعثر', 'is_protected' => true],
            ['name' => 'مرفوع فيه', 'is_protected' => true],
            ['name' => 'منتهي بمطالبة', 'is_protected' => true],
        ], $now);

        $this->syncNamedRecords('claim_statuses', [
            ['name' => 'قيد المراجعة', 'is_protected' => true],
            ['name' => 'مرفوض', 'is_protected' => true],
            ['name' => 'مقبول', 'is_protected' => true],
            ['name' => 'مدفوع جزئي', 'is_protected' => true],
            ['name' => 'مدفوع بخصم', 'is_protected' => true],
            ['name' => 'مدفوع كامل', 'is_protected' => true],
        ], $now);

        $this->syncNamedRecords('claim_payers', [
            ['name' => 'المحكمة', 'is_protected' => true],
            ['name' => 'العميل', 'is_protected' => true],
            ['name' => 'الكفيل', 'is_protected' => true],
        ], $now);

        $this->syncNamedRecords('claimants', [
            ['name' => 'المطالب الافتراضي'],
        ], $now);

        $this->syncNamedRecords('customer_statuses', [
            ['name' => 'جديد', 'is_protected' => true],
            ['name' => 'ملتزم', 'is_protected' => true],
            ['name' => 'غير ملتزم', 'is_protected' => true],
            ['name' => 'غير نشط', 'is_protected' => true],
            ['name' => 'مرفوع فيه', 'is_protected' => true],
            ['name' => 'قائمة سوداء', 'is_protected' => true],
        ], $now);

        $this->syncNamedRecords('guarantor_statuses', [
            ['name' => 'جديد', 'is_protected' => true],
            ['name' => 'ملتزم', 'is_protected' => true],
            ['name' => 'غير ملتزم', 'is_protected' => true],
            ['name' => 'غير نشط', 'is_protected' => true],
            ['name' => 'مرفوع فيه', 'is_protected' => true],
            ['name' => 'قائمة سوداء', 'is_protected' => true],
        ], $now);

        $this->syncNamedRecords('installment_statuses', [
            ['name' => 'لم يحل', 'is_protected' => true],
            ['name' => 'متأخر', 'is_protected' => true],
            ['name' => 'مؤجل', 'is_protected' => true],
            ['name' => 'معتذر', 'is_protected' => true],
            ['name' => 'مستحق', 'is_protected' => true],
            ['name' => 'مدفوع كامل', 'is_protected' => true],
            ['name' => 'مدفوع مبكر', 'is_protected' => true],
            ['name' => 'مدفوع جزئي', 'is_protected' => true],
            ['name' => 'مدفوع متأخر', 'is_protected' => true],
        ], $now);

        $this->syncNamedRecords('installment_types', [
            ['name' => 'سنوي', 'is_protected' => true],
            ['name' => 'شهري', 'is_protected' => true],
            ['name' => 'اسبوعي', 'is_protected' => true],
            ['name' => 'يومي', 'is_protected' => true],
        ], $now);

        $transactionTypeIds = $this->syncNamedRecords('transaction_types', [
            ['name' => 'إيداع', 'is_protected' => true, 'description' => 'إيداع نقدي في الحساب'],
            ['name' => 'سحب', 'is_protected' => true, 'description' => 'سحب نقدي من الحساب'],
            ['name' => 'تحويل بين حسابات', 'is_protected' => true, 'description' => 'عملية تحويل بين حسابات'],
        ], $now);

        $transactionStatusIds = $this->seedTransactionStatuses($now, $transactionTypeIds);

        $categoryIds = $this->syncNamedRecords('categories', [
            ['name' => 'المستثمرين', 'is_protected' => true],
            ['name' => 'العقود', 'is_protected' => true],
            ['name' => 'البضائع', 'is_protected' => true],
            ['name' => 'المكتب', 'is_protected' => true],
            ['name' => 'الاقساط', 'is_protected' => true],
        ], $now);

        $this->syncNamedRecords('product_types', [
            ['name' => 'بطاقات', 'is_protected' => true],
            ['name' => 'جوال', 'is_protected' => true],
            ['name' => 'سيارة', 'is_protected' => true],
        ], $now);

        $this->seedCategoryTransactionStatuses($now, $transactionStatusIds, $categoryIds);

        $recurrencePeriodIds = $this->syncNamedRecords('expense_recurrence_periods', [
            ['name' => 'شهري', 'description' => 'يتكرر كل شهر', 'is_protected' => true],
            ['name' => 'نصف سنوي', 'description' => 'يتكرر مرتين في السنة', 'is_protected' => true],
            ['name' => 'أخرى', 'description' => 'فترة تكرار مخصصة أو غير منتظمة', 'is_protected' => true],
        ], $now);

        $monthlyRecurrenceId = $recurrencePeriodIds['شهري'] ?? null;
        $otherRecurrenceId = $recurrencePeriodIds['أخرى'] ?? null;

        $this->syncNamedRecords('expense_types', [
            [
                'name' => 'الإيجارات',
                'description' => 'المصروفات الخاصة بإيجار المكاتب أو الفروع',
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'expense_recurrence_period_id' => $monthlyRecurrenceId,
            ],
            [
                'name' => 'أقساط السيارات',
                'description' => 'الأقساط الشهرية لتمويل السيارات التابعة للشركة',
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'expense_recurrence_period_id' => $monthlyRecurrenceId,
            ],
            [
                'name' => 'أقساط القروض',
                'description' => 'أقساط التمويلات أو القروض البنكية',
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'expense_recurrence_period_id' => $monthlyRecurrenceId,
            ],
            [
                'name' => 'الرواتب',
                'description' => 'رواتب وأجور الموظفين والعاملين',
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'expense_recurrence_period_id' => $monthlyRecurrenceId,
            ],
            [
                'name' => 'مصروفات أخرى',
                'description' => 'مصروفات متنوعة لا تنتمي للأنواع الرئيسية',
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => false,
                'expense_recurrence_period_id' => $otherRecurrenceId,
            ],
        ], $now);
    }

    private function seedTransactionStatuses(Carbon $now, array $transactionTypeIds): array
    {
        if (!Schema::hasTable('transaction_statuses')) {
            return [];
        }

        $statuses = [
            ['name' => 'شراء بضائع', 'type' => 'سحب'],
            ['name' => 'بيع بضائع', 'type' => 'إيداع'],
            ['name' => 'إضافة عقد', 'type' => 'سحب'],
            ['name' => 'سداد قسط', 'type' => 'إيداع'],
            ['name' => 'المكاتبة', 'type' => 'إيداع'],
            ['name' => 'فرق البيع', 'type' => 'إيداع'],
            ['name' => 'ربح المكتب', 'type' => 'إيداع'],
            ['name' => 'سحب سيولة', 'type' => 'سحب'],
            ['name' => 'إضافة سيولة', 'type' => 'إيداع'],
            ['name' => 'سداد مطالبة', 'type' => 'إيداع'],
            ['name' => 'محاماة مطالبة', 'type' => 'إيداع'],
            ['name' => 'إيداع حسابات', 'type' => 'إيداع'],
            ['name' => 'سحب حسابات', 'type' => 'سحب'],
            ['name' => 'تحويل بين حسابات', 'type' => 'تحويل بين حسابات'],
            ['name' => 'رصيد افتتاحي', 'type' => 'إيداع'],
            ['name' => 'رأس المال', 'type' => 'إيداع'],
            ['name' => 'زكاة المال', 'type' => 'سحب'],
            ['name' => 'مديونية', 'type' => 'سحب'],
            ['name' => 'سداد مديونية', 'type' => 'إيداع'],

        ];

        $records = [];
        foreach ($statuses as $status) {
            $typeId = $transactionTypeIds[$status['type']] ?? null;
            if (!$typeId) {
                continue;
            }

            $records[] = [
                'name' => $status['name'],
                'transaction_type_id' => $typeId,
                'is_protected' => true,
            ];
        }

        return $this->syncNamedRecords('transaction_statuses', $records, $now);
    }

    private function seedCategoryTransactionStatuses(Carbon $now, array $transactionStatusIds, array $categoryIds): void
    {
        if (!Schema::hasTable('category_transaction_status')) {
            return;
        }

        $pairs = [
            ['status' => 'إضافة عقد', 'category' => 'المستثمرين'],
            ['status' => 'سداد قسط', 'category' => 'المستثمرين'],
            ['status' => 'سحب سيولة', 'category' => 'المستثمرين'],
            ['status' => 'إضافة سيولة', 'category' => 'المستثمرين'],
            ['status' => 'شراء بضائع', 'category' => 'البضائع'],
            ['status' => 'بيع بضائع', 'category' => 'البضائع'],
            ['status' => 'إضافة عقد', 'category' => 'البضائع'],
            ['status' => 'شراء بضائع', 'category' => 'المكتب'],
            ['status' => 'بيع بضائع', 'category' => 'المكتب'],
            ['status' => 'المكاتبة', 'category' => 'المكتب'],
            ['status' => 'فرق البيع', 'category' => 'المكتب'],
            ['status' => 'إيداع حسابات', 'category' => 'المكتب'],
            ['status' => 'سحب حسابات', 'category' => 'المكتب'],
            ['status' => 'تحويل بين حسابات', 'category' => 'المكتب'],
            ['status' => 'رصيد افتتاحي', 'category' => 'المكتب'],
            ['status' => 'سداد مطالبة', 'category' => 'المستثمرين'],
            ['status' => 'محاماة مطالبة', 'category' => 'المكتب'],
            ['status' => 'رأس المال', 'category' => 'المستثمرين'],
            ['status' => 'زكاة المال', 'category' => 'المستثمرين'],
            ['status' => 'مديونية', 'category' => 'المكتب'],
            ['status' => 'مديونية', 'category' => 'المستثمرين'],
            ['status' => 'سداد مديونية', 'category' => 'المكتب'],
            ['status' => 'سداد مديونية', 'category' => 'المستثمرين'],

        ];

        foreach ($pairs as $pair) {
            $statusId = $transactionStatusIds[$pair['status']] ?? null;
            $categoryId = $categoryIds[$pair['category']] ?? null;

            if (!$statusId || !$categoryId) {
                continue;
            }

            $existing = DB::table('category_transaction_status')
                ->where('transaction_status_id', $statusId)
                ->where('category_id', $categoryId)
                ->first();

            $payload = [
                'is_protected' => true,
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('category_transaction_status')
                    ->where('id', $existing->id)
                    ->update($payload);

                continue;
            }

            DB::table('category_transaction_status')->insert(array_merge($payload, [
                'transaction_status_id' => $statusId,
                'category_id' => $categoryId,
                'created_at' => $now,
            ]));
        }
    }

    /**
     * @return array<string, int>
     */
    private function syncNamedRecords(string $table, array $records, Carbon $now, string $uniqueKey = 'name'): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $ids = [];

        foreach ($records as $record) {
            if (!array_key_exists($uniqueKey, $record)) {
                continue;
            }

            $key = $record[$uniqueKey];
            $attributes = [$uniqueKey => $key];
            $values = array_diff_key($record, [$uniqueKey => null]);
            $values['updated_at'] = $now;

            $existing = DB::table($table)->where($uniqueKey, $key)->first();

            if ($existing) {
                DB::table($table)
                    ->where('id', $existing->id)
                    ->update($values);

                $ids[(string) $key] = (int) $existing->id;
                continue;
            }

            $values['created_at'] = $now;
            $id = DB::table($table)->insertGetId(array_merge($attributes, $values));
            $ids[(string) $key] = (int) $id;
        }

        return $ids;
    }
}
