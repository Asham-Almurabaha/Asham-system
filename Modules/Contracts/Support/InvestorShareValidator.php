<?php

namespace Modules\Contracts\Support;

final class InvestorShareValidator
{
    private const EPS = 0.0001;

    /**
     * @param array<int, array<string, mixed>> $investors
     */
    public function validate(array $investors): float
    {
        if ($investors === []) {
            return 0.0;
        }

        $sum = 0.0;

        foreach ($investors as $index => $investor) {
            [$percentage, $field] = $this->extractPercentage($investor);

            if ($percentage === null) {
                continue;
            }

            if ($percentage < 0 || $percentage > 100) {
                throw InvestorShareValidationException::percentageOutOfRange($index, $field);
            }

            $sum += $percentage;
        }

        if ($sum - 100 > self::EPS) {
            throw InvestorShareValidationException::totalExceeds($sum);
        }

        return round($sum, 4);
    }

    /**
     * @param array<string, mixed> $investor
     * @return array{0: float|null, 1: string|null}
     */
    private function extractPercentage(array $investor): array
    {
        $fields = ['share_percentage', 'pct', 'percentage'];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $investor)) {
                continue;
            }

            $value = $investor[$field];

            if ($value === null || $value === '') {
                return [0.0, $field];
            }

            if (is_numeric($value)) {
                return [(float) $value, $field];
            }
        }

        return [null, null];
    }
}
