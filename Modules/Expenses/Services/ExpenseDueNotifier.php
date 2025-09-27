<?php

namespace Modules\Expenses\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Expenses\Entities\Expense;
use App\Models\User;
use App\Notifications\ExpenseDueNotification;

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
}
