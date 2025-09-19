<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Notifications\ZakatDueNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Investors\Entities\Investor;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ZakatDueNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $now;

    private TransactionType $depositType;

    private TransactionType $withdrawType;

    private TransactionStatus $depositStatus;

    private TransactionStatus $zakatStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::parse('2025-03-01 09:00:00');
        Carbon::setTestNow($this->now);

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->setupLedgerLookups();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_sends_notifications_for_due_investors(): void
    {
        Notification::fake();

        $investor = $this->createDueInvestor();
        $admin = $this->createAdminUser();

        $this->artisan('zakat:notify')->assertExitCode(0);

        Notification::assertSentTo($admin, ZakatDueNotification::class, function (ZakatDueNotification $notification) use ($investor) {
            $this->assertCount(1, $notification->investors);
            $entry = $notification->investors->first();
            $this->assertSame($investor->id, $entry->investor->id);
            $this->assertEqualsWithDelta(250.0, $entry->amount, 0.01);

            return true;
        });

        $investor->refresh();
        $this->assertNotNull($investor->zakat_last_notified_at);
        $this->assertNotNull($investor->zakat_last_notified_due_date);

        $expectedDueDate = Carbon::parse($investor->investment_start_date)->addDays(354);
        $this->assertTrue($investor->zakat_last_notified_due_date->isSameDay($expectedDueDate));
    }

    public function test_command_skips_investors_already_notified_for_same_cycle(): void
    {
        Notification::fake();

        $investor = $this->createDueInvestor();
        $admin = $this->createAdminUser();

        $this->artisan('zakat:notify')->assertExitCode(0);
        Notification::assertSentTo($admin, ZakatDueNotification::class);

        Notification::fake();
        $this->artisan('zakat:notify')->assertExitCode(0);
        Notification::assertNothingSent();

        $investor->refresh();
        $this->assertNotNull($investor->zakat_last_notified_due_date);
    }

    public function test_force_option_resends_even_if_already_notified(): void
    {
        Notification::fake();

        $investor = $this->createDueInvestor();
        $admin = $this->createAdminUser();

        $this->artisan('zakat:notify')->assertExitCode(0);

        $investor = $investor->fresh();
        $firstTimestamp = $investor->zakat_last_notified_at;

        Notification::fake();
        Carbon::setTestNow($this->now->copy()->addDay());

        $this->artisan('zakat:notify', ['--force' => true])->assertExitCode(0);

        Notification::assertSentTo($admin, ZakatDueNotification::class);

        $investor->refresh();
        $this->assertNotNull($investor->zakat_last_notified_at);
        $this->assertTrue($investor->zakat_last_notified_at->greaterThan($firstTimestamp));
    }

    public function test_investor_notification_state_resets_after_new_zakat_entry(): void
    {
        Notification::fake();

        $investor = $this->createDueInvestor();
        $this->createAdminUser();

        $this->artisan('zakat:notify')->assertExitCode(0);
        $investor = $investor->fresh();
        $this->assertNotNull($investor->zakat_last_notified_due_date);

        Carbon::setTestNow($this->now->copy()->addDays(10));

        LedgerEntry::create([
            'entry_date' => Carbon::now()->toDateString(),
            'investor_id' => $investor->id,
            'is_office' => false,
            'transaction_status_id' => $this->zakatStatus->id,
            'transaction_type_id' => $this->withdrawType->id,
            'amount' => 250,
            'direction' => 'out',
        ]);

        Notification::fake();
        $this->artisan('zakat:notify')->assertExitCode(0);
        Notification::assertNothingSent();

        $investor->refresh();
        $this->assertNull($investor->zakat_last_notified_due_date);
        $this->assertNull($investor->zakat_last_notified_at);
    }

    private function createDueInvestor(): Investor
    {
        $investor = Investor::create([
            'name' => 'مستثمر '.uniqid(),
            'investment_start_date' => $this->now->copy()->subDays(400)->toDateString(),
        ]);

        LedgerEntry::create([
            'entry_date' => $this->now->copy()->subDays(400)->toDateString(),
            'investor_id' => $investor->id,
            'is_office' => false,
            'transaction_status_id' => $this->depositStatus->id,
            'transaction_type_id' => $this->depositType->id,
            'amount' => 10000,
            'direction' => 'in',
        ]);

        return $investor;
    }

    private function createAdminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function setupLedgerLookups(): void
    {
        $this->depositType = TransactionType::create(['name' => 'إيداع']);
        $this->withdrawType = TransactionType::create(['name' => 'سحب']);

        $this->depositStatus = TransactionStatus::create([
            'name' => 'إضافة سيولة',
            'transaction_type_id' => $this->depositType->id,
        ]);

        $this->zakatStatus = TransactionStatus::create([
            'name' => 'زكاة المال',
            'transaction_type_id' => $this->withdrawType->id,
        ]);
    }
}
