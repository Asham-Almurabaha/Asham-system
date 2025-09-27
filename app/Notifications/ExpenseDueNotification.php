<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Modules\Expenses\Entities\Expense;

class ExpenseDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param Collection<int, Expense> $expenses
     */
    public function __construct(
        public Collection $expenses,
        public CarbonInterface $targetDate,
        public CarbonInterface $dispatchedAt,
    )
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->expenses->count();
        $mail = (new MailMessage())
            ->subject(__('expenses::notifications.mail.subject', ['count' => $count]))
            ->greeting(__('expenses::notifications.mail.greeting'))
            ->line(__('expenses::notifications.mail.intro', ['date' => $this->targetDate->toDateString(), 'count' => $count]));

        $this->expenses->each(function (Expense $expense, int $index) use (&$mail) {
            $line = ($index + 1).'. '.$expense->title.' — '.number_format($expense->amount, 2).' '.$expense->currency_code;

            if ($expense->type) {
                $line .= ' ('.$expense->type->name.')';
            }

            $line .= ' — '.__('expenses::notifications.mail.due_on', ['date' => $expense->due_date?->toDateString()]);

            if ($expense->reference) {
                $line .= ' — '.__('expenses::notifications.mail.reference', ['reference' => $expense->reference]);
            }

            $mail->line($line);

            if ($url = $this->expenseUrl($expense)) {
                $mail->line(__('expenses::notifications.mail.view_expense').': '.$url);
            }
        });

        $mail->line(__('expenses::notifications.mail.footer', ['datetime' => $this->dispatchedAt->toDateTimeString()]));
        $mail->salutation(__('expenses::notifications.mail.salutation'));

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reference_date' => $this->targetDate->toIso8601String(),
            'dispatched_at' => $this->dispatchedAt->toIso8601String(),
            'count' => $this->expenses->count(),
            'expenses' => $this->expenses->map(function (Expense $expense) {
                return [
                    'id' => $expense->id,
                    'title' => $expense->title,
                    'amount' => (float) $expense->amount,
                    'currency_code' => $expense->currency_code,
                    'due_date' => optional($expense->due_date)->toDateString(),
                    'reference' => $expense->reference,
                    'type' => $expense->type?->name,
                    'url' => $this->expenseUrl($expense),
                ];
            })->all(),
        ];
    }

    private function expenseUrl(Expense $expense): ?string
    {
        try {
            if (app('router')->has('expenses.expenses.edit')) {
                return route('expenses.expenses.edit', $expense);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
