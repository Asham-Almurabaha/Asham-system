<?php

namespace Modules\Investors\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Investors\DTOs\ZakatDueInvestor;
use Modules\Investors\DTOs\ZakatNotificationReport;
use Modules\Investors\Entities\Investor;
use App\Models\User;
use App\Notifications\ZakatDueNotification;

class ZakatDueNotifier
{
    public function __construct(private readonly InvestorDataService $investorData)
    {
    }

    public function execute(bool $force = false): ZakatNotificationReport
    {
        $dueInvestors = $this->gatherDueInvestors($force);
        $adminRecipients = $this->adminRecipients();
        $emailRecipients = $this->emailRecipients($adminRecipients);

        if ($dueInvestors->isEmpty()) {
            return new ZakatNotificationReport($dueInvestors, $adminRecipients, $emailRecipients, null);
        }

        if ($adminRecipients->isEmpty() && $emailRecipients->isEmpty()) {
            Log::warning('Zakat due notification skipped because no recipients were found.');

            return new ZakatNotificationReport($dueInvestors, $adminRecipients, $emailRecipients, null);
        }

        $dispatchedAt = Carbon::now();
        $notification = new ZakatDueNotification($dueInvestors, $dispatchedAt);

        if ($adminRecipients->isNotEmpty()) {
            Notification::send($adminRecipients, $notification);
        }

        foreach ($emailRecipients as $email) {
            Notification::route('mail', $email)->notify($notification);
        }

        $this->markInvestorsAsNotified($dueInvestors, $dispatchedAt);

        return new ZakatNotificationReport($dueInvestors, $adminRecipients, $emailRecipients, $dispatchedAt);
    }

    private function gatherDueInvestors(bool $force = false): Collection
    {
        $investors = Investor::query()->orderBy('id')->get();
        $due = collect();

        foreach ($investors as $investor) {
            $data = $this->investorData->build($investor);
            $currency = $data['currencySymbol'] ?? 'ر.س';
            $zakat = $data['zakat'] ?? null;

            if (!is_array($zakat)) {
                $this->maybeResetNotification($investor, null);
                continue;
            }

            $dueDate = $this->normalizeCarbon($zakat['due_date'] ?? null);
            $isDue = (bool) ($zakat['is_due'] ?? false);
            $amount = isset($zakat['amount']) ? (float) $zakat['amount'] : 0.0;
            $base = isset($zakat['base']) ? (float) $zakat['base'] : 0.0;
            $rate = isset($zakat['rate']) ? (float) $zakat['rate'] : 0.0;
            $daysOverdue = $this->normalizeInteger($zakat['days_overdue'] ?? null);
            $startDate = $this->normalizeCarbon($zakat['start_date'] ?? null);
            $lastPayment = $this->normalizeCarbon($zakat['last_entry_date'] ?? null);

            if (!$isDue || $amount <= 0 || !$dueDate) {
                $this->maybeResetNotification($investor, $dueDate);
                continue;
            }

            if (!$force && $this->alreadyNotifiedFor($investor, $dueDate)) {
                continue;
            }

            $due->push(new ZakatDueInvestor(
                investor: $investor->fresh(),
                dueDate: $dueDate,
                amount: $amount,
                base: $base,
                daysOverdue: $daysOverdue,
                rate: $rate > 0 ? $rate : 0.025,
                startDate: $startDate,
                lastPaymentDate: $lastPayment,
                currencySymbol: $currency,
            ));
        }

        return $due;
    }

    private function adminRecipients(): Collection
    {
        return User::query()
            ->role('admin')
            ->whereNotNull('email')
            ->get();
    }

    private function emailRecipients(Collection $adminRecipients): Collection
    {
        $configured = collect(config('notifications.zakat.emails', []))
            ->filter(function ($email) {
                if (!is_string($email)) {
                    return false;
                }

                $trimmed = trim($email);

                return $trimmed !== '' && filter_var($trimmed, FILTER_VALIDATE_EMAIL);
            })
            ->map(fn ($email) => trim($email))
            ->unique(fn ($email) => Str::lower($email));

        if ($configured->isEmpty()) {
            return collect();
        }

        $adminEmails = $adminRecipients
            ->pluck('email')
            ->filter()
            ->map(fn ($email) => Str::lower($email))
            ->values();

        return $configured
            ->reject(fn ($email) => $adminEmails->contains(Str::lower($email)))
            ->values();
    }

    private function markInvestorsAsNotified(Collection $investors, CarbonInterface $dispatchedAt): void
    {
        foreach ($investors as $entry) {
            if (!$entry instanceof ZakatDueInvestor) {
                continue;
            }

            $investor = $entry->investor->fresh();
            if (!$investor) {
                continue;
            }

            $investor->forceFill([
                'zakat_last_notified_due_date' => $entry->dueDate->copy(),
                'zakat_last_notified_at' => $dispatchedAt->copy(),
            ])->save();
        }
    }

    private function alreadyNotifiedFor(Investor $investor, CarbonInterface $dueDate): bool
    {
        $lastDue = $investor->zakat_last_notified_due_date;
        if (!$lastDue instanceof CarbonInterface) {
            return false;
        }

        return $lastDue->isSameDay($dueDate);
    }

    private function maybeResetNotification(Investor $investor, ?CarbonInterface $currentDueDate): void
    {
        $lastDue = $investor->zakat_last_notified_due_date;
        if (!$lastDue instanceof CarbonInterface) {
            return;
        }

        if ($currentDueDate instanceof CarbonInterface && $lastDue->isSameDay($currentDueDate)) {
            return;
        }

        $investor->forceFill([
            'zakat_last_notified_due_date' => null,
            'zakat_last_notified_at' => null,
        ])->save();
    }

    private function normalizeCarbon(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value->copy();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return null;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if (is_null($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
