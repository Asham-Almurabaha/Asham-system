<?php

namespace Tests\Unit\Expenses;

use PHPUnit\Framework\TestCase;

class ExpensesTranslationsTest extends TestCase
{
    public function test_module_translation_files_have_matching_keys(): void
    {
        $basePath = dirname(__DIR__, 3);

        $english = require $basePath.'/Modules/Expenses/Resources/lang/en/expenses.php';
        $arabic = require $basePath.'/Modules/Expenses/Resources/lang/ar/expenses.php';

        $englishKeys = $this->collectTranslationKeys($english);
        $arabicKeys = $this->collectTranslationKeys($arabic);

        $this->assertSame(
            $englishKeys,
            $arabicKeys,
            'The English and Arabic translation files must share the same structure.'
        );

        $this->assertTranslationValuesAreNonEmptyStrings($english, 'en');
        $this->assertTranslationValuesAreNonEmptyStrings($arabic, 'ar');
    }

    public function test_sidebar_translations_include_expenses_entry(): void
    {
        $basePath = dirname(__DIR__, 3);

        foreach (['en', 'ar'] as $locale) {
            $sidebar = require $basePath."/resources/lang/{$locale}/sidebar.php";

            $this->assertArrayHasKey(
                'Expenses',
                $sidebar,
                sprintf('The sidebar translation must contain an "Expenses" entry for the [%s] locale.', $locale)
            );

            $value = $sidebar['Expenses'];

            $this->assertIsString($value, sprintf('The "Expenses" sidebar label must be a string for the [%s] locale.', $locale));
            $this->assertNotSame(
                '',
                trim($value),
                sprintf('The "Expenses" sidebar label must not be empty for the [%s] locale.', $locale)
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function collectTranslationKeys(array $translations, string $prefix = ''): array
    {
        $keys = [];

        foreach ($translations as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->collectTranslationKeys($value, $fullKey));
            } else {
                $keys[] = $fullKey;
            }
        }

        sort($keys);

        return $keys;
    }

    private function assertTranslationValuesAreNonEmptyStrings(array $translations, string $locale, string $prefix = ''): void
    {
        foreach ($translations as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $this->assertTranslationValuesAreNonEmptyStrings($value, $locale, $fullKey);

                continue;
            }

            $this->assertIsString(
                $value,
                sprintf('The translation [%s] for the [%s] locale must be a string.', $fullKey, $locale)
            );

            $this->assertNotSame(
                '',
                trim($value),
                sprintf('The translation [%s] for the [%s] locale must not be empty.', $fullKey, $locale)
            );
        }
    }
}
