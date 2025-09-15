<?php

namespace Modules\Contracts\Support;

final class ContractStatusNames
{
    public const NO_INVESTORS = 'بدون مستثمر';
    public const PENDING      = 'معلق';
    public const NEW          = 'جديد';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::NO_INVESTORS,
            self::PENDING,
            self::NEW,
        ];
    }
}
