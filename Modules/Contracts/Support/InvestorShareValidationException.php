<?php

namespace Modules\Contracts\Support;

use RuntimeException;

final class InvestorShareValidationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $index = null,
        private readonly ?string $field = null
    ) {
        parent::__construct($message);
    }

    public function index(): ?int
    {
        return $this->index;
    }

    public function field(): ?string
    {
        return $this->field;
    }

    public static function percentageOutOfRange(int $index, ?string $field): self
    {
        return new self('نسبة المشاركة يجب أن تكون بين 0 و 100.', $index, $field);
    }

    public static function totalExceeds(float $sum): self
    {
        return new self(
            sprintf(
                'مجموع نسب المستثمرين لا يجوز أن يتجاوز 100%%. المجموع الحالي: %s%%',
                self::formatPercentage($sum)
            ),
            null,
            'investors'
        );
    }

    private static function formatPercentage(float $value): string
    {
        $formatted = number_format($value, 4, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
