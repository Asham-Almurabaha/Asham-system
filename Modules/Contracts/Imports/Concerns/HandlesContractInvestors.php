<?php

namespace Modules\Contracts\Imports\Concerns;

use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractStatus;
use Modules\Contracts\Services\InvestorTransactionLogger;
use Modules\Contracts\Support\ContractStatusNames;
use Modules\Contracts\Support\InvestorShareValidator;
use Modules\Investors\Entities\Investor;

trait HandlesContractInvestors
{
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
        $validator = new InvestorShareValidator();
        $sum = $validator->validate($investors);

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
                $id = ContractStatus::where('name', ContractStatusNames::NEW)->value('id');
            } else {
                $id = ContractStatus::where('name', ContractStatusNames::PENDING)->value('id');
            }
            if ($id) $contract->update(['contract_status_id'=>$id]);

            $entries = [];
            foreach ($pivot as $investorId => $row) {
                $entries[] = [
                    'investor_id' => (int) $investorId,
                    'amount'      => (float) ($row['share_value'] ?? 0),
                ];
            }

            if (!empty($entries)) {
                app(InvestorTransactionLogger::class)->log($contract, $entries, 'إضافة عقد', [
                    'allow_type_fallback' => true,
                    'fallback_direction'  => 'in',
                ]);
            }

        } else {
            $contract->investors()->detach();
            $id = ContractStatus::where('name', ContractStatusNames::NO_INVESTORS)->value('id');
            if ($id) $contract->update(['contract_status_id'=>$id]);
        }
    }

}
