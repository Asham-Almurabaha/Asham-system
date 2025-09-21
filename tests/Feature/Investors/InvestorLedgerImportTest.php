<?php

namespace Tests\Feature\Investors;

use App\Models\LedgerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Imports\InvestorLedgerEntriesImport;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\Lookups\Entities\TransactionStatus;
use Tests\TestCase;

class InvestorLedgerImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_investor_transaction_when_importing_ledger_entry(): void
    {
        $this->seed(LookupsDatabaseSeeder::class);

        $investor = Investor::create([
            'name' => 'مستثمر تجريبي',
        ]);

        $status = TransactionStatus::where('name', 'إضافة سيولة')->firstOrFail();

        $import = new InvestorLedgerEntriesImport();

        $result = $import->model([
            'investor_id'      => $investor->id,
            'status_id'        => $status->id,
            'amount'           => 1500,
            'transaction_date' => '2024-06-15',
            'notes'            => '  ملاحظة للتجربة  ',
        ]);

        $this->assertInstanceOf(LedgerEntry::class, $result);
        $this->assertTrue($result->exists);

        $this->assertDatabaseHas('ledger_entries', [
            'investor_id'           => $investor->id,
            'transaction_status_id' => $status->id,
            'amount'                => 1500,
            'direction'             => 'in',
            'notes'                 => 'ملاحظة للتجربة',
        ]);

        $this->assertDatabaseHas('investor_transactions', [
            'investor_id'      => $investor->id,
            'status_id'        => $status->id,
            'amount'           => 1500,
            'transaction_date' => '2024-06-15',
            'notes'            => 'ملاحظة للتجربة',
        ]);
    }
}
