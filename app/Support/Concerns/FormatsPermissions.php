<?php

namespace App\Support\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

trait FormatsPermissions
{
    protected function groupPermissions(Collection $permissions): Collection
    {
        return $permissions
            ->groupBy(function (Permission $permission) {
                $name = $permission->name;

                if (str_contains($name, '.')) {
                    return Str::before($name, '.');
                }

                if (str_contains($name, '-')) {
                    return Str::before($name, '-');
                }

                return 'general';
            })
            ->sortKeys()
            ->map(function (Collection $group, string $key) {
                return [
                    'label' => $this->formatGroupLabel($key),
                    'permissions' => $group->sortBy('name')->values(),
                ];
            })
            ->values();
    }

    protected function formatGroupLabel(string $key): string
    {
        if ($key === 'general') {
            return __('permissions.General');
        }

        $normalized = str_replace(['_', '-'], ' ', $key);

        return Str::headline($normalized);
    }

    protected function formatPermissionLabel(string $permission): string
    {
        $normalized = str_replace(['.', '-', '_'], ' ', $permission);

        return Str::headline($normalized);
    }

    protected function formatRoleLabel(string $role): string
    {
        $normalized = str_replace(['_', '-'], ' ', $role);

        return Str::headline($normalized);
    }
}
