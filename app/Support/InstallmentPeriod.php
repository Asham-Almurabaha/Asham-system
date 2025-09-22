<?php

namespace App\Support;

use Carbon\Carbon;

class InstallmentPeriod
{
    /**
     * Resolve the custom installment period boundaries.
     *
     * The period always starts on the 24th of a month and ends on the 23rd of the following month.
     * If the month/year arguments are invalid or missing, the period containing the reference date is used.
     *
     * @param  int|null    $month     1..12 (optional)
     * @param  int|null    $year      four digit year (optional)
     * @param  Carbon|null $reference Reference point when month/year are not provided (default: now)
     * @return array{start: Carbon, end: Carbon}
     */
    public static function resolve(?int $month = null, ?int $year = null, ?Carbon $reference = null): array
    {
        $reference = $reference ? $reference->copy() : Carbon::now();
        $timezone  = $reference->getTimezone();

        $validMonth = $month !== null && $month >= 1 && $month <= 12;
        $validYear  = $year !== null && $year >= 1900 && $year <= 2100;

        if ($validMonth && $validYear) {
            $start = Carbon::create($year, $month, 24, 0, 0, 0, $timezone);
        } else {
            $target = $reference->copy();
            if ((int) $target->day >= 24) {
                $start = Carbon::create($target->year, $target->month, 24, 0, 0, 0, $timezone);
            } else {
                $target->subMonthNoOverflow();
                $start = Carbon::create($target->year, $target->month, 24, 0, 0, 0, $timezone);
            }
        }

        $start = $start->startOfDay();
        $end   = $start->copy()->addMonthNoOverflow()->setDay(23)->endOfDay();

        return ['start' => $start, 'end' => $end];
    }
}
