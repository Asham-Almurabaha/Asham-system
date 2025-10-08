<?php

namespace Tests\Feature\Accounts;

use Modules\Ledger\Entities\LedgerEntry;
use App\Models\OfficeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounts\Entities\BankAccount;
use Modules\Accounts\Entities\Safe;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Entities\InvestorTransaction;
use Modules\Lookups\Entities\TransactionStatus;
use Tests\TestCase;

class LedgerEntryTransactionsTest extends TestCase
{
    use RefreshDatabase;

    private TransactionStatus $investorDepositStatus;
    private TransactionStatus $officeDepositStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->investorDepositStatus = TransactionStatus::where('name', 'إضافة سيولة')->firstOrFail();
        $this->officeDepositStatus   = TransactionStatus::where('name', 'إيداع حسابات')->firstOrFail();
    }

    public function test_store_creates_investor_transaction_and_ref(): void
    {
        $user = User::factory()->create();
        $investor = Investor::create(['name' => 'مستثمر تجريبي']);
        $bank = BankAccount::create(['name' => 'حساب بنكي']);

        $response = $this->actingAs($user)->post(route('ledger.store'), [
            'party_category'   => 'investors',
            'investor_id'      => $investor->id,
            'status_id'        => $this->investorDepositStatus->id,
            'bank_account_id'  => $bank->id,
            'amount'           => '1500',
            'transaction_date' => '2025-01-15',
            'notes'            => 'قيد مستثمر',
        ]);

        $response->assertRedirect(route('ledger.index'));

        $transaction = InvestorTransaction::first();
        $this->assertNotNull($transaction);
        $this->assertSame($investor->id, $transaction->investor_id);
        $this->assertEquals($this->investorDepositStatus->id, $transaction->status_id);
        $this->assertEquals(1500.00, (float) $transaction->amount);
        $this->assertEquals('2025-01-15', $transaction->transaction_date->toDateString());
        $this->assertSame('قيد مستثمر', $transaction->notes);

        $entry = LedgerEntry::first();
        $this->assertNotNull($entry);
        $this->assertSame('IT-' . $transaction->id, $entry->ref);
        $this->assertFalse($entry->is_office);
    }

    public function test_store_creates_office_transaction_and_ref(): void
    {
        $user = User::factory()->create();
        $safe = Safe::create(['name' => 'الخزنة الرئيسية']);

        $response = $this->actingAs($user)->post(route('ledger.store'), [
            'party_category'   => 'office',
            'status_id'        => $this->officeDepositStatus->id,
            'safe_id'          => $safe->id,
            'amount'           => '750',
            'transaction_date' => '2025-02-10',
            'notes'            => 'قيد مكتب',
        ]);

        $response->assertRedirect(route('ledger.index'));

        $transaction = OfficeTransaction::first();
        $this->assertNotNull($transaction);
        $this->assertNull($transaction->investor_id);
        $this->assertEquals($this->officeDepositStatus->id, $transaction->status_id);
        $this->assertEquals(750.00, (float) $transaction->amount);
        $this->assertEquals('2025-02-10', $transaction->transaction_date);
        $this->assertSame('قيد مكتب', $transaction->notes);

        $entry = LedgerEntry::first();
        $this->assertNotNull($entry);
        $this->assertSame('OT-' . $transaction->id, $entry->ref);
        $this->assertTrue($entry->is_office);
    }

    public function test_split_store_creates_investor_transaction_and_updates_refs(): void
    {
        $user = User::factory()->create();
        $investor = Investor::create(['name' => 'مستثمر مجزأ']);
        $bank = BankAccount::create(['name' => 'حساب مجزأ']);
        $safe = Safe::create(['name' => 'خزنة مجزأة']);

        $response = $this->actingAs($user)->post(route('ledger.split.store'), [
            'party_category'   => 'investors',
            'investor_id'      => $investor->id,
            'status_id'        => $this->investorDepositStatus->id,
            'amount'           => '500',
            'bank_share'       => '300',
            'safe_share'       => '200',
            'bank_account_id'  => $bank->id,
            'safe_id'          => $safe->id,
            'transaction_date' => '2025-03-05',
            'notes'            => 'قيد مستثمر مجزأ',
        ]);

        $response->assertRedirect(route('ledger.index'));

        $transaction = InvestorTransaction::first();
        $this->assertNotNull($transaction);
        $this->assertSame($investor->id, $transaction->investor_id);
        $this->assertEquals(500.00, (float) $transaction->amount);
        $this->assertEquals('2025-03-05', $transaction->transaction_date->toDateString());

        $entries = LedgerEntry::orderBy('id')->get();
        $this->assertCount(2, $entries);
        $entries->each(function (LedgerEntry $entry) use ($transaction) {
            $this->assertSame('IT-' . $transaction->id, $entry->ref);
            $this->assertFalse($entry->is_office);
        });

        $bankEntry = $entries->firstWhere('bank_account_id', $bank->id);
        $this->assertNotNull($bankEntry);
        $this->assertEquals(300.00, (float) $bankEntry->amount);
        $this->assertNull($bankEntry->safe_id);
        $this->assertSame($investor->id, $bankEntry->investor_id);

        $safeEntry = $entries->firstWhere('safe_id', $safe->id);
        $this->assertNotNull($safeEntry);
        $this->assertEquals(200.00, (float) $safeEntry->amount);
        $this->assertNull($safeEntry->bank_account_id);
        $this->assertSame($investor->id, $safeEntry->investor_id);
    }

    public function test_split_store_creates_office_transaction_and_updates_refs(): void
    {
        $user = User::factory()->create();
        $bank = BankAccount::create(['name' => 'حساب مكتب']);
        $safe = Safe::create(['name' => 'خزنة مكتب']);

        $response = $this->actingAs($user)->post(route('ledger.split.store'), [
            'party_category'   => 'office',
            'status_id'        => $this->officeDepositStatus->id,
            'amount'           => '900',
            'bank_share'       => '450',
            'safe_share'       => '450',
            'bank_account_id'  => $bank->id,
            'safe_id'          => $safe->id,
            'transaction_date' => '2025-04-20',
            'notes'            => 'قيد مكتب مجزأ',
        ]);

        $response->assertRedirect(route('ledger.index'));

        $transaction = OfficeTransaction::first();
        $this->assertNotNull($transaction);
        $this->assertEquals(900.00, (float) $transaction->amount);
        $this->assertEquals('2025-04-20', $transaction->transaction_date);

        $entries = LedgerEntry::orderBy('id')->get();
        $this->assertCount(2, $entries);
        $entries->each(function (LedgerEntry $entry) use ($transaction) {
            $this->assertSame('OT-' . $transaction->id, $entry->ref);
            $this->assertTrue($entry->is_office);
            $this->assertNull($entry->investor_id);
        });

        $bankEntry = $entries->firstWhere('bank_account_id', $bank->id);
        $this->assertNotNull($bankEntry);
        $this->assertEquals(450.00, (float) $bankEntry->amount);
        $this->assertNull($bankEntry->safe_id);

        $safeEntry = $entries->firstWhere('safe_id', $safe->id);
        $this->assertNotNull($safeEntry);
        $this->assertEquals(450.00, (float) $safeEntry->amount);
        $this->assertNull($safeEntry->bank_account_id);
    }
}
