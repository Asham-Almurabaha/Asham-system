<?php

namespace Tests\Feature\Contracts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\Lookups\Entities\ClaimStatus;
use Modules\Lookups\Entities\ClaimPayer;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Lookups\Entities\CustomerStatus;
use Modules\Lookups\Entities\GuarantorStatus;
use Modules\Lookups\Entities\InstallmentType;
use Modules\Lookups\Entities\ProductType;
use Modules\Customers\Entities\Customer;
use Modules\Guarantors\Entities\Guarantor;
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

        $response = $this->patch(route('contract-claims.apply-discount', $claim), [
            'discount_amount' => 150.75,
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $claim->refresh();

        $this->assertSame(
            $paidWithDiscountStatus->id,
            $claim->claim_status_id,
            'Claim status should change to paid with discount.'
        );

        $this->assertEquals(150.75, (float) $claim->discount_amount);
    }

    public function test_recording_claim_payment_creates_entry_with_claim_payer(): void
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

        $response = $this->post(route('contract-claims.payments.store', $claim), [
            'claim_payer_id' => $claimPayer->id,
            'amount' => 250.50,
            'paid_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $this->assertDatabaseHas('contract_claim_payments', [
            'contract_claim_id' => $claim->id,
            'claim_payer_id' => $claimPayer->id,
            'amount' => '250.50',
        ]);

        $this->assertSame(
            $claimReviewStatus->id,
            $claim->fresh()->claim_status_id,
            'Recording a payment should not change the claim status automatically.'
        );
    }
}
