<?php

namespace App\Imports;

use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\ContractStatus;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\InstallmentStatus;
use App\Models\InstallmentType;
use App\Models\Investor;
use App\Models\InvestorTransaction;
use App\Models\LedgerEntry;
use App\Models\ProductType;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContractsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    use SkipsErrors, SkipsFailures;

    private int $rows = 0;
    private int $inserted = 0;
    private int $updated = 0;
    private int $unchanged = 0;
    private int $skipped = 0;

    /** @var array<int,array{row:int,attribute?:string|array,values:array,messages:array}> */
    private array $failuresSimple = [];
    /** @var string[] */
    private array $errorsSimple = [];

    private const STATUS_NAME_NO_INVESTORS = 'بدون مستثمر';
    private const STATUS_NAME_PENDING      = 'معلق';
    private const STATUS_NAME_NEW          = 'جديد';
    private const EPS = 0.0001;

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

    /** عمود واحد بصيغة id:pct|id:pct */
    private function parseInvestorsPct(string $raw): array
    {
        $out = [];
        $raw = trim($raw);
        if ($raw === '') return $out;

        foreach (explode('|', $raw) as $chunk) {
            [$id,$pct] = array_pad(array_map('trim', explode(':', $chunk, 2)), 2, null);
            $id  = (int) $id;
            $pct = (float) $pct;
            if ($id>0 && $pct>=0) $out[] = ['id'=>$id, 'pct'=>$pct];
        }
        return $out;
    }

    /** حتى 6 مستثمرين من الأعمدة + دمج مع عمود investors */
    private function parseInvestorsFlexible(array $d): array
    {
        $byId = [];

        // 1) من الأعمدة investor{n}_id / investor{n}_name + investor{n}_pct
        for ($n=1; $n<=6; $n++) {
            $idKeyName = "investor{$n}_id";
            $nameKey   = "investor{$n}_name";
            $pctKey    = "investor{$n}_pct";

            // بدائل
            $altIdKeys  = [$idKeyName, "inv{$n}_id", "investor{$n}"];
            $altPctKeys = [$pctKey, "inv{$n}_pct", "investor{$n}_percentage", "investor{$n}_share"];

            $id = null;
            foreach ($altIdKeys as $k) {
                if (isset($d[$k]) && $d[$k] !== '') { $id = (int)$d[$k]; break; }
            }

            if (!$id && !empty($d[$nameKey])) {
                $id = (int) Investor::where('name', $d[$nameKey])->value('id');
                if (!$id) throw new \RuntimeException("المستثمر {$n} بالاسم '{$d[$nameKey]}' غير موجود.");
            }

            $pct = null;
            foreach ($altPctKeys as $k) {
                if (isset($d[$k]) && $d[$k] !== '') { $pct = (float)$d[$k]; break; }
            }

            if ($id && $pct !== null) {
                if (!Investor::whereKey($id)->exists()) {
                    throw new \RuntimeException("المستثمر #{$id} غير موجود (عمود المستثمر {$n}).");
                }
                $byId[$id] = ($byId[$id] ?? 0.0) + (float)$pct;
            }
        }

        // 2) من عمود واحد
        foreach ($this->parseInvestorsPct((string)($d['investors'] ?? '')) as $row) {
            $id  = (int)$row['id'];
            $pct = (float)$row['pct'];
            if ($id>0 && $pct>=0) {
                if (!Investor::whereKey($id)->exists()) {
                    throw new \RuntimeException("المستثمر #{$id} غير موجود (عمود investors).");
                }
                $byId[$id] = ($byId[$id] ?? 0.0) + $pct;
            }
        }

        $out = [];
        foreach ($byId as $id => $pct) {
            if ($pct < 0) continue;
            $out[] = ['id' => (int)$id, 'pct' => (float)$pct];
        }
        return $out;
    }

    private function attachInvestorsAndAutoStatus(Contract $contract, array $investors): void
    {
        $sum = 0.0;
        foreach ($investors as $inv) $sum += (float)$inv['pct'];
        if ($sum > 100.0001) throw new \RuntimeException('مجموع نسب المستثمرين تجاوز 100%.');

        if ($sum > self::EPS && !empty($investors)) {
            $pivot = [];
            foreach ($investors as $inv) {
                $id = (int)$inv['id'];
                $value = round(($contract->contract_value * (float)$inv['pct'])/100, 2);
                if ($value <= 0) throw new \RuntimeException('قيمة مشاركة المستثمر يجب أن تكون > 0.');
                $pivot[$id] = [
                    'share_percentage' => (float)$inv['pct'],
                    'share_value'      => (float)$value,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }
            $contract->investors()->sync($pivot);

            $sumRounded = round($sum,2);
            if (abs($sumRounded-100) <= self::EPS) {
                $id = ContractStatus::where('name', self::STATUS_NAME_NEW)->value('id');
            } else {
                $id = ContractStatus::where('name', self::STATUS_NAME_PENDING)->value('id');
            }
            if ($id) $contract->update(['contract_status_id'=>$id]);

            $this->logInvestorTransactions($contract, $pivot, 'إضافة عقد');

        } else {
            $contract->investors()->detach();
            $id = ContractStatus::where('name', self::STATUS_NAME_NO_INVESTORS)->value('id');
            if ($id) $contract->update(['contract_status_id'=>$id]);
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

    private function logInvestorTransactions(Contract $contract, array $pivot, string $statusName): void
    {
        $status = TransactionStatus::where('name',$statusName)->first(['id','transaction_type_id']);
        if (!$status) return;

        $typeId = $status->transaction_type_id ?: $this->guessTypeIdByStatusName($statusName);
        if (!$typeId) return;

        $direction = $this->directionFromTypeName(
            TransactionType::whereKey($typeId)->value('name')
        ) ?? 'in';

        foreach ($pivot as $investorId => $row) {
            $amount = (float)($row['share_value'] ?? 0);
            if ($amount <= 0) continue;

            $trx = InvestorTransaction::create([
                'investor_id'      => (int)$investorId,
                'contract_id'      => $contract->id,
                'status_id'        => $status->id,
                'amount'           => $amount,
                'transaction_date' => now(),
                'notes'            => "عملية {$statusName} للعقد رقم {$contract->contract_number}",
            ]);

            LedgerEntry::create([
                'entry_date'             => now()->toDateString(),
                'investor_id'            => (int)$investorId,
                'is_office'              => false,
                'transaction_status_id'  => $status->id,
                'transaction_type_id'    => $typeId,
                'bank_account_id'        => null,
                'safe_id'                => null,
                'contract_id'            => $contract->id,
                'installment_id'         => null,
                'amount'                 => $amount,
                'direction'              => $direction,
                'ref'                    => 'IT-'.$trx->id,
                'notes'                  => "قيد {$statusName} للعقد #{$contract->contract_number} (مستثمر #{$investorId})",
            ]);
        }
    }

    private function guessTypeIdByStatusName(string $statusName): ?int
    {
        $typeId = TransactionType::where('name',$statusName)->value('id');
        if ($typeId) return (int)$typeId;

        $alts = [
            'إضافة عقد'   => ['استثمار عقد', 'حركة مستثمر', 'عقد جديد'],
            'توزيع أرباح' => ['أرباح', 'حركة مستثمر'],
            'سداد أصل'    => ['سداد أصل', 'تحصيل'],
            'سداد قسط'    => ['تحصيل قسط', 'تحصيل'],
        ];
        foreach ($alts[$statusName] ?? [] as $alt) {
            $typeId = TransactionType::where('name',$alt)->value('id');
            if ($typeId) return (int)$typeId;
        }
        return null;
    }

    private function directionFromTypeName(?string $name): ?string
    {
        if (!$name) return null;
        $name = $this->arNormalize($name);
        if (str_contains($name,'ايداع') || str_contains($name,'توريد') || str_contains($name,'تحصيل') || str_contains($name,'deposit')) return 'in';
        if (str_contains($name,'سحب')   || str_contains($name,'صرف')   || str_contains($name,'توزيع') || str_contains($name,'استرداد') || str_contains($name,'withdraw')) return 'out';
        return null;
    }

    private function arNormalize(string $text): string
    {
        $text = mb_strtolower(trim($text),'UTF-8');
        $map = ['أ'=>'ا','إ'=>'ا','آ'=>'ا','ة'=>'ه','ى'=>'ي','ؤ'=>'و','ئ'=>'ي'];
        return strtr($text,$map);
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
