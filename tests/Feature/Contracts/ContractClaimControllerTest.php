<?php

namespace Tests\Feature\Contracts;

use Modules\Ledger\Entities\LedgerEntry;
use App\Models\OfficeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Contracts\Entities\ContractNote;
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

    public function test_storing_claim_records_legal_fee_note(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();

        $customer = Customer::create([
            'name' => 'Legal Fee Customer',
            'national_id' => '11223344556677',
            'phone' => '0550000000',
            'email' => 'legal-customer@example.test',
            'address' => 'Customer Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Legal Fee Guarantor',
            'national_id' => '77665544332211',
            'phone' => '0550000001',
            'email' => 'legal-guarantor@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-9001',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 800,
            'sale_price' => 1000,
            'contract_value' => 1000,
            'investor_profit' => 200,
            'total_value' => 1000,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 100,
            'installments_count' => 10,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        $claimDate = now()->toDateString();

        $response = $this->post(route('contract-claims.store'), [
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 1200,
            'claim_date' => $claimDate,
            'document_number' => 'DOC-LEGAL-001',
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $note = ContractNote::query()
            ->where('contract_id', $contract->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($note);
        $this->assertSame($claimDate, $note->note_date->toDateString());
        $this->assertSame(
            'قيمة المحاماة = مبلغ المطالبة (1200.00) - المتبقي في العقد (1000.00) = 200.00',
            $note->note
        );
    }

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

    public function test_storing_claim_moves_regular_or_finished_contract_to_required(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $contractRegularStatus = ContractStatus::where('name', 'منتظم')->firstOrFail();
        $contractFinishedStatus = ContractStatus::where('name', 'منتهي')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Status Check',
            'national_id' => '22222222222222',
            'phone' => '0500000400',
            'email' => 'customer-status@example.test',
            'address' => 'Customer Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Status Check',
            'national_id' => '33333333333333',
            'phone' => '0500000401',
            'email' => 'guarantor-status@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $regularContract = Contract::create([
            'contract_number' => 'CNT-5001',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRegularStatus->id,
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

        $finishedContract = Contract::create([
            'contract_number' => 'CNT-5002',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractFinishedStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 1500,
            'sale_price' => 1700,
            'contract_value' => 1700,
            'investor_profit' => 200,
            'total_value' => 1700,
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

        $storeRegularResponse = $this->post(route('contract-claims.store'), [
            'contract_id' => $regularContract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 300,
            'claim_date' => now()->toDateString(),
            'document_number' => 'DOC-REG-001',
        ]);

        $storeRegularResponse->assertRedirect(route('contract-claims.index'));

        $this->assertSame(
            $contractRequiredStatus->id,
            $regularContract->fresh()->contract_status_id,
            'Regular contracts should become required after storing a claim.'
        );

        $storeFinishedResponse = $this->post(route('contract-claims.store'), [
            'contract_id' => $finishedContract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 450,
            'claim_date' => now()->toDateString(),
            'document_number' => 'DOC-FIN-001',
        ]);

        $storeFinishedResponse->assertRedirect(route('contract-claims.index'));

        $this->assertSame(
            $contractRequiredStatus->id,
            $finishedContract->fresh()->contract_status_id,
            'Finished contracts should become required after storing a claim.'
        );
    }

    public function test_rejected_claim_restores_contract_status_using_natural_logic(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractNewStatus = ContractStatus::where('name', 'جديد')->firstOrFail();
        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $claimRejectedStatus = ClaimStatus::where('name', 'مرفوض')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $installmentStatus = InstallmentStatus::where('name', 'لم يحل')->firstOrFail();
        $nationality = Nationality::query()->firstOrFail();
        $title = Title::query()->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Reject Example',
            'national_id' => '13579135791357',
            'phone' => '0500000300',
            'email' => 'customer-reject@example.test',
            'address' => 'Customer Reject Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Reject Example',
            'national_id' => '24680246802468',
            'phone' => '0500000301',
            'email' => 'guarantor-reject@example.test',
            'address' => 'Guarantor Reject Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $investor = Investor::create([
            'name' => 'Investor Reject Example',
            'national_id' => '55566677788899',
            'phone' => '0500000302',
            'email' => 'investor-reject@example.test',
            'address' => 'Investor Reject Address',
            'nationality_id' => $nationality->id,
            'title_id' => $title->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-2001',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractNewStatus->id,
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

        $contract->investors()->attach($investor->id, [
            'share_percentage' => 100,
            'share_value' => 1800,
            'office_share_percentage' => $investor->office_share_percentage,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ContractInstallment::create([
            'contract_id' => $contract->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(30)->toDateString(),
            'due_amount' => 1800,
            'payment_amount' => 0,
            'installment_status_id' => $installmentStatus->id,
        ]);

        $storeResponse = $this->post(route('contract-claims.store'), [
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 500,
            'claim_date' => now()->toDateString(),
            'document_number' => 'DOC-REJECT-001',
        ]);

        $storeResponse->assertRedirect(route('contract-claims.index'));

        $this->assertSame(
            $contractRequiredStatus->id,
            $contract->fresh()->contract_status_id,
            'A new claim should move the contract to the required status.'
        );

        $claim = ContractClaim::where('contract_id', $contract->id)->firstOrFail();

        $response = $this->patch(route('contract-claims.update-status', $claim), [
            'claim_status_id' => $claimRejectedStatus->id,
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $this->assertSame(
            $contractNewStatus->id,
            $contract->fresh()->contract_status_id,
            'Rejected claims should restore the contract status using the natural logic.'
        );
    }

    public function test_rejecting_raised_claim_restores_finished_status(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractFinishedStatus = ContractStatus::where('name', 'منتهي')->firstOrFail();
        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $contractRaisedStatus = ContractStatus::where('name', 'مرفوع فيه')->firstOrFail();
        $claimAcceptedStatus = ClaimStatus::where('name', 'مقبول')->firstOrFail();
        $claimRejectedStatus = ClaimStatus::where('name', 'مرفوض')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $installmentStatus = InstallmentStatus::where('name', 'مدفوع كامل')->firstOrFail();
        $nationality = Nationality::query()->firstOrFail();
        $title = Title::query()->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Raised Reject',
            'national_id' => '42424242424242',
            'phone' => '0500000410',
            'email' => 'customer-raised-reject@example.test',
            'address' => 'Customer Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Raised Reject',
            'national_id' => '64646464646464',
            'phone' => '0500000411',
            'email' => 'guarantor-raised-reject@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $investor = Investor::create([
            'name' => 'Investor Raised Reject',
            'national_id' => '42426688442266',
            'phone' => '0500000412',
            'email' => 'investor-raised-reject@example.test',
            'address' => 'Investor Address',
            'nationality_id' => $nationality->id,
            'title_id' => $title->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-5050',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractFinishedStatus->id,
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

        $contract->investors()->attach($investor->id, [
            'share_percentage' => 100,
            'share_value' => $contract->total_value,
            'office_share_percentage' => $investor->office_share_percentage,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ContractInstallment::create([
            'contract_id' => $contract->id,
            'installment_number' => 1,
            'due_date' => now()->subMonth()->toDateString(),
            'due_amount' => 1200,
            'payment_amount' => 1200,
            'installment_status_id' => $installmentStatus->id,
        ]);

        $storeResponse = $this->post(route('contract-claims.store'), [
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 600,
            'claim_date' => now()->toDateString(),
            'document_number' => 'DOC-RR-001',
        ]);

        $storeResponse->assertRedirect(route('contract-claims.index'));

        $claim = ContractClaim::where('contract_id', $contract->id)->firstOrFail();

        $this->assertSame(
            $contractRequiredStatus->id,
            $contract->fresh()->contract_status_id,
            'Storing the claim should move the contract to the required status.'
        );

        $acceptResponse = $this->patch(route('contract-claims.update-status', $claim), [
            'claim_status_id' => $claimAcceptedStatus->id,
        ]);

        $acceptResponse->assertRedirect(route('contract-claims.index'));

        $this->assertSame(
            $contractRaisedStatus->id,
            $contract->fresh()->contract_status_id,
            'Accepting the claim should raise the contract.'
        );

        $rejectResponse = $this->patch(route('contract-claims.update-status', $claim), [
            'claim_status_id' => $claimRejectedStatus->id,
        ]);

        $rejectResponse->assertRedirect(route('contract-claims.index'));

        $this->assertSame(
            $contractFinishedStatus->id,
            $contract->fresh()->contract_status_id,
            'Rejecting the raised claim should restore the finished status.'
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

    public function test_partial_payment_that_covers_outstanding_finishes_contract(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $contractFinishedWithClaimStatus = ContractStatus::where('name', 'منتهي بمطالبة')->firstOrFail();
        $partialStatus = ClaimStatus::where('name', 'مدفوع جزئي')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $claimPayer = ClaimPayer::where('name', 'المحكمة')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Partial Complete',
            'national_id' => '12121212121212',
            'phone' => '0500000500',
            'email' => 'customer-partial-complete@example.test',
            'address' => 'Customer Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Partial Complete',
            'national_id' => '34343434343434',
            'phone' => '0500000501',
            'email' => 'guarantor-partial-complete@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-5101',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRequiredStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 200,
            'sale_price' => 500,
            'contract_value' => 500,
            'investor_profit' => 50,
            'total_value' => 400,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 100,
            'installments_count' => 4,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        $storeResponse = $this->post(route('contract-claims.store'), [
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 500,
            'claim_date' => now()->toDateString(),
            'document_number' => 'DOC-PART-001',
        ]);

        $storeResponse->assertRedirect(route('contract-claims.index'));

        $claim = ContractClaim::where('contract_id', $contract->id)->firstOrFail();

        $paymentResponse = $this->post(route('contract-claims.payments.store', $claim), [
            'claim_payer_id' => $claimPayer->id,
            'amount' => 400,
            'paid_at' => now()->toDateString(),
        ]);

        $paymentResponse->assertRedirect(route('contract-claims.index'));

        $claim->refresh();

        $this->assertSame(
            $partialStatus->id,
            $claim->claim_status_id,
            'Partial payments should keep the claim on the partial-paid status.'
        );

        $this->assertEqualsWithDelta(100.0, $claim->remaining_amount, 0.01);

        $this->assertSame(
            $contractFinishedWithClaimStatus->id,
            $contract->fresh()->contract_status_id,
            'Covering the outstanding amount with a partial claim should finish the contract with claim.'
        );
    }

    public function test_partial_payment_that_leaves_outstanding_raises_contract(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $contractRequiredStatus = ContractStatus::where('name', 'مطلوب')->firstOrFail();
        $contractRaisedStatus = ContractStatus::where('name', 'مرفوع فيه')->firstOrFail();
        $partialStatus = ClaimStatus::where('name', 'مدفوع جزئي')->firstOrFail();
        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $claimPayer = ClaimPayer::where('name', 'المحكمة')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Partial Raised',
            'national_id' => '56565656565656',
            'phone' => '0500000502',
            'email' => 'customer-partial-raised@example.test',
            'address' => 'Customer Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Partial Raised',
            'national_id' => '78787878787878',
            'phone' => '0500000503',
            'email' => 'guarantor-partial-raised@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-5102',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRequiredStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 200,
            'sale_price' => 500,
            'contract_value' => 500,
            'investor_profit' => 50,
            'total_value' => 400,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 100,
            'installments_count' => 4,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        $storeResponse = $this->post(route('contract-claims.store'), [
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 500,
            'claim_date' => now()->toDateString(),
            'document_number' => 'DOC-PART-002',
        ]);

        $storeResponse->assertRedirect(route('contract-claims.index'));

        $claim = ContractClaim::where('contract_id', $contract->id)->firstOrFail();

        $paymentResponse = $this->post(route('contract-claims.payments.store', $claim), [
            'claim_payer_id' => $claimPayer->id,
            'amount' => 150,
            'paid_at' => now()->toDateString(),
        ]);

        $paymentResponse->assertRedirect(route('contract-claims.index'));

        $claim->refresh();

        $this->assertSame(
            $partialStatus->id,
            $claim->claim_status_id,
            'Partial payments should update the claim to the partial status.'
        );

        $this->assertGreaterThan(0.0, $claim->remaining_amount);

        $this->assertSame(
            $contractRaisedStatus->id,
            $contract->fresh()->contract_status_id,
            'Uncovered partial payments should keep the contract raised.'
        );
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
            'office_share_percentage' => $investor->office_share_percentage,
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

        $officeTransaction = OfficeTransaction::where('contract_id', $contract->id)
            ->where('status_id', $officeStatus->id)
            ->where('amount', '30.00')
            ->where('contract_claim_id', $claim->id)
            ->where('contract_claim_payment_id', $paymentRecord->id)
            ->first();

        $this->assertNotNull($officeTransaction, 'Office transaction for office profit should be recorded.');
        $this->assertSame($claim->id, $officeTransaction?->contract_claim_id);
        $this->assertSame($paymentRecord->id, $officeTransaction?->contract_claim_payment_id);

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

        $investorTransaction = InvestorTransaction::where('contract_id', $contract->id)
            ->where('investor_id', $investor->id)
            ->where('status_id', $investorStatus->id)
            ->where('amount', '220.50')
            ->first();

        $this->assertNotNull($investorTransaction, 'Investor transaction should be created for the payment.');
        $this->assertSame($claim->id, $investorTransaction->contract_claim_id);
        $this->assertSame($paymentRecord->id, $investorTransaction->contract_claim_payment_id);

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

    public function test_discount_application_logs_investor_transaction_with_claim_references(): void
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
        $investorStatus = TransactionStatus::whereIn('name', ['سداد مطالبة', 'سداد مطالبه'])->firstOrFail();
        $nationality = Nationality::query()->firstOrFail();
        $title = Title::query()->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Discount Investor',
            'national_id' => '22223333444455',
            'phone' => '0500000120',
            'email' => 'customer-discount-investor@example.test',
            'address' => 'Test Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Discount Investor',
            'national_id' => '66667777888899',
            'phone' => '0500000121',
            'email' => 'guarantor-discount-investor@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CNT-4301',
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'contract_status_id' => $contractRequiredStatus->id,
            'product_type_id' => $productType->id,
            'products_count' => 1,
            'purchase_price' => 1700,
            'sale_price' => 2000,
            'contract_value' => 2000,
            'investor_profit' => 300,
            'total_value' => 2000,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 180,
            'installments_count' => 12,
            'start_date' => now()->subMonth()->toDateString(),
            'first_installment_date' => now()->addMonth()->toDateString(),
            'contract_image' => null,
            'contract_customer_image' => null,
            'contract_guarantor_image' => null,
        ]);

        $investor = Investor::create([
            'name' => 'Investor Discount Claim',
            'national_id' => '12312312312312',
            'phone' => '0500000125',
            'email' => 'investor-discount-claim@example.test',
            'address' => 'Investor Address',
            'nationality_id' => $nationality->id,
            'title_id' => $title->id,
            'office_share_percentage' => 0,
        ]);

        $contract->investors()->attach($investor->id, [
            'share_percentage' => 100,
            'share_value' => $contract->total_value,
            'office_share_percentage' => $investor->office_share_percentage,
        ]);

        $claim = ContractClaim::create([
            'contract_id' => $contract->id,
            'claimant_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 600,
            'claim_date' => now()->subDays(3)->toDateString(),
            'document_number' => 'DOC-365',
            'claim_status_id' => $claimReviewStatus->id,
        ]);

        $response = $this->patch(route('contract-claims.apply-discount', $claim), [
            'discount_amount' => 150,
            'claim_payer_id' => $claimPayer->id,
            'paid_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('contract-claims.index'));

        $paymentRecord = ContractClaimPayment::where('contract_claim_id', $claim->id)->first();

        $this->assertNotNull($paymentRecord, 'Discount application should create a claim payment when a balance remains.');
        $this->assertSame(
            $paidWithDiscountStatus->id,
            $claim->fresh()->claim_status_id,
            'Claim status should switch to paid with discount after applying the discount.'
        );

        $investorTransaction = InvestorTransaction::where('contract_id', $contract->id)
            ->where('contract_claim_payment_id', $paymentRecord->id)
            ->first();

        $this->assertNotNull($investorTransaction, 'Investor transaction should be recorded for the discount payment.');
        $this->assertSame($investor->id, $investorTransaction->investor_id);
        $this->assertSame($investorStatus->id, $investorTransaction->status_id);
        $this->assertSame($claim->id, $investorTransaction->contract_claim_id);
        $this->assertSame($paymentRecord->id, $investorTransaction->contract_claim_payment_id);
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
            'office_share_percentage' => $investor->office_share_percentage,
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

        $paymentRecord = ContractClaimPayment::where('contract_claim_id', $claim->id)->first();
        $this->assertNotNull($paymentRecord, 'Payment record should exist after storing claim payment.');

        $officeTransaction = OfficeTransaction::where([
                'contract_id' => $contract->id,
                'status_id' => $legalStatus->id,
                'amount' => '50.00',
            ])
            ->where('contract_claim_id', $claim->id)
            ->where('contract_claim_payment_id', $paymentRecord->id)
            ->first();

        $this->assertNotNull($officeTransaction, 'Office legal transaction should include claim references.');
        $this->assertSame($claim->id, $officeTransaction?->contract_claim_id);
        $this->assertSame($paymentRecord->id, $officeTransaction?->contract_claim_payment_id);
    }
}
