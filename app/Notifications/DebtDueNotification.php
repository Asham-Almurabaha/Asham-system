<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Modules\Debts\Entities\Debt;

class DebtDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, Debt>  $debts
     */
    public function __construct(
        public Collection $debts,
        public CarbonInterface $targetDate,
        public CarbonInterface $dispatchedAt,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->debts->count();
        $mail = (new MailMessage())
            ->subject(__('debts::notifications.mail.subject', ['count' => $count]))
            ->greeting(__('debts::notifications.mail.greeting'))
            ->line(__('debts::notifications.mail.intro', [
                'date' => $this->targetDate->toDateString(),
                'count' => $count,
            ]));

        $this->debts->each(function (Debt $debt, int $index) use (&$mail) {
            $line = ($index + 1).'. '.$this->debtDisplayName($debt);
            $line .= ' — '.__('debts::notifications.mail.outstanding', [
                'amount' => number_format($debt->outstanding_amount, 2),
            ]);

            if ($debt->due_at) {
                $line .= ' — '.__('debts::notifications.mail.due_on', [
                    'date' => $debt->due_at->toDateString(),
                ]);
            }

            $mail->line($line);

            if ($url = $this->debtUrl($debt)) {
                $mail->line(__('debts::notifications.mail.view_debt').': '.$url);
            }
        });

        $mail->line(__('debts::notifications.mail.footer', [
            'datetime' => $this->dispatchedAt->toDateTimeString(),
        ]));
        $mail->salutation(__('debts::notifications.mail.salutation'));

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reference_date' => $this->targetDate->toIso8601String(),
            'dispatched_at' => $this->dispatchedAt->toIso8601String(),
            'count' => $this->debts->count(),
            'debts' => $this->debts->map(function (Debt $debt) {
                return [
                    'id' => $debt->id,
                    'name' => $this->debtDisplayName($debt),
                    'outstanding_amount' => (float) $debt->outstanding_amount,
                    'due_date' => $debt->due_at?->toDateString(),
                    'url' => $this->debtUrl($debt),
                ];
            })->all(),
        ];
    }

    private function debtDisplayName(Debt $debt): string
    {
        return $debt->counterparty_name
            ?? $debt->customer?->name
            ?? $debt->investor?->name
            ?? __('debts::notifications.mail.unknown_name', ['id' => $debt->id]);
    }

    private function debtUrl(Debt $debt): ?string
    {
        try {
            if (app('router')->has('debts.edit')) {
                return route('debts.edit', $debt);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
