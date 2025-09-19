<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Modules\Investors\DTOs\ZakatDueInvestor;

class ZakatDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param Collection<int, ZakatDueInvestor> $investors
     */
    public function __construct(public Collection $investors, public CarbonInterface $dispatchedAt)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->investors->count();
        $mail = (new MailMessage())
            ->subject('تنبيه بموعد زكاة مستثمرين متأخرة')
            ->greeting('مرحباً،')
            ->line('رصد النظام مستثمرين حان موعد زكاتهم ولم يتم تسجيل سداد لها بعد.');

        $mail->line('عدد المستثمرين المتأخرين: '.$count);

        $this->investors->each(function (ZakatDueInvestor $item, int $index) use ($mail) {
            $line = ($index + 1).'. '.$item->investor->name.' — مبلغ الزكاة التقديري '
                .number_format($item->amount, 2).' '.$item->currencySymbol;

            if (!is_null($item->daysOverdue)) {
                $line .= ' (متأخر منذ '.number_format($item->daysOverdue).' يوم)';
            }

            $line .= ' — موعد الاستحقاق: '.$item->dueDate->toDateString();

            $mail->line($line);

            $url = $this->investorUrl($item);
            if ($url) {
                $mail->line('رابط التفاصيل: '.$url);
            }
        });

        $mail->line('تم إرسال هذا التنبيه بتاريخ '.$this->dispatchedAt->toDateTimeString().'.');
        $mail->salutation('مع تحيات نظام إدارة المستثمرين');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dispatched_at' => $this->dispatchedAt->toIso8601String(),
            'investors' => $this->investors->map(fn (ZakatDueInvestor $item) => $item->toArray())->all(),
        ];
    }

    private function investorUrl(ZakatDueInvestor $item): ?string
    {
        try {
            if (app('router')->has('investors.show')) {
                return route('investors.show', $item->investor);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
