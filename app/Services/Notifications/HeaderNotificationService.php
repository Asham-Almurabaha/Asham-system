<?php

namespace App\Services\Notifications;

use App\Models\Note;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Schema;
use Modules\Contracts\Services\DueInstallmentsNotifier;
use Modules\Debts\Services\DebtDueNotifier;
use Modules\Expenses\Services\ExpenseDueNotifier;
use Modules\Investors\DTOs\ZakatDueInvestor;
use Modules\Investors\Services\ZakatDueNotifier;

class HeaderNotificationService
{
    private const CACHE_PREFIX = 'header.notifications';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly DueInstallmentsNotifier $installmentsNotifier,
        private readonly ZakatDueNotifier $zakatNotifier,
        private readonly DebtDueNotifier $debtNotifier,
        private readonly ExpenseDueNotifier $expenseNotifier,
    ) {
    }

    public function forUser(?Authenticatable $user, string $locale): array
    {
        if (!$user instanceof User) {
            return $this->emptyPayload();
        }

        $key = $this->cacheKey($user, $locale);
        $ttl = max(1, (int) config('notifications.header.cache_ttl_seconds', 60));

        return $this->cache->remember($key, $ttl, function () use ($user) {
            return $this->buildPayload($user);
        });
    }

    public function invalidate(): void
    {
        $versionKey = $this->versionCacheKey();
        $current = $this->cacheVersion();

        $incremented = $this->cache->increment($versionKey);

        if ($incremented === false || $incremented === null) {
            $this->cache->forever($versionKey, $current + 1);
        }
    }

    private function buildPayload(User $user): array
    {
        $payload = $this->emptyPayload();

        $installments = $this->installmentSection($user);
        $payload['installments'] = $installments;
        $payload['total'] += $installments['count'];

        $zakat = $this->zakatSection();
        $payload['zakat'] = $zakat;
        $payload['total'] += $zakat['count'];

        $debts = $this->debtSection($user);
        $payload['debts'] = $debts;
        $payload['total'] += $debts['count'];

        $expenses = $this->expenseSection($user);
        $payload['expenses'] = $expenses;
        $payload['total'] += $expenses['count'];

        $notes = $this->noteSection($user);
        $payload['notes'] = $notes;
        $payload['total'] += $notes['count'];

        return $payload;
    }

    private function installmentSection(User $user): array
    {
        if (!Schema::hasTable('contract_installments') || !Schema::hasTable('contracts')) {
            return $this->emptySection();
        }

        if (!$user->can('contracts.index')) {
            return $this->emptySection();
        }

        $data = $this->installmentsNotifier->today();

        return [
            'count' => (int) ($data['count'] ?? 0),
            'items' => array_values($data['items'] ?? []),
        ];
    }

    private function zakatSection(): array
    {
        if (!Schema::hasTable('investors') || !Schema::hasTable('ledger_entries')) {
            return $this->emptySection();
        }

        $report = $this->zakatNotifier->preview();

        return [
            'count' => $report->investorsCount(),
            'items' => $report->entries
                ->map(function (ZakatDueInvestor $entry) {
                    return [
                        'id' => $entry->investor->getKey(),
                        'name' => $entry->investor->name,
                        'due_date' => $entry->dueDate->toDateString(),
                        'days_overdue' => $entry->daysOverdue,
                        'amount' => $entry->amount,
                        'currency' => $entry->currencySymbol,
                    ];
                })
                ->take(5)
                ->values()
                ->all(),
        ];
    }

    private function debtSection(User $user): array
    {
        if (!Schema::hasTable('debts')) {
            return $this->emptySection();
        }

        if (!$user->can('debts.index')) {
            return $this->emptySection();
        }

        $data = $this->debtNotifier->summary();

        return [
            'count' => (int) ($data['count'] ?? 0),
            'items' => array_values($data['items'] ?? []),
        ];
    }

    private function expenseSection(User $user): array
    {
        if (!Schema::hasTable('expenses') || !Schema::hasTable('expense_types')) {
            return $this->emptySection();
        }

        if (!$user->can('expenses.expenses.index')) {
            return $this->emptySection();
        }

        $data = $this->expenseNotifier->headerSummary();

        return [
            'count' => (int) ($data['count'] ?? 0),
            'items' => array_values($data['items'] ?? []),
        ];
    }

    private function noteSection(User $user): array
    {
        if (!Schema::hasTable('notes')) {
            return $this->emptySection();
        }

        if (!$user->can('notes.index')) {
            return $this->emptySection();
        }

        $notes = Note::query()
            ->where('user_id', $user->getKey())
            ->whereNull('completed_at')
            ->whereNotNull('reminder_at')
            ->orderBy('reminder_at')
            ->take(5)
            ->get();

        $now = Carbon::now();

        $dueCount = $notes
            ->filter(fn (Note $note) => $note->reminder_at && $note->reminder_at->lte($now))
            ->count();

        return [
            'count' => $dueCount,
            'items' => $notes->map(function (Note $note) use ($now) {
                $reminderAt = $note->reminder_at?->copy();

                return [
                    'id' => $note->getKey(),
                    'title' => $note->title,
                    'reminder_at' => $reminderAt?->toIso8601String(),
                    'is_due' => $reminderAt ? $reminderAt->lte($now) : false,
                    'is_overdue' => $reminderAt ? $reminderAt->lt($now) : false,
                    'diff_days' => $reminderAt ? $reminderAt->diffInDays($now, false) : null,
                ];
            })->all(),
        ];
    }

    private function emptyPayload(): array
    {
        return [
            'total' => 0,
            'zakat' => $this->emptySection(),
            'installments' => $this->emptySection(),
            'debts' => $this->emptySection(),
            'expenses' => $this->emptySection(),
            'notes' => $this->emptySection(),
        ];
    }

    private function emptySection(): array
    {
        return [
            'count' => 0,
            'items' => [],
        ];
    }

    private function cacheKey(User $user, string $locale): string
    {
        $version = $this->cacheVersion();
        $identifier = $user->getAuthIdentifier() ?? 'guest';

        return sprintf('%s.%d.%s.%s', self::CACHE_PREFIX, $version, $locale, $identifier);
    }

    private function cacheVersion(): int
    {
        $versionKey = $this->versionCacheKey();
        $version = $this->cache->get($versionKey);

        if (!is_int($version) || $version < 1) {
            $version = 1;
            $this->cache->forever($versionKey, $version);
        }

        return $version;
    }

    private function versionCacheKey(): string
    {
        return self::CACHE_PREFIX.'.version';
    }
}
