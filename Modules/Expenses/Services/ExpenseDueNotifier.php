<?php

namespace Modules\Expenses\Services;

use App\Models\User;
use App\Notifications\ExpenseDueNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Expenses\Entities\Expense;

class ExpenseDueNotifier
{
    public function notify(bool $force = false, ?Carbon $date = null): array
    {
        $date = $date?->copy() ?? Carbon::today();
        $expenses = $this->dueExpenses($date, $force);

        if ($expenses->isEmpty()) {
            return [
                'dispatched' => false,
                'count' => 0,
                'recipients' => collect(),
            ];
        }

        $recipients = $this->recipients();

        if ($recipients->isEmpty()) {
            return [
                'dispatched' => false,
                'count' => $expenses->count(),
                'recipients' => $recipients,
            ];
        }

        $notification = new ExpenseDueNotification($expenses, $date, now());
        Notification::send($recipients, $notification);

        foreach ($expenses as $expense) {
            $expense->markNotified();
        }

        return [
            'dispatched' => true,
            'count' => $expenses->count(),
            'recipients' => $recipients,
        ];
    }

    public function headerSummary(?Carbon $date = null, int $limit = 5): array
    {
        $date = $date?->copy() ?? Carbon::today();
        $maxLead = 30; // Maximum lead time window in days

        $expenses = Expense::query()
            ->with(['type.recurrencePeriod'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $date->copy()->addDays($maxLead))
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->filter(function (Expense $expense) use ($date) {
                $dueDate = $expense->due_date;

                if (!$dueDate) {
                    return false;
                }

                $lead = $this->leadTimeForExpense($expense);
                $threshold = $date->copy()->addDays($lead);

                return $dueDate->lte($threshold);
            });

        $count = $expenses->count();

        if ($count === 0) {
            return ['count' => 0, 'items' => []];
        }

        $limit = max(1, $limit);

        $items = $expenses->take($limit)->map(function (Expense $expense) use ($date) {
            $dueDate = $expense->due_date?->copy();
            $daysOverdue = null;
            $daysUntilDue = null;

            if ($dueDate) {
                if ($dueDate->lt($date)) {
                    $daysOverdue = $dueDate->diffInDays($date);
                    $daysUntilDue = 0;
                } elseif ($dueDate->gt($date)) {
                    $daysUntilDue = $date->diffInDays($dueDate);
                    $daysOverdue = 0;
                } else {
                    $daysOverdue = 0;
                    $daysUntilDue = 0;
                }
            }

            return [
                'id' => $expense->getKey(),
                'title' => $expense->title,
                'amount' => (float) $expense->amount,
                'due_date' => $dueDate?->toDateString(),
                'type' => $expense->type?->name,
                'recurrence' => $expense->type?->recurrencePeriod?->name,
                'currency' => $expense->type?->currency_code ?? config('app.currency_symbol', 'ر.س'),
                'days_overdue' => $daysOverdue,
                'days_until_due' => $daysUntilDue,
            ];
        })->values()->all();

        return [
            'count' => $count,
            'items' => $items,
        ];
    }

    protected function dueExpenses(Carbon $date, bool $force = false): Collection
    {
        return Expense::query()
            ->with('type')
            ->whereDate('due_date', '<=', $date)
            ->when(!$force, function ($query) use ($date) {
                $query->where(function ($inner) use ($date) {
                    $inner->whereNull('last_notified_at')
                        ->orWhereDate('last_notified_at', '<', $date);
                });
            })
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    protected function recipients(): Collection
    {
        return User::query()
            ->permission('expenses.expenses.index')
            ->whereNotNull('email')
            ->get();
    }

    protected function leadTimeForExpense(Expense $expense): int
    {
        $periodName = $expense->type?->recurrencePeriod?->name;

        if (!$periodName) {
            return 0;
        }

        $normalized = Str::lower(trim($periodName));

        $monthlyAliases = ['monthly', 'شهري'];
        $semiAnnualAliases = ['semi annual', 'semi-annual', 'semiannual', 'biannual', 'half-year', 'half yearly', 'half-yearly', 'نصف سنوي'];
        $annualAliases = ['annual', 'سنوي', 'yearly'];

        if (in_array($normalized, $monthlyAliases, true)) {
            return 5;
        }

        if (in_array($normalized, $semiAnnualAliases, true) || in_array($normalized, $annualAliases, true)) {
            return 30;
        }

        return 0;
    }
}
