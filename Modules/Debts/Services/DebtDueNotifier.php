<?php

namespace Modules\Debts\Services;

use App\Models\User;
use App\Notifications\DebtDueNotification;
use Carbon\Carbon;
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
            ->orderBy('id')
            ->get();
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
