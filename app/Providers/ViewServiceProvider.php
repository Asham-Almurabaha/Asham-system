<?php

namespace App\Providers;

use App\Models\Note;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Modules\Contracts\Services\DueInstallmentsNotifier;
use Modules\Debts\Services\DebtDueNotifier;
use Modules\Expenses\Services\ExpenseDueNotifier;
use Modules\Investors\DTOs\ZakatDueInvestor;
use Modules\Investors\Services\ZakatDueNotifier;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $permissionResolver = function (): array {
            static $cached;

            if ($cached !== null) {
                return $cached;
            }

            $user = Auth::user();

            if (!$user) {
                return $cached = [];
            }

            $permissions = $user->getAllPermissions()->pluck('name')->all();

            $personalAccess = [
                'settings.account.*',
                'settings.account.edit',
                'settings.account.profile.update',
                'settings.account.password.update',
            ];

            return $cached = array_values(array_unique(array_merge($permissions, $personalAccess)));
        };

        $normalizePatterns = fn(array $patterns): array => array_values(array_filter(array_map(
            fn($pattern) => is_string($pattern) ? trim($pattern) : null,
            $patterns
        ), fn($pattern) => $pattern !== null && $pattern !== ''));

        $hasPermissionForPattern = function (array $permissions, string $pattern): bool {
            foreach ($permissions as $permission) {
                if (Str::is($pattern, $permission)) {
                    return true;
                }
            }

            return false;
        };

        Blade::if('routecanany', function (...$patterns) use ($permissionResolver, $normalizePatterns, $hasPermissionForPattern): bool {
            $flattened = [];

            foreach ($patterns as $pattern) {
                if (is_array($pattern)) {
                    $flattened = array_merge($flattened, $pattern);
                } elseif (is_string($pattern)) {
                    $flattened[] = $pattern;
                }
            }

            $flattened = $normalizePatterns($flattened);

            if (empty($flattened)) {
                return false;
            }

            $permissions = $permissionResolver();

            foreach ($flattened as $pattern) {
                if ($hasPermissionForPattern($permissions, $pattern)) {
                    return true;
                }
            }

            return false;
        });

        Blade::if('routecan', function (...$patterns) use ($permissionResolver, $normalizePatterns, $hasPermissionForPattern): bool {
            $flattened = [];

            foreach ($patterns as $pattern) {
                if (is_array($pattern)) {
                    $flattened = array_merge($flattened, $pattern);
                } elseif (is_string($pattern)) {
                    $flattened[] = $pattern;
                }
            }

            $flattened = $normalizePatterns($flattened);

            if (empty($flattened)) {
                return false;
            }

            $permissions = $permissionResolver();

            foreach ($flattened as $pattern) {
                if (!$hasPermissionForPattern($permissions, $pattern)) {
                    return false;
                }
            }

            return true;
        });

        // شارِك $setting و $appName على كل القوالب
        View::composer('*', function ($view) {
            $setting = null;

            // تجنب مشاكل وقت المايجريشن
            if (Schema::hasTable('settings')) {
                $setting = Cache::remember('app.setting.latest', 3600, function () {
                    return Setting::query()->latest('id')->first();
                });
            }

            $appName = $setting?->name ?? config('app.name', 'لوحة التحكم');

            $view->with(compact('setting', 'appName'));
        });

        View::composer('layouts.header', function ($view) {
            $notifications = [
                'total' => 0,
                'zakat' => [
                    'count' => 0,
                    'items' => [],
                ],
                'installments' => [
                    'count' => 0,
                    'items' => [],
                ],
                'debts' => [
                    'count' => 0,
                    'items' => [],
                ],
                'expenses' => [
                    'count' => 0,
                    'items' => [],
                ],
                'notes' => [
                    'count' => 0,
                    'items' => [],
                ],
            ];

            if (!Auth::check()) {
                $view->with('headerNotifications', $notifications);

                return;
            }

            if (Schema::hasTable('contract_installments') && Schema::hasTable('contracts')) {
                $user = Auth::user();

                if ($user && $user->can('contracts.index')) {
                    $locale = app()->getLocale();
                    $cacheKey = "header.installments.notifications.{$locale}";

                    $data = Cache::remember($cacheKey, 60, function () {
                        return app(DueInstallmentsNotifier::class)->today();
                    });

                    $count = (int) ($data['count'] ?? 0);
                    $items = $data['items'] ?? [];

                    $notifications['installments'] = [
                        'count' => $count,
                        'items' => array_values($items),
                    ];

                    $notifications['total'] += $count;
                }
            }

            if (Schema::hasTable('investors') && Schema::hasTable('ledger_entries')) {
                $locale = app()->getLocale();
                $cacheKey = "header.zakat.notifications.{$locale}";

                $data = Cache::remember($cacheKey, 60, function () {
                    $report = app(ZakatDueNotifier::class)->preview();

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
                });

                $notifications['total'] += (int) ($data['count'] ?? 0);
                $notifications['zakat'] = [
                    'count' => (int) ($data['count'] ?? 0),
                    'items' => $data['items'] ?? [],
                ];
            }

            if (Schema::hasTable('debts')) {
                $user = Auth::user();

                if ($user && $user->can('debts.index')) {
                    $locale = app()->getLocale();
                    $cacheKey = "header.debts.notifications.{$locale}";

                    $data = Cache::remember($cacheKey, 60, function () {
                        return app(DebtDueNotifier::class)->summary();
                    });

                    $count = (int) ($data['count'] ?? 0);
                    $items = $data['items'] ?? [];

                    $notifications['debts'] = [
                        'count' => $count,
                        'items' => array_values($items),
                    ];

                    $notifications['total'] += $count;
                }
            }

            if (Schema::hasTable('expenses') && Schema::hasTable('expense_types')) {
                $user = Auth::user();

                if ($user && $user->can('expenses.expenses.index')) {
                    $locale = app()->getLocale();
                    $cacheKey = "header.expenses.notifications.{$locale}";

                    $data = Cache::remember($cacheKey, 60, function () {
                        return app(ExpenseDueNotifier::class)->headerSummary();
                    });

                    $count = (int) ($data['count'] ?? 0);
                    $items = $data['items'] ?? [];

                    $notifications['expenses'] = [
                        'count' => $count,
                        'items' => array_values($items),
                    ];

                    $notifications['total'] += $count;
                }
            }

            if (Schema::hasTable('notes')) {
                $user = Auth::user();

                if ($user && $user->can('notes.index')) {
                    $notes = Note::query()
                        ->where('user_id', $user->getKey())
                        ->whereNull('completed_at')
                        ->whereNotNull('reminder_at')
                        ->orderBy('reminder_at')
                        ->take(5)
                        ->get();

                    $now = Carbon::now();

                    $dueCount = $notes->filter(fn(Note $note) => $note->reminder_at && $note->reminder_at->lte($now))->count();

                    $notifications['notes'] = [
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

                    $notifications['total'] += $dueCount;
                }
            }

            $view->with('headerNotifications', $notifications);
        });
    }
}
