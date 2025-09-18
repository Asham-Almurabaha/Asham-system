<?php

namespace Tests\Feature\Contracts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractClaim;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\Lookups\Entities\ClaimStatus;
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
            'claim_first_party_id' => null,
            'filed_party_role' => ContractClaim::FILED_PARTY_CUSTOMER,
            'claim_amount' => 500,
            'claim_date' => now()->subDays(10)->toDateString(),
            'document_number' => 'DOC-001',
            'claim_status_id' => $claimAcceptedStatus->id,
        ]);

        $response = $this->post(route('contract-claims.store'), [
            'contract_id' => $contract->id,
            'claim_first_party_id' => null,
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
}
