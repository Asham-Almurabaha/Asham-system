<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Investors\Services\ZakatDueNotifier;

class SendZakatDueNotifications extends Command
{
    protected $signature = 'zakat:notify {--force : إعادة إرسال التنبيهات حتى لو تم الإبلاغ عن نفس الموعد سابقاً}';

    protected $description = 'إرسال تنبيه دوري عند حلول موعد زكاة أحد المستثمرين دون تسجيل سداد لها';

    public function handle(ZakatDueNotifier $notifier): int
    {
        $report = $notifier->execute(force: (bool) $this->option('force'));

        if ($report->investorsCount() === 0) {
            $this->info('لا يوجد مستثمرون بحاجة إلى تنبيه زكاة حالياً.');

            return self::SUCCESS;
        }

        if ($report->recipientCount() === 0) {
            $this->warn('تم رصد مستثمرين متأخرين لكن لم يتم إرسال تنبيهات لعدم وجود مستقبلين مُعدّين.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'تم إرسال تنبيهات زكاة لـ %d مستثمر/مستثمرة إلى %d مستلم/مستلمة.',
            $report->investorsCount(),
            $report->recipientCount()
        ));

        return self::SUCCESS;
    }
}
