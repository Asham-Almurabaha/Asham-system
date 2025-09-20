<?php

namespace Tests\Feature\Contracts;

use App\Models\LedgerEntry;
use App\Models\OfficeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Contracts\Entities\ContractClaimPayment;
use Modules\Contracts\Entities\ContractInstallment;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\Lookups\Entities\ClaimStatus;
use Modules\Lookups\Entities\ClaimPayer;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\CustomerStatus;
use Modules\Lookups\Entities\GuarantorStatus;
use Modules\Lookups\Entities\InstallmentType;
use Modules\Lookups\Entities\InstallmentStatus;
use Modules\Lookups\Entities\Nationality;
use Modules\Lookups\Entities\ProductType;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\Title;
use Modules\Customers\Entities\Customer;
use Modules\Guarantors\Entities\Guarantor;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Entities\InvestorTransaction;
use Tests\TestCase;

class ContractClaimControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_claim_does_not_reset_contract_status_when_previous_claim_exists(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRaisedStatus = ContractStatus::where('name', 'مرفوع فيه')->firstOrFail();
        $claimAcceptedStatus = ClaimStatus::where('name', 'مقبول')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Example',
            'national_id' => '12345678901234',
            'phone' => '0500000000',
            'email' => 'customer@example.test',
            'address' => 'Test Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Example',
            'national_id' => '98765432109876',
            'phone' => '0500000001',
            'email' => 'guarantor@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-1001',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRaisedStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 1000,
            'sale_price' => 1200,
            'contract_value' => 1200,
            'investor_profit' => 200,
            'total_value' => 1200,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 100,
            'installments_count' => 12,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        ContractClaim::create([
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 500,
            'claim_date' => now()->subDays(10)->toDateString(),
            'document_number' => 'DOC-001',
            'claim_status_id' => $claimAcceptedStatus->id,
        ]);

        $response = $this->post(route('contract-claims.store'), [
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 250,
            'claim_date' => now()->toDateString(),
            'document_number' => 'DOC-002',
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $this->assertDatabaseHas('contract_claims', [
            'contract_id' => $contract->id,
            'document_number' => 'DOC-002',
        ]);

        $this->assertSame(
            $contractRaisedStatus->id,
            $contract->fresh()->contract_status_id,
            'Contract status should remain unchanged when previous claims exist.'
        );
    }

    public function test_updating_claim_to_accepted_updates_contract_status_to_raised(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $contractRaisedStatus = ContractStatus::where('name', 'مرفوع فيه')->firstOrFail();
        $claimReviewStatus = ClaimStatus::where('name', 'قيد المراجعة')->firstOrFail();
        $claimAcceptedStatus = ClaimStatus::where('name', 'مقبول')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Example',
            'national_id' => '12345678901234',
            'phone' => '0500000000',
            'email' => 'customer@example.test',
            'address' => 'Test Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Example',
            'national_id' => '98765432109876',
            'phone' => '0500000001',
            'email' => 'guarantor@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-2001',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRequiredStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 2000,
            'sale_price' => 2200,
            'contract_value' => 2200,
            'investor_profit' => 200,
            'total_value' => 2200,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 200,
            'installments_count' => 12,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        $claim = ContractClaim::create([
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 750,
            'claim_date' => now()->subDays(5)->toDateString(),
            'document_number' => 'DOC-100',
            'claim_status_id' => $claimReviewStatus->id,
        ]);

        $response = $this->patch(route('contract-claims.update-status', $claim), [
            'claim_status_id' => $claimAcceptedStatus->id,
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $this->assertSame(
            $contractRaisedStatus->id,
            $contract->fresh()->contract_status_id,
            'Contract status should update to raised when claim is accepted.'
        );

        $this->assertSame(
            $claimAcceptedStatus->id,
            $claim->fresh()->claim_status_id,
            'Claim status should be updated to accepted.'
        );
    }

    public function test_applying_discount_updates_claim_status_and_amount(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $claimReviewStatus = ClaimStatus::where('name', 'قيد المراجعة')->firstOrFail();
        $paidWithDiscountStatus = ClaimStatus::where('name', 'مدفوع بخصم')->firstOrFail();
        $finishedWithClaimStatus = ContractStatus::where('name', 'منتهي بمطالبة')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Example',
            'national_id' => '12345678901234',
            'phone' => '0500000000',
            'email' => 'customer-discount@example.test',
            'address' => 'Test Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Example',
            'national_id' => '98765432109876',
            'phone' => '0500000001',
            'email' => 'guarantor-discount@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-3001',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRequiredStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 1500,
            'sale_price' => 1750,
            'contract_value' => 1750,
            'investor_profit' => 250,
            'total_value' => 1750,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 150,
            'installments_count' => 12,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        $claim = ContractClaim::create([
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 600,
            'claim_date' => now()->subDays(7)->toDateString(),
            'document_number' => 'DOC-200',
            'claim_status_id' => $claimReviewStatus->id,
        ]);

        $claimPayer = ClaimPayer::where('name', 'المحكمة')->firstOrFail();

        $response = $this->patch(route('contract-claims.apply-discount', $claim), [
            'discount_amount' => 150.75,
            'claim_payer_id' => $claimPayer->id,
            'paid_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $claim->refresh();

        $this->assertSame(
            $paidWithDiscountStatus->id,
            $claim->claim_status_id,
            'Claim status should change to paid with discount.'
        );

        $this->assertEquals(150.75, (float) $claim->discount_amount);

        $this->assertDatabaseHas('contract_claim_payments', [
            'contract_claim_id' => $claim->id,
            'claim_payer_id' => $claimPayer->id,
            'amount' => '449.25',
        ]);
    }

    public function test_recording_claim_payment_creates_entry_with_claim_payer(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $claimReviewStatus = ClaimStatus::where('name', 'قيد المراجعة')->firstOrFail();
        $partialStatus = ClaimStatus::where('name', 'مدفوع جزئي')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $claimPayer = ClaimPayer::where('name', 'المحكمة')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Payment Example',
            'national_id' => '11122233344455',
            'phone' => '0500000010',
            'email' => 'customer-payment@example.test',
            'address' => 'Test Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Payment Example',
            'national_id' => '99988877766655',
            'phone' => '0500000011',
            'email' => 'guarantor-payment@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-4001',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRequiredStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 1800,
            'sale_price' => 2100,
            'contract_value' => 2100,
            'investor_profit' => 300,
            'total_value' => 2100,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 175,
            'installments_count' => 12,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        $claim = ContractClaim::create([
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 500,
            'claim_date' => now()->subDays(3)->toDateString(),
            'document_number' => 'DOC-300',
            'claim_status_id' => $claimReviewStatus->id,
        ]);

        $bankAccount = BankAccount::create([
            'name' => 'Main Account',
            'bank_name' => 'Test Bank',
            'account_number' => '1234567890',
            'iban' => 'SA0012345678901234567899',
            'opening_balance' => 0,
            'currency_code' => 'SAR',
            'is_active' => true,
        ]);

        $nationality = Nationality::query()->firstOrFail();
        $title = Title::query()->firstOrFail();

        $investor = Investor::create([
            'name' => 'Investor Example',
            'national_id' => '77788899900011',
            'phone' => '0500000900',
            'email' => 'investor@example.test',
            'address' => 'Investor Address',
            'nationality_id' => $nationality->id,
            'title_id' => $title->id,
            'office_share_percentage' => 10,
        ]);

        $contract->investors()->attach($investor->id, [
            'share_percentage' => 100,
            'share_value' => $contract->total_value,
        ]);

        $paymentDate = now()->toDateString();
        $notes = 'ملاحظة اختبار السداد';

        $response = $this->post(route('contract-claims.payments.store', $claim), [
            'claim_payer_id' => $claimPayer->id,
            'amount' => 250.50,
            'paid_at' => $paymentDate,
            'bank_account_id' => $bankAccount->id,
            'notes' => $notes,
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $this->assertDatabaseHas('contract_claim_payments', [
            'contract_claim_id' => $claim->id,
            'claim_payer_id' => $claimPayer->id,
            'amount' => '250.50',
        ]);

        $officeStatus = TransactionStatus::whereIn('name', ['محاماة مطالبة', 'محاماه مطالبه'])->firstOrFail();
        $investorStatus = TransactionStatus::whereIn('name', ['سداد مطالبة', 'سداد مطالبه'])->firstOrFail();

        $paymentRecord = ContractClaimPayment::where('contract_claim_id', $claim->id)->first();

        $this->assertNotNull($paymentRecord, 'Payment record should exist after storing claim payment.');

        $this->assertTrue(
            OfficeTransaction::where('contract_id', $contract->id)
                ->where('status_id', $officeStatus->id)
                ->where('amount', '30.00')
                ->exists(),
            'Office transaction for office profit should be recorded.'
        );

        $officeLedger = LedgerEntry::where([
            'contract_id' => $contract->id,
            'transaction_status_id' => $officeStatus->id,
            'amount' => '30.00',
            'bank_account_id' => $bankAccount->id,
            'is_office' => 1,
        ])->first();

        $this->assertNotNull($officeLedger, 'Office ledger entry should exist.');
        $this->assertStringContainsString('DOC-300', $officeLedger->notes ?? '');
        $this->assertStringContainsString('ملاحظات', $officeLedger->notes ?? '');
        $this->assertStringContainsString($notes, $officeLedger->notes ?? '');

        $investorLedger = LedgerEntry::where([
            'contract_id' => $contract->id,
            'transaction_status_id' => $investorStatus->id,
            'amount' => '220.50',
            'investor_id' => $investor->id,
            'bank_account_id' => $bankAccount->id,
        ])->first();

        $this->assertNotNull($investorLedger, 'Investor ledger entry should exist.');
        $this->assertStringContainsString('ملاحظات', $investorLedger->notes ?? '');
        $this->assertStringContainsString($notes, $investorLedger->notes ?? '');

        $this->assertTrue(
            InvestorTransaction::where('contract_id', $contract->id)
                ->where('investor_id', $investor->id)
                ->where('status_id', $investorStatus->id)
                ->where('amount', '220.50')
                ->exists(),
            'Investor transaction should be created for the payment.'
        );

        $this->assertSame(
            $partialStatus->id,
            $claim->fresh()->claim_status_id,
            'Recording a partial payment should update the claim status to partially paid.'
        );
    }

    public function test_full_claim_payment_updates_status_to_paid_in_full(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $claimReviewStatus = ClaimStatus::where('name', 'قيد المراجعة')->firstOrFail();
        $paidInFullStatus = ClaimStatus::where('name', 'مدفوع كامل')->firstOrFail();
        $finishedWithClaimStatus = ContractStatus::where('name', 'منتهي بمطالبة')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $claimPayer = ClaimPayer::where('name', 'المحكمة')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Full Payment',
            'national_id' => '12312312312312',
            'phone' => '0500000100',
            'email' => 'customer-full@example.test',
            'address' => 'Test Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Full Payment',
            'national_id' => '32132132132132',
            'phone' => '0500000101',
            'email' => 'guarantor-full@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-4101',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRequiredStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 1500,
            'sale_price' => 1700,
            'contract_value' => 1700,
            'investor_profit' => 200,
            'total_value' => 1700,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 200,
            'installments_count' => 12,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        $claim = ContractClaim::create([
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 450,
            'claim_date' => now()->subDays(2)->toDateString(),
            'document_number' => 'DOC-350',
            'claim_status_id' => $claimReviewStatus->id,
        ]);

        $response = $this->post(route('contract-claims.payments.store', $claim), [
            'claim_payer_id' => $claimPayer->id,
            'amount' => 450,
            'paid_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $updatedClaim = $claim->fresh();

        $this->assertSame(
            $paidInFullStatus->id,
            $updatedClaim->claim_status_id,
            'Full settlement should mark the claim as paid in full.'
        );

        $this->assertEqualsWithDelta(0.0, $updatedClaim->remaining_amount, 0.01);

        $this->assertSame(
            $finishedWithClaimStatus->id,
            $contract->fresh()->contract_status_id,
            'Full settlement should move the contract to the finished-with-claim status.'
        );
    }

    public function test_discounted_claim_payment_keeps_discount_status_after_payment(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $claimReviewStatus = ClaimStatus::where('name', 'قيد المراجعة')->firstOrFail();
        $paidWithDiscountStatus = ClaimStatus::where('name', 'مدفوع بخصم')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $claimPayer = ClaimPayer::where('name', 'المحكمة')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Discount Payment',
            'national_id' => '45645645645645',
            'phone' => '0500000110',
            'email' => 'customer-discount-pay@example.test',
            'address' => 'Test Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Discount Payment',
            'national_id' => '65465465465465',
            'phone' => '0500000111',
            'email' => 'guarantor-discount-pay@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-4201',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRequiredStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 1600,
            'sale_price' => 1850,
            'contract_value' => 1850,
            'investor_profit' => 250,
            'total_value' => 1850,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 220,
            'installments_count' => 12,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        $claim = ContractClaim::create([
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 600,
            'claim_date' => now()->subDays(3)->toDateString(),
            'document_number' => 'DOC-360',
            'claim_status_id' => $claimReviewStatus->id,
        ]);

        $discountResponse = $this->patch(route('contract-claims.apply-discount', $claim), [
            'discount_amount' => 150,
            'claim_payer_id' => $claimPayer->id,
            'paid_at' => now()->toDateString(),
        ]);

        $discountResponse->assertRedirect(route('contract-claims.index'));

        $this->assertSame(
            $paidWithDiscountStatus->id,
            $claim->fresh()->claim_status_id,
            'Applying a discount should set the claim status to paid with discount.'
        );

        $this->assertSame(
            $finishedWithClaimStatus->id,
            $contract->fresh()->contract_status_id,
            'Settling a claim with a discount should move the contract to the finished-with-claim status.'
        );

        $paymentResponse = $this->post(route('contract-claims.payments.store', $claim), [
            'claim_payer_id' => $claimPayer->id,
            'amount' => 100,
            'paid_at' => now()->toDateString(),
        ]);

        $paymentResponse->assertRedirect(route('contract-claims.index'));

        $this->assertSame(
            $paidWithDiscountStatus->id,
            $claim->fresh()->claim_status_id,
            'Discounted claims should retain the paid-with-discount status after additional payments.'
        );

        $this->assertSame(
            $finishedWithClaimStatus->id,
            $contract->fresh()->contract_status_id,
            'Additional payments should keep the contract in the finished-with-claim status.'
        );
    }

    public function test_cannot_record_payment_exceeding_remaining_amount(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $claimReviewStatus = ClaimStatus::where('name', 'قيد المراجعة')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $claimPayer = ClaimPayer::where('name', 'المحكمة')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Remaining Example',
            'national_id' => '32165498701234',
            'phone' => '0500000020',
            'email' => 'customer-remaining@example.test',
            'address' => 'Test Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Remaining Example',
            'national_id' => '78945612309876',
            'phone' => '0500000021',
            'email' => 'guarantor-remaining@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-5001',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRequiredStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 1500,
            'sale_price' => 1800,
            'contract_value' => 1800,
            'investor_profit' => 300,
            'total_value' => 1800,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 150,
            'installments_count' => 12,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        $claim = ContractClaim::create([
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 500,
            'discount_amount' => 100,
            'claim_date' => now()->subDays(4)->toDateString(),
            'document_number' => 'DOC-400',
            'claim_status_id' => $claimReviewStatus->id,
        ]);

        $claim->payments()->create([
            'claim_payer_id' => $claimPayer->id,
            'amount' => 350,
            'paid_at' => now()->subDay()->toDateString(),
        ]);

        $response = $this->from(route('contract-claims.index'))->post(route('contract-claims.payments.store', $claim), [
            'claim_payer_id' => $claimPayer->id,
            'amount' => 100,
            'paid_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('contract-claims.index'));
        $response->assertSessionHasErrors(['amount']);

        $this->assertSame(1, ContractClaimPayment::where('contract_claim_id', $claim->id)->count());
        $this->assertDatabaseMissing('contract_claim_payments', [
            'contract_claim_id' => $claim->id,
            'amount' => '100.00',
        ]);
        $this->assertDatabaseCount('ledger_entries', 0);
    }

    public function test_claim_payment_records_excess_as_legal_office_income(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $claimReviewStatus = ClaimStatus::where('name', 'قيد المراجعة')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $installmentStatus = InstallmentStatus::where('name', 'مدفوع كامل')->firstOrFail();
        $claimPayer = ClaimPayer::where('name', 'المحكمة')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Excess Example',
            'national_id' => '45678912345678',
            'phone' => '0500000030',
            'email' => 'customer-excess@example.test',
            'address' => 'Test Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Excess Example',
            'national_id' => '98712365409876',
            'phone' => '0500000031',
            'email' => 'guarantor-excess@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-6001',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRequiredStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 2000,
            'sale_price' => 2300,
            'contract_value' => 2300,
            'investor_profit' => 300,
            'total_value' => 2300,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 200,
            'installments_count' => 12,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
        ]);

        ContractInstallment::create([
            'contract_id' => $contract->id,
            'installment_number' => 1,
            'due_date' => now()->subMonths(2)->toDateString(),
            'due_amount' => 2300,
            'payment_date' => now()->subMonth()->toDateString(),
            'payment_amount' => 2200,
            'installment_status_id' => $installmentStatus->id,
        ]);

        $claim = ContractClaim::create([
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 700,
            'claim_date' => now()->subDays(2)->toDateString(),
            'document_number' => 'DOC-360',
            'claim_status_id' => $claimReviewStatus->id,
        ]);

        $bankAccount = BankAccount::create([
            'name' => 'Extra Account',
            'bank_name' => 'Extra Bank',
            'account_number' => '777222333',
            'iban' => 'SA7711223344556677889900',
            'opening_balance' => 0,
            'currency_code' => 'SAR',
            'is_active' => true,
        ]);

        $nationality = Nationality::query()->firstOrFail();
        $title = Title::query()->firstOrFail();

        $investor = Investor::create([
            'name' => 'Investor Excess',
            'national_id' => '66778899001122',
            'phone' => '0500000040',
            'email' => 'investor-excess@example.test',
            'address' => 'Investor Address',
            'nationality_id' => $nationality->id,
            'title_id' => $title->id,
            'office_share_percentage' => 0,
        ]);

        $contract->investors()->attach($investor->id, [
            'share_percentage' => 100,
            'share_value' => $contract->total_value,
        ]);

        $response = $this->post(route('contract-claims.payments.store', $claim), [
            'claim_payer_id' => $claimPayer->id,
            'amount' => 150.00,
            'paid_at' => now()->toDateString(),
            'bank_account_id' => $bankAccount->id,
            'notes' => 'سداد مع فائض',
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $this->assertDatabaseHas('contract_claim_payments', [
            'contract_claim_id' => $claim->id,
            'amount' => '150.00',
        ]);

        $investorStatus = TransactionStatus::whereIn('name', ['سداد مطالبة', 'سداد مطالبه'])->firstOrFail();
        $legalStatus = TransactionStatus::whereIn('name', ['محاماة مطالبة', 'محاماه مطالبه'])->firstOrFail();

        $this->assertTrue(
            LedgerEntry::where([
                'contract_id' => $contract->id,
                'transaction_status_id' => $investorStatus->id,
                'investor_id' => $investor->id,
                'amount' => '100.00',
                'bank_account_id' => $bankAccount->id,
            ])->exists()
        );

        $this->assertTrue(
            LedgerEntry::where([
                'contract_id' => $contract->id,
                'transaction_status_id' => $legalStatus->id,
                'amount' => '50.00',
                'is_office' => 1,
                'bank_account_id' => $bankAccount->id,
            ])->exists()
        );

        $this->assertTrue(
            OfficeTransaction::where([
                'contract_id' => $contract->id,
                'status_id' => $legalStatus->id,
                'amount' => '50.00',
            ])->exists()
        );
    }
}
