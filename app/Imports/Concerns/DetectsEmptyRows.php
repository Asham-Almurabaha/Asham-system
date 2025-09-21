<?php

namespace App\Imports\Concerns;

trait DetectsEmptyRows
{
    protected function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->valueHasContent($value)) {
                return false;
            }
        }

        return true;
    }

    protected function valueHasContent(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $nested) {
                if ($this->valueHasContent($nested)) {
                    return true;
                }
            }

            return false;
        }

        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }
}
