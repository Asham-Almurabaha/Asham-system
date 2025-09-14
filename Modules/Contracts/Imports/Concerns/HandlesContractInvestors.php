<?php

namespace Modules\Contracts\Imports\Concerns;

use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractStatus;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Entities\InvestorTransaction;
use App\Models\LedgerEntry;
use App\Models\TransactionStatus;
use App\Models\TransactionType;

trait HandlesContractInvestors
{
    private const STATUS_NAME_NO_INVESTORS = 'بدون مستثمر';
    private const STATUS_NAME_PENDING      = 'معلق';
    private const STATUS_NAME_NEW          = 'جديد';
    private const EPS = 0.0001;

    /** عمود واحد بصيغة id:pct|id:pct */
    private function parseInvestorsPct(string $raw): array
    {
        $out = [];
        $raw = trim($raw);
        if ($raw === '') return $out;

        foreach (explode('|', $raw) as $chunk) {
            [$id, $pct] = array_pad(array_map('trim', explode(':', $chunk, 2)), 2, null);
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

        for ($n=1; $n<=6; $n++) {
            $idKeyName = "investor{$n}_id";
            $nameKey   = "investor{$n}_name";
            $pctKey    = "investor{$n}_pct";

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
}
