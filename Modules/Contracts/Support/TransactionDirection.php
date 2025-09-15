<?php

namespace Modules\Contracts\Support;

final class TransactionDirection
{
    public static function directionFromTypeName(?string $typeName): ?string
    {
        if ($typeName === null) {
            return null;
        }

        $name = self::arNormalize($typeName);
        if ($name === '') {
            return null;
        }

        $exact = [
            'ايداع'   => 'in',
            'إيداع'   => 'in',
            'توريد'   => 'in',
            'تحصيل'  => 'in',
            'سحب'     => 'out',
            'صرف'     => 'out',
            'توزيع'   => 'out',
            'استرداد' => 'out',
            'deposit' => 'in',
            'withdraw' => 'out',
        ];

        if (isset($exact[$typeName])) {
            return $exact[$typeName];
        }

        if (isset($exact[$name])) {
            return $exact[$name];
        }

        if (mb_strpos($name, 'ايداع') !== false
            || mb_strpos($name, 'توريد') !== false
            || mb_strpos($name, 'تحصيل') !== false
        ) {
            return 'in';
        }

        if (mb_strpos($name, 'سحب') !== false
            || mb_strpos($name, 'صرف') !== false
            || mb_strpos($name, 'توزيع') !== false
            || mb_strpos($name, 'استرداد') !== false
        ) {
            return 'out';
        }

        if (mb_strpos($name, 'deposit') !== false) {
            return 'in';
        }

        if (mb_strpos($name, 'withdraw') !== false) {
            return 'out';
        }

        return null;
    }

    public static function arNormalize(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = mb_strtolower($text, 'UTF-8');

        $map = [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ة' => 'ه',
            'ى' => 'ي',
            'ؤ' => 'و',
            'ئ' => 'ي',
        ];

        return strtr($text, $map);
    }
}
