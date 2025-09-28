<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\DebtDueNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Debts\Entities\Debt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DebtDueNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::parse('2025-01-10 09:00:00');
        Carbon::setTestNow($this->now);

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_sends_notifications_for_due_debts(): void
    {
        Notification::fake();

        $user = $this->createUserWithPermission();
        $debt = $this->createDebt([
            'due_at' => $this->now->copy()->subDay()->toDateString(),
            'paid_amount' => 250,
        ]);

        $this->artisan('debts:notify-due')->assertExitCode(0);

        Notification::assertSentTo($user, DebtDueNotification::class, function (DebtDueNotification $notification) use ($debt) {
            $this->assertCount(1, $notification->debts);
            $first = $notification->debts->first();
            $this->assertSame($debt->id, $first->id);

            return true;
        });

        $debt->refresh();
        $this->assertNotNull($debt->last_notified_at);
        $this->assertTrue($debt->last_notified_at->isSameDay($this->now));
    }

    public function test_command_skips_debts_already_notified_for_same_day(): void
    {
        Notification::fake();

        $this->createUserWithPermission();
        $debt = $this->createDebt([
            'due_at' => $this->now->copy()->subDays(2)->toDateString(),
            'last_notified_at' => $this->now->copy()->toDateString(),
        ]);

        $this->artisan('debts:notify-due')->assertExitCode(0);

        Notification::assertNothingSent();

        $debt->refresh();
        $this->assertNotNull($debt->last_notified_at);
    }

    public function test_force_option_resends_even_if_already_notified(): void
    {
        Notification::fake();

        $user = $this->createUserWithPermission();
        $debt = $this->createDebt([
            'due_at' => $this->now->copy()->subDays(2)->toDateString(),
            'last_notified_at' => $this->now->copy()->subDay()->toDateString(),
        ]);

        $this->artisan('debts:notify-due', ['--force' => true])->assertExitCode(0);

        Notification::assertSentTo($user, DebtDueNotification::class);

        $debt->refresh();
        $this->assertNotNull($debt->last_notified_at);
        $this->assertTrue($debt->last_notified_at->greaterThan($this->now->copy()->subDay()));
    }

    private function createDebt(array $overrides = []): Debt
    {
        $data = array_merge([
            'party_type' => 'other',
            'principal_amount' => 1000,
            'paid_amount' => 0,
            'issued_at' => $this->now->copy()->subMonth()->toDateString(),
            'due_at' => $this->now->copy()->toDateString(),
            'counterparty_name' => 'مديون '.uniqid(),
        ], $overrides);

        return Debt::create($data);
    }

    private function createUserWithPermission(): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'debts.index',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(['email' => 'debt-manager@example.com']);
        $user->givePermissionTo($permission);

        return $user;
    }
}
