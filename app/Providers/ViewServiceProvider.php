<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Investors\DTOs\ZakatDueInvestor;
use Modules\Investors\Services\ZakatDueNotifier;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
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
            ];

            if (!Auth::check()) {
                $view->with('headerNotifications', $notifications);

                return;
            }

            if (!Schema::hasTable('investors') || !Schema::hasTable('ledger_entries')) {
                $view->with('headerNotifications', $notifications);

                return;
            }

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

            $notifications['total'] = (int) ($data['count'] ?? 0);
            $notifications['zakat'] = [
                'count' => (int) ($data['count'] ?? 0),
                'items' => $data['items'] ?? [],
            ];

            $view->with('headerNotifications', $notifications);
        });
    }
}
