<?php

namespace Modules\Contracts\Imports;

use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractInstallment;
use Modules\Contracts\Entities\ContractStatus;
use Modules\Customers\Entities\Customer;
use Modules\Guarantors\Entities\Guarantor;
use App\Models\InstallmentStatus;
use App\Models\InstallmentType;
use App\Models\LedgerEntry;
use App\Models\ProductType;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Contracts\Imports\Concerns\HandlesContractInvestors;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContractsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    use SkipsErrors, SkipsFailures, HandlesContractInvestors;

    private int $rows = 0;
    private int $inserted = 0;
    private int $updated = 0;
    private int $unchanged = 0;
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
                    // عقد بنفس الرقم؟
                    if (!empty($data['contract_number'])) {
                        $exists = Contract::where('contract_number', $data['contract_number'])->first();
                        if ($exists) {
                            $this->skipped++;
                            $this->pushFailure($rowNum, 'contract_number', $data, ['موجود مسبقًا.']);
                            return;
                        }
                    }

                    // Resolve IDs
                    $customerId    = $this->resolveCustomerId($data);
                    $guarantorId   = $this->resolveGuarantorId($data);
                    $productTypeId = $this->resolveProductTypeId($data);
                    $installTypeId = $this->resolveInstallmentTypeId($data);

                    if (!$customerId)    throw new \RuntimeException('customer_id/customer غير صحيح.');
                    if (!$productTypeId) throw new \RuntimeException('product_type_id / product_type غير صحيح.');
                    if (!$installTypeId) throw new \RuntimeException('installment_type_id / installment_type غير صحيح.');

                    // قيم أساسية
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
                        'contract_status_id'      => null, // يتظبط بعد المستثمرين
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

                    // إنشاء جدول الأقساط
                    $this->createInstallmentsForContract(
                        $contract,
                        $payload['total_value'],
                        $payload['installment_value'],
                        $firstDate ?: $startDate,
                        $installTypeId
                    );

                    // المستثمرون (6 أعمدة أو عمود واحد)
                    $investors = $this->parseInvestorsFlexible($data);
                    $this->attachInvestorsAndAutoStatus($contract, $investors);

                    // قيد فرق البيع + product_transactions (اختياري)
                    $this->createSaleDiffLedgerEntry($contract, $payload);

                    // ===== السدادات: حتى 18 (amount+date) + عمود payments الموحد =====
                    $payments = $this->parsePaymentsFlexible($data);
                    if (!empty($payments)) {
                        // قيد دفتر لكل سداد (لو وُجد status/type مناسبين)
                        $this->createPaymentLedgerEntries($contract, $payments);
                        // توزيع السدادات على الأقساط FIFO وتحديث حالات الأقساط
                        $this->allocatePaymentsToInstallments($contract, $payments);
                    }

                    $this->inserted++;
                });

            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errorsSimple[] = "صف {$rowNum}: " . $e->getMessage();
                $this->pushFailure($rowNum, '*', $data, [$e->getMessage()]);
            }
        }
    }

    // ===== Helpers =====

    private function normalize(array $arr): array
    {
        // أسماء بديلة -> قياسية
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

    private function toDate(?string $v): ?string
    {
        if (!$v) return null;
        try { return Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable) { return null; }
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


    private function createSaleDiffLedgerEntry(Contract $contract, array $payload): void
    {
        $sale = (float)($payload['sale_price'] ?? 0);
        $buy  = (float)($payload['purchase_price'] ?? 0);
        $diff = round($sale - $buy, 2);
        if ($diff <= 0) return;

        $statusRow = TransactionStatus::whereIn('name', ['فرق البيع','ربح فرق البيع'])
            ->first(['id','transaction_type_id']);
        if (!$statusRow) return;

        $typeId = $statusRow->transaction_type_id
            ?: TransactionType::whereIn('name', ['ربح فرق البيع','فرق البيع','أرباح','تحصيل'])->value('id');

        if (!$typeId) return;

        $saleDiffEntry = LedgerEntry::create([
            'entry_date'            => now()->toDateString(),
            'investor_id'           => null,
            'is_office'             => true,
            'transaction_status_id' => $statusRow->id,
            'transaction_type_id'   => $typeId,
            'bank_account_id'       => null,
            'safe_id'               => null,
            'contract_id'           => $contract->id,
            'installment_id'        => null,
            'amount'                => $diff,
            'ref'                   => 'CT-'.$contract->id,
            'notes'                 => "قيد فرق البيع للعقد #{$contract->contract_number}",
        ]);

        // product_transactions (اختياري)
        try {
            if (Schema::hasTable('product_transactions') &&
                Schema::hasColumn('product_transactions','ledger_entry_id')) {

                $qty = (int)($payload['products_count'] ?? 0);
                $productTypeId = (int)($payload['product_type_id'] ?? 0);
                if ($productTypeId > 0 && Schema::hasTable('product_types')) {
                    if (!DB::table('product_types')->where('id',$productTypeId)->exists()) $productTypeId = 0;
                }

                $rec = [
                    'ledger_entry_id' => $saleDiffEntry->id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
                if (Schema::hasColumn('product_transactions','quantity')) {
                    $rec['quantity'] = max(0,$qty);
                }
                if (Schema::hasColumn('product_transactions','product_type_id')) {
                    $rec['product_type_id'] = $productTypeId ?: null;
                } elseif (Schema::hasColumn('product_transactions','goods_type_id')) {
                    $rec['goods_type_id'] = $productTypeId ?: null;
                }
                if (Schema::hasColumn('product_transactions','transaction_status_id')) {
                    $addId = TransactionStatus::where('name','إضافة عقد')->value('id');
                    if ($addId) $rec['transaction_status_id'] = $addId;
                }
                if (Schema::hasColumn('product_transactions','contract_id')) {
                    $rec['contract_id'] = $contract->id;
                }
                DB::table('product_transactions')->insert($rec);
            }
        } catch (\Throwable $ignore) {}
    }


    private function pushFailure(int $row, string $attr, array $vals, array $messages): void
    {
        $this->failuresSimple[] = [
            'row' => $row,
            'attribute' => $attr,
            'values' => $vals,
            'messages' => $messages,
        ];
    }

    // ===== السدادات (حتى 18 + العمود الموحد) =====

    /**
     * يقرا السدادات من:
     * - عمود payments: "date:amount|date:amount[#note]" أو "amount:date"
     * - أزواج حتى 18: payment{n}_amount + payment{n}_date (ويُقبل aliases: installment/qist/qst/qest)
     * - down_payment(+_date) أو first_payment_amount(+_date)
     * @return array<int,array{date:string,amount:float,notes?:string}>
     */
    private function parsePaymentsFlexible(array $d): array
    {
        $out = [];

        // 1) من عمود واحد
        $raw = trim((string)($d['payments'] ?? ''));
        if ($raw !== '') {
            foreach (explode('|', $raw) as $chunk) {
                $chunk = trim($chunk);
                if ($chunk === '') continue;

                $note = null;
                if (str_contains($chunk, '#')) {
                    [$chunk, $note] = array_map('trim', explode('#', $chunk, 2));
                }

                [$left, $right] = array_pad(array_map('trim', explode(':', $chunk, 2)), 2, null);
                if ($left === null || $right === null) continue;

                $isLeftDate  = (bool) strtotime($left);
                $isRightDate = (bool) strtotime($right);

                if ($isLeftDate && !$isRightDate) {
                    $date   = $this->toDate($left);
                    $amount = (float) $right;
                } elseif (!$isLeftDate && $isRightDate) {
                    $date   = $this->toDate($right);
                    $amount = (float) $left;
                } else {
                    continue;
                }

                if ($date && $amount > 0) {
                    $out[] = ['date'=>$date, 'amount'=>$amount, 'notes'=>$note];
                }
            }
        }

        // 2) من أعمدة منفصلة حتى 18
        for ($n=1; $n<=18; $n++) {
            $amountKeys = [
                "payment{$n}_amount", "payment{$n}_value",
                "installment{$n}_amount", "installment{$n}_value",
                "qist{$n}_amount", "qist{$n}_value",
                "qst{$n}_amount",  "qst{$n}_value",
                "qest{$n}_amount", "qest{$n}_value",
            ];
            $dateKeys = [
                "payment{$n}_date", "payment{$n}_at",
                "installment{$n}_date", "installment{$n}_at",
                "qist{$n}_date", "qist{$n}_at",
                "qst{$n}_date",  "qst{$n}_at",
                "qest{$n}_date", "qest{$n}_at",
            ];
            $notesKeys = [
                "payment{$n}_notes", "installment{$n}_notes",
                "qist{$n}_notes", "qst{$n}_notes", "qest{$n}_notes",
            ];

            $amt = null;
            foreach ($amountKeys as $k) {
                if (isset($d[$k]) && $d[$k] !== '') { $amt = (float)$d[$k]; break; }
            }

            $dat = null;
            foreach ($dateKeys as $k) {
                $dat = $this->toDate($d[$k] ?? null) ?? $dat;
                if ($dat) break;
            }

            $nts = null;
            foreach ($notesKeys as $k) {
                if (isset($d[$k]) && trim((string)$d[$k]) !== '') { $nts = (string)$d[$k]; break; }
            }

            if ($amt !== null && $amt > 0 && $dat) {
                $out[] = ['date'=>$dat, 'amount'=>$amt, 'notes'=>($nts ?: null)];
            }
        }

        // 3) دفعة أولى (اختياري)
        $downAmt = isset($d['down_payment']) ? (float)$d['down_payment']
                 : (isset($d['first_payment_amount']) ? (float)$d['first_payment_amount'] : null);
        $downDat = $this->toDate($d['down_payment_date'] ?? ($d['first_payment_date'] ?? null));
        if ($downAmt !== null && $downAmt > 0 && $downDat) {
            $out[] = ['date'=>$downDat, 'amount'=>$downAmt, 'notes'=>'دفعة أولى'];
        }

        // ترتيب بالتاريخ
        usort($out, fn($a,$b) => strcmp($a['date'],$b['date']));

        return $out;
    }

    /** ينشئ قيود دفتر لكل سداد (لو لقى حالة/نوع مناسبين) */
    private function createPaymentLedgerEntries(Contract $contract, array $payments): void
    {
        $status = TransactionStatus::whereIn('name', ['سداد قسط','تحصيل قسط','تحصيل'])->first(['id','transaction_type_id']);
        if (!$status) return;

        $typeId = $status->transaction_type_id
            ?: TransactionType::whereIn('name', ['سداد قسط','تحصيل قسط','تحصيل'])->value('id');
        if (!$typeId) return;

        foreach ($payments as $idx => $p) {
            $amount = (float)$p['amount'];
            $date   = (string)$p['date'];
            if ($amount <= 0 || !$date) continue;

            LedgerEntry::create([
                'entry_date'             => $date,
                'investor_id'            => null,
                'is_office'              => true,
                'transaction_status_id'  => $status->id,
                'transaction_type_id'    => $typeId,
                'bank_account_id'        => null,
                'safe_id'                => null,
                'contract_id'            => $contract->id,
                'installment_id'         => null,
                'amount'                 => $amount,
                'direction'              => 'in',
                'ref'                    => 'PY-'.$contract->id.'-'.($idx+1),
                'notes'                  => $p['notes'] ?? 'سداد قسط',
            ]);
        }
    }

    /** يوزع السدادات FIFO على الأقساط ويحدث حالة القسط (إن وُجدت) */
    private function allocatePaymentsToInstallments(Contract $contract, array $payments): void
    {
        /** @var \Illuminate\Support\Collection<int,ContractInstallment> $insts */
        $insts = ContractInstallment::where('contract_id', $contract->id)
            ->orderBy('due_date')->orderBy('id')->get();

        if ($insts->isEmpty()) return;

        // خريطة حالات الأقساط
        $st = InstallmentStatus::pluck('id','name');
        $paidId     = $st['مسدد'] ?? $st['مدفوع'] ?? $st['مدفوع بالكامل'] ?? null;
        $partialId  = $st['مدفوع جزئياً'] ?? $st['مسدد جزئياً'] ?? null;

        foreach ($payments as $p) {
            $left = (float)$p['amount'];
            if ($left <= 0) continue;

            foreach ($insts as $inst) {
                $due  = (float)$inst->due_amount;
                $paid = (float)$inst->payment_amount;

                if ($paid + 1e-9 >= $due) continue; // مكتمل

                $canPay = min($left, $due - $paid);
                if ($canPay <= 0) continue;

                $paid += $canPay;
                $left -= $canPay;

                $update = ['payment_amount' => $paid];

                // تحديث الحالة لو IDs متاحة
                if ($paidId || $partialId) {
                    if (abs($paid - $due) <= 0.0001) {
                        if ($paidId) $update['installment_status_id'] = $paidId;
                    } elseif ($paid > 0 && $paid < $due) {
                        if ($partialId) $update['installment_status_id'] = $partialId;
                    }
                }

                $inst->update($update);

                if ($left <= 0) break; // خلّصنا سداد واحد
            }
        }
    }

    // ===== Counters getters =====
    public function getRowCount(): int { return $this->rows; }
    public function getInsertedCount(): int { return $this->inserted; }
    public function getUpdatedCount(): int { return $this->updated; }
    public function getUnchangedCount(): int { return $this->unchanged; }
    public function getSkippedCount(): int { return $this->skipped; }
    public function getFailuresSimple(): array { return $this->failuresSimple; }
    public function getErrorsSimple(): array { return $this->errorsSimple; }

    public function chunkSize(): int { return 500; }
    public function batchSize(): int { return 500; }
}
