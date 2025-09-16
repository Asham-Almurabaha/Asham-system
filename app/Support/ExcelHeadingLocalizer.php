<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class ExcelHeadingLocalizer
{
    /**
     * Convert a collection of heading keys into localized labels.
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    public static function translateMany(array $keys): array
    {
        return array_map([self::class, 'translate'], $keys);
    }

    /**
     * Translate a single heading key.
     */
    public static function translate(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            return '';
        }

        if (preg_match('/^investor(\d+)_(id|name|pct)$/', $key, $matches)) {
            return trans('export.headings.investor_' . $matches[2] . '_indexed', [
                'number' => $matches[1],
            ]);
        }

        if (preg_match('/^payment(\d+)_(amount|date|notes)$/', $key, $matches)) {
            return trans('export.headings.payment_' . $matches[2] . '_indexed', [
                'number' => $matches[1],
            ]);
        }

        $translationKey = 'export.headings.' . $key;

        if (Lang::has($translationKey)) {
            return trans($translationKey);
        }

        return (string) Str::of($key)
            ->replace('_', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->title();
    }

    /**
     * Translate sheet titles used in multi-sheet exports.
     */
    public static function translateTitle(string $title): string
    {
        $title = trim(Str::snake($title));

        if ($title === '') {
            return '';
        }

        $translationKey = 'export.sheets.' . $title;

        if (Lang::has($translationKey)) {
            return trans($translationKey);
        }

        return (string) Str::of($title)
            ->replace('_', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->title();
    }
}

