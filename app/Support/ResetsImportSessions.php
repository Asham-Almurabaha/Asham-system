<?php

namespace App\Support;

use Illuminate\Http\Request;

trait ResetsImportSessions
{
    /**
     * Clear import session data unless we just finished an operation that should keep it.
     */
    protected function resetImportSession(
        string $namespace,
        array $keys,
        Request $request,
        array $keepFlags = []
    ): void {
        $keep = $request->boolean('keep', false);

        foreach ($keepFlags as $flag) {
            if (session()->has($flag)) {
                $keep = true;
                break;
            }
        }

        if ($keep) {
            return;
        }

        $namespacedKeys = array_map(
            fn ($key) => str_contains($key, '.') ? $key : "{$namespace}.{$key}",
            $keys
        );

        session()->forget($namespacedKeys);
    }
}
