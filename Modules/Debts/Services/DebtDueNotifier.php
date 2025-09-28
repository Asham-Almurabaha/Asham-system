<?php

namespace Modules\Debts\Services;

use App\Models\User;
use App\Notifications\DebtDueNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Debts\Entities\Debt;

class DebtDueNotifier
{
    public function notify(bool $force = false, ?Carbon $date = null): array
    {
        $date = $date?->copy() ?? Carbon::today();
        $debts = $this->dueDebts($date, $force);

        if ($debts->isEmpty()) {
            return [
                'dispatched' => false,
                'count' => 0,
                'user_recipients' => collect(),
                'email_recipients' => [],
            ];
        }

        $userRecipients = $this->userRecipients();
        $emailRecipients = $this->emailRecipients();

        if ($userRecipients->isEmpty() && empty($emailRecipients)) {
            return [
                'dispatched' => false,
                'count' => $debts->count(),
                'user_recipients' => $userRecipients,
                'email_recipients' => $emailRecipients,
            ];
        }

        $notification = new DebtDueNotification($debts, $date, now());

        if ($userRecipients->isNotEmpty()) {
            Notification::send($userRecipients, $notification);
        }

        foreach ($emailRecipients as $email) {
            Notification::route('mail', $email)->notify($notification);
        }

        foreach ($debts as $debt) {
            $debt->forceFill(['last_notified_at' => now()])->save();
        }

        return [
            'dispatched' => true,
            'count' => $debts->count(),
            'user_recipients' => $userRecipients,
            'email_recipients' => $emailRecipients,
        ];
    }

    protected function dueDebts(Carbon $date, bool $force = false): Collection
    {
        return $this->dueDebtsQuery($date, $force)->get();
    }

    protected function dueDebtsQuery(Carbon $date, bool $force = false): Builder
    {
        return Debt::query()
            ->with(['customer:id,name', 'investor:id,name'])
            ->whereColumn('principal_amount', '>', 'paid_amount')
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<=', $date)
            ->when(! $force, function ($query) use ($date) {
                $query->where(function ($inner) use ($date) {
                    $inner->whereNull('last_notified_at')
                        ->orWhereDate('last_notified_at', '<', $date);
                });
            })
            ->orderBy('due_at')
            ->orderBy('id');
    }

    public function summary(?Carbon $date = null, int $limit = 5): array
    {
        $date = $date?->copy() ?? Carbon::today();
        $query = $this->dueDebtsQuery($date);

        $count = (clone $query)->count();

        if ($count === 0) {
            return ['count' => 0, 'items' => []];
        }

        $currency = config('app.currency_symbol', 'ر.س');
        $limit = max(1, $limit);

        $items = $query->limit($limit)->get()->map(function (Debt $debt) use ($currency, $date) {
            $dueDate = $debt->due_at?->copy();
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

            $partyName = $debt->counterparty_name
                ?? $debt->customer?->name
                ?? $debt->investor?->name;

            return [
                'id' => $debt->getKey(),
                'title' => $partyName,
                'due_date' => $dueDate?->toDateString(),
                'remaining_amount' => (float) $debt->outstanding_amount,
                'currency' => $currency,
                'days_overdue' => $daysOverdue,
                'days_until_due' => $daysUntilDue,
                'party_type' => $debt->party_type,
            ];
        })->all();

        return [
            'count' => $count,
            'items' => $items,
        ];
    }

    protected function userRecipients(): Collection
    {
        return User::query()
            ->permission('debts.index')
            ->whereNotNull('email')
            ->get();
    }

    protected function emailRecipients(): array
    {
        return config('notifications.debts.emails', []);
    }
}
