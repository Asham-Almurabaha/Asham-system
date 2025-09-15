<?php

namespace Modules\Contracts\Imports;

use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractInstallment;
use Modules\Customers\Entities\Customer;
use Modules\Guarantors\Entities\Guarantor;
use App\Models\InstallmentStatus;
use App\Models\InstallmentType;
use App\Models\ProductType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContractsBasicImport implements ToCollection, WithHeadingRow
{
    use SkipsErrors, SkipsFailures;

    private int $rows = 0;
    private int $inserted = 0;
    private int $skipped = 0;

    /** @var array<int,array{row:int,attribute?:string|array,values:array,messages:array}> */
    private array $failuresSimple = [];
    /** @var string[] */
    private array $errorsSimple = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $raw) {
            $this->rows++;

            $rowNum = $i + 2; // مع صف العناوين
            $data   = $this->normalize($raw->toArray());

            try {
                DB::transaction(function () use ($data, $rowNum) {
                    if (!empty($data['contract_number'])) {
                        $exists = Contract::where('contract_number', $data['contract_number'])->first();
                        if ($exists) {
                            $this->skipped++;
                            $this->pushFailure($rowNum, 'contract_number', $data, ['موجود مسبقًا.']);
                            return;
                        }
                    }

                    $customerId    = $this->resolveCustomerId($data);
                    $guarantorId   = $this->resolveGuarantorId($data);
                    $productTypeId = $this->resolveProductTypeId($data);
                    $installTypeId = $this->resolveInstallmentTypeId($data);

                    if (!$customerId)    throw new \RuntimeException('customer_id/customer غير صحيح.');
                    if (!$productTypeId) throw new \RuntimeException('product_type_id / product_type غير صحيح.');
                    if (!$installTypeId) throw new \RuntimeException('installment_type_id / installment_type غير صحيح.');

                    $sale          = (float) ($data['sale_price'] ?? 0);
                    $contractValue = (float) ($data['contract_value'] ?? $sale);
                    $profit        = (float) ($data['investor_profit'] ?? 0);
                    $totalValue    = (float) ($data['total_value'] ?? ($contractValue + $profit));

                    $startDate = $this->toDate($data['start_date'] ?? null) ?? now()->toDateString();
                    $firstDate = $this->toDate($data['first_installment_date'] ?? null);

                    $payload = [
                        'contract_number'         => $data['contract_number'] ?? (date('Ymd').rand(10,99)),
                        'customer_id'             => $customerId,
                        'guarantor_id'            => $guarantorId,
                        'contract_status_id'      => null,
                        'product_type_id'         => $productTypeId,
                        'products_count'          => (int) ($data['products_count'] ?? 0),
                        'purchase_price'          => (float) ($data['purchase_price'] ?? 0),
                        'sale_price'              => $sale,
                        'contract_value'          => $contractValue,
                        'investor_profit'         => $profit,
                        'total_value'             => $totalValue,
                        'discount_amount'         => (float) ($data['discount_amount'] ?? 0),
                        'installment_type_id'     => $installTypeId,
                        'installment_value'       => (float) ($data['installment_value'] ?? 0),
                        'installments_count'      => (int)   ($data['installments_count'] ?? 0),
                        'start_date'              => $startDate,
                        'first_installment_date'  => $firstDate,
                        'contract_image'          => $data['contract_image']          ?? null,
                        'contract_customer_image' => $data['contract_customer_image'] ?? null,
                        'contract_guarantor_image'=> $data['contract_guarantor_image']?? null,
                    ];

                    /** @var Contract $contract */
                    $contract = Contract::create($payload);

                    $this->createInstallmentsForContract(
                        $contract,
                        $payload['total_value'],
                        $payload['installment_value'],
                        $firstDate ?: $startDate,
                        $installTypeId
                    );

                    $this->inserted++;
                });
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errorsSimple[] = "صف {$rowNum}: " . $e->getMessage();
                $this->pushFailure($rowNum, '*', $data, [$e->getMessage()]);
            }
        }
    }

    private function normalize(array $arr): array
    {
        $map = [
            'customer'         => 'customer_name',
            'guarantor'        => 'guarantor_name',
            'product_type'     => 'product_type_name',
            'installment_type' => 'installment_type_name',
        ];
        foreach ($map as $from => $to) {
            if (isset($arr[$from]) && !isset($arr[$to])) $arr[$to] = $arr[$from];
        }
        return $arr;
    }

    private function resolveCustomerId(array $d): ?int
    {
        if (!empty($d['customer_id'])) return (int)$d['customer_id'];
        if (!empty($d['customer_national_id'])) {
            return Customer::where('national_id', $d['customer_national_id'])->value('id');
        }
        if (!empty($d['customer_name'])) {
            return Customer::where('name', $d['customer_name'])->value('id');
        }
        return null;
    }

    private function resolveGuarantorId(array $d): ?int
    {
        if (!empty($d['guarantor_id'])) return (int)$d['guarantor_id'];
        if (!empty($d['guarantor_national_id'])) {
            return Guarantor::where('national_id', $d['guarantor_national_id'])->value('id');
        }
        if (!empty($d['guarantor_name'])) {
            return Guarantor::where('name', $d['guarantor_name'])->value('id');
        }
        return null;
    }

    private function resolveProductTypeId(array $d): ?int
    {
        if (!empty($d['product_type_id'])) return (int)$d['product_type_id'];
        if (!empty($d['product_type_name'])) {
            return ProductType::where('name', $d['product_type_name'])->value('id');
        }
        return null;
    }

    private function resolveInstallmentTypeId(array $d): ?int
    {
        if (!empty($d['installment_type_id'])) return (int)$d['installment_type_id'];
        if (!empty($d['installment_type_name'])) {
            return InstallmentType::where('name', $d['installment_type_name'])->value('id');
        }
        return null;
    }

    private function toDate($v): ?string
    {
        if ($v === null || $v === '') return null;

        if (is_numeric($v) && (float) $v > 10000) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v);
                return Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function createInstallmentsForContract(Contract $contract, float $totalValue, float $installmentValue, string $base, int $installmentTypeId): void
    {
        $statuses = InstallmentStatus::pluck('id', 'name');

        $baseDate = Carbon::parse($base);
        $typeName = optional(InstallmentType::find($installmentTypeId))->name;

        $computeDueDate = function (Carbon $base, int $i) use ($typeName) {
            $t = mb_strtolower((string)$typeName);
            $step = max(0, $i-1);
            if (str_contains($t,'يوم') || str_contains($t,'daily'))  return $base->copy()->addDays($step);
            if (str_contains($t,'أسبوع')|| str_contains($t,'week'))  return $base->copy()->addWeeks($step);
            if (str_contains($t,'سنة') || str_contains($t,'year'))   return $base->copy()->addYears($step);
            return $base->copy()->addMonthsNoOverflow($step);
        };

        if ($installmentValue > 0.0) {
            $count = (int) floor($totalValue / $installmentValue);
            $remaining = round($totalValue - ($count*$installmentValue), 2);

            for ($i=1; $i <= $count; $i++) {
                ContractInstallment::create([
                    'contract_id'           => $contract->id,
                    'installment_number'    => $i,
                    'due_date'              => $computeDueDate($baseDate, $i),
                    'due_amount'            => $installmentValue,
                    'payment_amount'        => 0,
                    'installment_status_id' => $statuses['لم يحل'] ?? null,
                ]);
            }
            if ($remaining > 0) {
                ContractInstallment::create([
                    'contract_id'           => $contract->id,
                    'installment_number'    => $count + 1,
                    'due_date'              => $computeDueDate($baseDate, $count+1),
                    'due_amount'            => $remaining,
                    'payment_amount'        => 0,
                    'installment_status_id' => $statuses['لم يحل'] ?? null,
                ]);
            }
        } elseif ($totalValue > 0.0) {
            ContractInstallment::create([
                'contract_id'           => $contract->id,
                'installment_number'    => 1,
                'due_date'              => $baseDate,
                'due_amount'            => $totalValue,
                'payment_amount'        => 0,
                'installment_status_id' => $statuses['لم يحل'] ?? null,
            ]);
        }
    }

    public function getRowCount(): int { return $this->rows; }
    public function getInsertedCount(): int { return $this->inserted; }
    public function getUpdatedCount(): int { return 0; }
    public function getUnchangedCount(): int { return 0; }
    public function getSkippedCount(): int { return $this->skipped; }
    public function getFailuresSimple(): array { return $this->failuresSimple; }
    public function getErrorsSimple(): array { return $this->errorsSimple; }

    private function pushFailure(int $row, string|array $attribute, array $values, array $messages): void
    {
        $this->failuresSimple[] = [
            'row' => $row,
            'attribute' => $attribute,
            'values' => $values,
            'messages' => $messages,
        ];
    }
}
