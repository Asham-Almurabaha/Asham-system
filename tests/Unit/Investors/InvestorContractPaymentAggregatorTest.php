<?php

namespace Tests\Unit\Investors;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Contracts\Entities\Contract;
use Modules\Customers\Entities\Customer;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Entities\InvestorTransaction;
use Modules\Investors\Support\InvestorContractPaymentAggregator;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\InstallmentType;
use Modules\Lookups\Entities\ProductType;
use Modules\Lookups\Entities\TransactionStatus;
use Tests\TestCase;

class InvestorContractPaymentAggregatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aggregates_installment_and_claim_payments_for_single_investor(): void
    {
        $investor = Investor::create(['name' => 'Investor-' . uniqid('', true)]);

        $contract = $this->createContract();

        $installmentStatus = TransactionStatus::where('name', 'سداد قسط')->firstOrFail();
        $claimStatus = TransactionStatus::where('name', 'سداد مطالبة')->firstOrFail();

        InvestorTransaction::create([
            'investor_id' => $investor->id,
            'contract_id' => $contract->id,
            'status_id' => $installmentStatus->id,
            'amount' => 150,
            'transaction_date' => '2024-01-01',
        ]);

        InvestorTransaction::create([
            'investor_id' => $investor->id,
            'contract_id' => $contract->id,
            'status_id' => $installmentStatus->id,
            'amount' => 50,
            'transaction_date' => '2024-01-05',
        ]);

        InvestorTransaction::create([
            'investor_id' => $investor->id,
            'contract_id' => $contract->id,
            'status_id' => $claimStatus->id,
            'amount' => 200,
            'transaction_date' => '2024-01-10',
        ]);

        InvestorContractPaymentAggregator::clearCachedBuckets();

        $result = InvestorContractPaymentAggregator::sumForInvestor($investor->id, [$contract->id]);

        $this->assertTrue($result->has($contract->id));
        $summary = $result->get($contract->id);

        $this->assertSame(200.0, $summary['installments']);
        $this->assertSame(200.0, $summary['claims']);
        $this->assertSame(400.0, $summary['total']);
    }

    public function test_it_aggregates_payments_for_multiple_investors(): void
    {
        $investorOne = Investor::create(['name' => 'Investor-A']);
        $investorTwo = Investor::create(['name' => 'Investor-B']);

        $contractOne = $this->createContract();
        $contractTwo = $this->createContract();

        $installmentStatus = TransactionStatus::where('name', 'سداد قسط')->firstOrFail();
        $claimStatus = TransactionStatus::where('name', 'سداد مطالبة')->firstOrFail();

        InvestorTransaction::create([
            'investor_id' => $investorOne->id,
            'contract_id' => $contractOne->id,
            'status_id' => $installmentStatus->id,
            'amount' => 500,
            'transaction_date' => '2024-02-01',
        ]);

        InvestorTransaction::create([
            'investor_id' => $investorTwo->id,
            'contract_id' => $contractTwo->id,
            'status_id' => $installmentStatus->id,
            'amount' => 300,
            'transaction_date' => '2024-02-03',
        ]);

        InvestorTransaction::create([
            'investor_id' => $investorTwo->id,
            'contract_id' => $contractTwo->id,
            'status_id' => $claimStatus->id,
            'amount' => 120,
            'transaction_date' => '2024-02-04',
        ]);

        InvestorContractPaymentAggregator::clearCachedBuckets();

        $result = InvestorContractPaymentAggregator::sumForInvestors(
            [$investorOne->id, $investorTwo->id],
            [$contractOne->id, $contractTwo->id]
        );

        $this->assertTrue($result->has($investorOne->id));
        $this->assertTrue($result->has($investorTwo->id));

        $firstSummary = $result->get($investorOne->id)->get($contractOne->id);
        $secondSummary = $result->get($investorTwo->id)->get($contractTwo->id);

        $this->assertSame(500.0, $firstSummary['total']);
        $this->assertSame(420.0, $secondSummary['total']);
        $this->assertSame(300.0, $secondSummary['installments']);
        $this->assertSame(120.0, $secondSummary['claims']);
    }

    private function createContract(): Contract
    {
        $customer = Customer::create([
            'name' => 'Customer-' . uniqid('', true),
        ]);

        $productType = ProductType::first() ?? ProductType::create(['name' => 'Test Product']);
        $installmentType = InstallmentType::first() ?? InstallmentType::create(['name' => 'شهري']);
        $status = ContractStatus::first() ?? ContractStatus::create(['name' => 'منتظم']);

        return Contract::create([
            'contract_number' => 'CN-' . uniqid('', true),
            'customer_id' => $customer->id,
            'contract_status_id' => $status->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'contract_value' => 1000,
            'investor_profit' => 500,
            'total_value' => 1500,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 500,
            'installments_count' => 3,
            'start_date' => now()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
        ]);
    }
}

