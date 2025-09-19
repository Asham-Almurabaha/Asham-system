<?php

namespace Modules\Investors\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class ZakatNotificationReport
{
    public function __construct(
        public Collection $entries,
        public Collection $userRecipients,
        public Collection $emailRecipients,
        public ?CarbonInterface $dispatchedAt,
    ) {}

    public function investorsCount(): int
    {
        return $this->entries->count();
    }

    public function recipientCount(): int
    {
        return $this->userRecipients->count() + $this->emailRecipients->count();
    }

    public function notificationsDispatched(): bool
    {
        return $this->dispatchedAt !== null;
    }
}
