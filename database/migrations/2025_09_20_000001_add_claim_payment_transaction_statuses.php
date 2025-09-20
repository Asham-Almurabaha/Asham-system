<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transaction_statuses') || !Schema::hasTable('transaction_types')) {
            return;
        }

        $depositTypeId = DB::table('transaction_types')
            ->whereIn('name', ['إيداع', 'وارد'])
            ->value('id');

        if (!$depositTypeId) {
            return;
        }

        $now = Carbon::now();

        $statuses = [
            [
                'name'     => 'سداد مطالبة',
                'aliases'  => ['سداد مطالبه', 'سداد مطالبة للمستثمرين', 'سداد مطالبه للمستثمرين'],
                'category' => 'المستثمرين',
            ],
            [
                'name'     => 'محاماة مطالبة',
                'aliases'  => ['محاماه مطالبه'],
                'category' => 'المكتب',
            ],
        ];

        foreach ($statuses as $config) {
            $status = DB::table('transaction_statuses')
                ->where('name', $config['name'])
                ->first(['id', 'name', 'transaction_type_id']);

            if (!$status && !empty($config['aliases'])) {
                $status = DB::table('transaction_statuses')
                    ->whereIn('name', $config['aliases'])
                    ->first(['id', 'name', 'transaction_type_id']);
            }

            if ($status) {
                if ($status->name !== $config['name']) {
                    DB::table('transaction_statuses')
                        ->where('id', $status->id)
                        ->update([
                            'name'       => $config['name'],
                            'updated_at' => $now,
                        ]);

                    $status->name = $config['name'];
                }

                if (!$status->transaction_type_id) {
                    DB::table('transaction_statuses')
                        ->where('id', $status->id)
                        ->update([
                            'transaction_type_id' => $depositTypeId,
                            'updated_at'          => $now,
                        ]);

                    $status->transaction_type_id = $depositTypeId;
                }

                $statusId = $status->id;
            } else {
                $statusId = DB::table('transaction_statuses')->insertGetId([
                    'name'                => $config['name'],
                    'transaction_type_id' => $depositTypeId,
                    'is_protected'        => true,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
            }

            if (!Schema::hasTable('category_transaction_status') || !Schema::hasTable('categories')) {
                continue;
            }

            $categoryId = DB::table('categories')
                ->where('name', $config['category'])
                ->value('id');

            if (!$categoryId) {
                continue;
            }

            $exists = DB::table('category_transaction_status')
                ->where('transaction_status_id', $statusId)
                ->where('category_id', $categoryId)
                ->exists();

            if (!$exists) {
                DB::table('category_transaction_status')->insert([
                    'transaction_status_id' => $statusId,
                    'category_id'           => $categoryId,
                    'is_protected'          => true,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('transaction_statuses')) {
            return;
        }

        $names = [
            'سداد مطالبة',
            'سداد مطالبه',
            'سداد مطالبة للمستثمرين',
            'سداد مطالبه للمستثمرين',
            'محاماة مطالبة',
            'محاماه مطالبه',
        ];

        if (Schema::hasTable('category_transaction_status')) {
            $statusIds = DB::table('transaction_statuses')
                ->whereIn('name', $names)
                ->pluck('id');

            if ($statusIds->isNotEmpty()) {
                DB::table('category_transaction_status')
                    ->whereIn('transaction_status_id', $statusIds)
                    ->delete();
            }
        }

        DB::table('transaction_statuses')
            ->whereIn('name', $names)
            ->delete();
    }
};
