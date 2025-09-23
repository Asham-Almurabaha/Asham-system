<?php

namespace Tests\Feature\Accounts;

use App\Imports\LedgerEntriesImport;
use App\Models\LedgerEntry;
use App\Models\OfficeTransaction;
use App\Models\ProductTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounts\Entities\Safe;
use Modules\Lookups\Entities\ProductType;
use Modules\Lookups\Entities\TransactionStatus;
use Tests\TestCase;

class LedgerEntriesImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_import_creates_office_transaction_for_goods_entry(): void
    {
        $status = TransactionStatus::where('name', 'شراء بضائع')->firstOrFail();
        $safe   = Safe::firstOrFail();
        $productType = ProductType::firstOrFail();

        $import = new LedgerEntriesImport();

        $entry = $import->model([
            'party_category'  => 'office',
            'status_id'       => $status->id,
            'safe_id'         => $safe->id,
            'amount'          => 1500,
            'transaction_date'=> '2025-05-10',
            'notes'           => 'شراء بضائع مستوردة',
            'product_type_id' => $productType->id,
            'quantity'        => 4,
        ]);

        $this->assertInstanceOf(LedgerEntry::class, $entry);
        $this->assertTrue($entry->fresh()->is_office);

        $transaction = OfficeTransaction::first();
        $this->assertNotNull($transaction, 'Office transaction should be created for goods entry.');
        $this->assertSame($status->id, $transaction->status_id);
        $this->assertEquals(1500.00, (float) $transaction->amount);
        $this->assertEquals('2025-05-10', $transaction->transaction_date);
        $this->assertSame('OT-' . $transaction->id, $entry->fresh()->ref);

        $productTx = ProductTransaction::first();
        $this->assertNotNull($productTx, 'Product transaction should be stored for goods entry.');
        $this->assertSame($entry->id, $productTx->ledger_entry_id);
        $this->assertSame($productType->id, $productTx->product_type_id);
        $this->assertSame(4, $productTx->quantity);
    }
}
