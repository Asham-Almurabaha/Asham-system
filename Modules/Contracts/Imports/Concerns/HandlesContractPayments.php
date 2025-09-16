<?php

namespace Modules\Contracts\Imports\Concerns;

use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractInstallment;
use App\Models\LedgerEntry;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;
use Modules\Contracts\Services\InstallmentStatusService;

trait HandlesContractPayments
{
    /**
     * يقرا السدادات من:
     * - عمود payments: "date:amount|date:amount[#note]" أو "amount:date"
     * - أزواج حتى 18: payment{n}_amount + payment{n}_date (ويُقبل aliases: installment/qist/qst/qest)
     * - down_payment(+_date) أو first_payment_amount(+_date)
     * @return array<int,array{date:string,amount:float,notes?:string}>
     */
    protected function parsePaymentsFlexible(array $d): array
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
    protected function createPaymentLedgerEntries(Contract $contract, array $payments): void
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
    protected function allocatePaymentsToInstallments(Contract $contract, array $payments): void
    {
        /** @var \Illuminate\Support\Collection<int,ContractInstallment> $insts */
        $insts = ContractInstallment::where('contract_id', $contract->id)
            ->orderBy('due_date')->orderBy('id')->get();

        if ($insts->isEmpty()) return;

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
                $payDate = $p['date'] ?? null;
                if ($payDate) {
                    $update['payment_date'] = $payDate;
                }

                $inst->update($update);

                // استخدم نفس منطق تحديث حالة القسط المستخدم في السدادات اليدوية
                InstallmentStatusService::recalculate($inst);

                if ($left <= 0) break; // خلّصنا سداد واحد
            }
        }
    }
}

