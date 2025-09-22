<?php

namespace Tests\Feature\Contracts;

use App\Models\LedgerEntry;
use App\Models\ProductTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Customers\Entities\Customer;
use Modules\Guarantors\Entities\Guarantor;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\Lookups\Entities\CustomerStatus;
use Modules\Lookups\Entities\GuarantorStatus;
use Modules\Lookups\Entities\InstallmentType;
use Modules\Lookups\Entities\ProductType;
use Modules\Lookups\Entities\TransactionStatus;
use Tests\TestCase;

class ContractAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_contract_fails_when_requested_quantity_exceeds_available_stock(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType     = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $purchaseStatus  = TransactionStatus::where('name', 'شراء بضائع')->firstOrFail();
        $saleStatus      = TransactionStatus::where('name', 'بيع بضائع')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Example',
            'national_id' => '11111111111111',
            'phone' => '0500000000',
            'email' => 'customer@example.test',
            'address' => 'Test Address',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Example',
            'national_id' => '22222222222222',
            'phone' => '0500000001',
            'email' => 'guarantor@example.test',
            'address' => 'Guarantor Address',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $entryDate = Carbon::now()->toDateString();

        $ledgerEntry = LedgerEntry::create([
            'entry_date' => $entryDate,
            'investor_id' => null,
            'is_office' => true,
            'transaction_status_id' => $purchaseStatus->id,
            'transaction_type_id' => $purchaseStatus->transaction_type_id,
            'bank_account_id' => null,
            'safe_id' => null,
            'contract_id' => null,
            'installment_id' => null,
            'amount' => 1000,
            'direction' => 'out',
            'ref' => 'STOCK-IN',
            'notes' => 'Initial stock purchase',
        ]);

        ProductTransaction::create([
            'product_type_id' => $productType->id,
            'ledger_entry_id' => $ledgerEntry->id,
            'quantity' => 5,
        ]);

        $saleLedgerEntry = LedgerEntry::create([
            'entry_date' => $entryDate,
            'investor_id' => null,
            'is_office' => true,
            'transaction_status_id' => $saleStatus->id,
            'transaction_type_id' => $saleStatus->transaction_type_id,
            'bank_account_id' => null,
            'safe_id' => null,
            'contract_id' => null,
            'installment_id' => null,
            'amount' => 600,
            'direction' => 'in',
            'ref' => 'STOCK-OUT',
            'notes' => 'Stock sale',
        ]);

        ProductTransaction::create([
            'product_type_id' => $productType->id,
            'ledger_entry_id' => $saleLedgerEntry->id,
            'quantity' => 2,
        ]);

        $response = $this->post(route('contracts.store'), [
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'product_type_id' => $productType->id,
            'products_count' => 4,
            'purchase_price' => 100,
            'sale_price' => 150,
            'contract_value' => 150,
            'investor_profit' => 50,
            'total_value' => 200,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 100,
            'installments_count' => 2,
            'start_date' => $entryDate,
            'first_installment_date' => Carbon::now()->addMonth()->toDateString(),
        ]);

        $response->assertSessionHasErrors('products_count');
        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_store_contract_succeeds_when_requested_quantity_is_available(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $customerStatus = CustomerStatus::where('name', 'جديد')->firstOrFail();
        $guarantorStatus = GuarantorStatus::where('name', 'جديد')->firstOrFail();
        $productType     = ProductType::query()->firstOrFail();
        $installmentType = InstallmentType::query()->firstOrFail();
        $purchaseStatus  = TransactionStatus::where('name', 'شراء بضائع')->firstOrFail();
        $saleStatus      = TransactionStatus::where('name', 'بيع بضائع')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Customer Example',
            'national_id' => '33333333333333',
            'phone' => '0500000002',
            'email' => 'customer2@example.test',
            'address' => 'Test Address 2',
            'customer_status_id' => $customerStatus->id,
        ]);

        $guarantor = Guarantor::create([
            'name' => 'Guarantor Example',
            'national_id' => '44444444444444',
            'phone' => '0500000003',
            'email' => 'guarantor2@example.test',
            'address' => 'Guarantor Address 2',
            'guarantor_status_id' => $guarantorStatus->id,
        ]);

        $entryDate = Carbon::now()->toDateString();

        $ledgerEntry = LedgerEntry::create([
            'entry_date' => $entryDate,
            'investor_id' => null,
            'is_office' => true,
            'transaction_status_id' => $purchaseStatus->id,
            'transaction_type_id' => $purchaseStatus->transaction_type_id,
            'bank_account_id' => null,
            'safe_id' => null,
            'contract_id' => null,
            'installment_id' => null,
            'amount' => 1000,
            'direction' => 'out',
            'ref' => 'STOCK-IN-2',
            'notes' => 'Initial stock purchase 2',
        ]);

        ProductTransaction::create([
            'product_type_id' => $productType->id,
            'ledger_entry_id' => $ledgerEntry->id,
            'quantity' => 5,
        ]);

        $saleLedgerEntry = LedgerEntry::create([
            'entry_date' => $entryDate,
            'investor_id' => null,
            'is_office' => true,
            'transaction_status_id' => $saleStatus->id,
            'transaction_type_id' => $saleStatus->transaction_type_id,
            'bank_account_id' => null,
            'safe_id' => null,
            'contract_id' => null,
            'installment_id' => null,
            'amount' => 600,
            'direction' => 'in',
            'ref' => 'STOCK-OUT-2',
            'notes' => 'Stock sale 2',
        ]);

        ProductTransaction::create([
            'product_type_id' => $productType->id,
            'ledger_entry_id' => $saleLedgerEntry->id,
            'quantity' => 2,
        ]);

        $response = $this->post(route('contracts.store'), [
            'customer_id' => $customer->id,
            'guarantor_id' => $guarantor->id,
            'product_type_id' => $productType->id,
            'products_count' => 3,
            'purchase_price' => 100,
            'sale_price' => 150,
            'contract_value' => 150,
            'investor_profit' => 50,
            'total_value' => 200,
            'discount_amount' => 0,
            'installment_type_id' => $installmentType->id,
            'installment_value' => 100,
            'installments_count' => 2,
            'start_date' => $entryDate,
            'first_installment_date' => Carbon::now()->addMonth()->toDateString(),
        ]);

        $response->assertRedirect(route('contracts.index'));

        $this->assertDatabaseHas('contracts', [
            'customer_id' => $customer->id,
            'products_count' => 3,
        ]);
    }
}
