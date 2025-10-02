<?php

namespace App\Support\Sidebar;

class SidebarPermissionRegistry
{
    /**
     * Get the sidebar permission groups defined in configuration.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function groups(): array
    {
        $config = config('sidebar-permissions.groups', []);

        return array_map(function (array $group): array {
            $group['items'] = array_map(function (array $item): array {
                $item['permission'] = self::normalizePermission($item['permission'] ?? null);
                $item['additional_permissions'] = self::normalizePermissionList($item['additional_permissions'] ?? []);
                $item['label'] = $item['label'] ?? ($item['key'] ?? '');

                return $item;
            }, $group['items'] ?? []);

            $group['label'] = $group['label'] ?? ($group['key'] ?? '');
            $group['additional_permissions'] = self::normalizePermissionList($group['additional_permissions'] ?? []);

            return $group;
        }, $config);
    }

    /**
     * Retrieve all unique permission names referenced by the sidebar configuration.
     *
     * @return array<int, string>
     */
    public static function allPermissionNames(): array
    {
        $names = [];

        foreach (self::groups() as $group) {
            $names = array_merge($names, $group['additional_permissions']);

            foreach ($group['items'] as $item) {
                if (!empty($item['permission'])) {
                    $names[] = $item['permission'];
                }

                $names = array_merge($names, $item['additional_permissions']);
            }
        }

        $names = array_filter(array_map('strval', $names), static function ($name) {
            return $name !== '';
        });

        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }

    /**
     * Retrieve only the primary permission names associated with sidebar links.
     *
     * @return array<int, string>
     */
    public static function primaryPermissionNames(): array
    {
        $names = [];

        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                if (!empty($item['permission'])) {
                    $names[] = $item['permission'];
                }
            }
        }

        $names = array_filter(array_map('strval', $names), static function ($name) {
            return $name !== '';
        });

        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }

    /**
     * @param  string|array<int, string>|null  $permissions
     * @return array<int, string>
     */
    protected static function normalizePermissionList($permissions): array
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        if (!is_array($permissions)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($permission) {
            $permission = is_string($permission) ? trim($permission) : '';

            return $permission !== '' ? $permission : null;
        }, $permissions)));
    }

    protected static function normalizePermission($permission): ?string
    {
        if (!is_string($permission)) {
            return null;
        }

        $permission = trim($permission);

        return $permission !== '' ? $permission : null;
    }
}
