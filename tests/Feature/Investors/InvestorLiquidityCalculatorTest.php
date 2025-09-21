<?php

namespace Tests\Feature\Investors;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Entities\InvestorTransaction;
use Modules\Investors\Support\InvestorLiquidityCalculator;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;
use Tests\TestCase;

class InvestorLiquidityCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_summarizes_liquidity_from_investor_transactions(): void
    {
        $investor = Investor::create([
            'name' => 'Test Investor ' . uniqid('', true),
        ]);

        $depositType = TransactionType::create([
            'name' => 'تحصيل مستثمر',
        ]);

        $withdrawType = TransactionType::create([
            'name' => 'توزيع أرباح',
        ]);

        $depositStatus = TransactionStatus::create([
            'name' => 'تحصيل مستثمر',
            'transaction_type_id' => $depositType->id,
        ]);

        $withdrawStatus = TransactionStatus::create([
            'name' => 'توزيع أرباح',
            'transaction_type_id' => $withdrawType->id,
        ]);

        InvestorTransaction::create([
            'investor_id' => $investor->id,
            'status_id' => $depositStatus->id,
            'amount' => 150,
            'transaction_date' => '2024-01-15',
        ]);

        InvestorTransaction::create([
            'investor_id' => $investor->id,
            'status_id' => $depositStatus->id,
            'amount' => 25,
            'transaction_date' => '2024-02-10',
        ]);

        InvestorTransaction::create([
            'investor_id' => $investor->id,
            'status_id' => $withdrawStatus->id,
            'amount' => 40,
            'transaction_date' => '2024-03-05',
        ]);

        InvestorLiquidityCalculator::clearCachedBuckets();

        $summary = InvestorLiquidityCalculator::summarizeForInvestor($investor->id);

        $this->assertSame(175.0, $summary['in']);
        $this->assertSame(40.0, $summary['out']);
        $this->assertSame(135.0, $summary['net']);

        $aggregate = InvestorLiquidityCalculator::aggregateTotals(null, [$investor->id]);

        $this->assertCount(1, $aggregate);
        $this->assertSame($summary, $aggregate[$investor->id]);
    }
}
