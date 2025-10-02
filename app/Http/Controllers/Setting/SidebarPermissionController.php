<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Support\Concerns\FormatsPermissions;
use App\Support\Sidebar\SidebarPermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SidebarPermissionController extends Controller
{
    use FormatsPermissions;

    public function index(Request $request)
    {
        $guard = (string) (config('auth.defaults.guard') ?? 'web');

        $groups = SidebarPermissionRegistry::groups();
        $primaryPermissionNames = SidebarPermissionRegistry::primaryPermissionNames();
        $allPermissionNames = SidebarPermissionRegistry::allPermissionNames();

        $permissions = Permission::query()
            ->whereIn('name', $allPermissionNames)
            ->where('guard_name', $guard)
            ->with('roles')
            ->get()
            ->keyBy('name');

        $roles = Role::query()
            ->where('guard_name', $guard)
            ->orderBy('name')
            ->get();

        $roleLabels = $roles->mapWithKeys(function (Role $role) {
            return [$role->name => $this->formatRoleLabel($role->name)];
        });

        $missingPermissions = array_values(array_diff($allPermissionNames, $permissions->keys()->all()));
        $totalSidebarLinks = count($primaryPermissionNames);
        $assignedCount = $permissions
            ->only($primaryPermissionNames)
            ->filter()
            ->sum(fn (Permission $permission) => $permission->roles->count());

        $groupData = array_map(function (array $group) use ($permissions, $roleLabels, $roles): array {
            $items = array_map(function (array $item) use ($permissions, $roleLabels): array {
                $permissionName = $item['permission'];
                $permission = $permissionName ? $permissions->get($permissionName) : null;
                $assignedRoles = $permission ? $permission->roles->pluck('name')->all() : [];

                return [
                    'key' => $item['key'] ?? $permissionName,
                    'label' => $item['label'] ?? $permissionName,
                    'permission' => $permissionName,
                    'additional_permissions' => $item['additional_permissions'],
                    'assigned_roles' => $assignedRoles,
                    'missing' => $permission === null && $permissionName !== null,
                    'role_labels' => $roleLabels,
                ];
            }, $group['items']);

            return [
                'key' => $group['key'] ?? Str::slug($group['label'] ?? ''),
                'label' => $group['label'] ?? '',
                'items' => $items,
            ];
        }, $groups);

        return view('settings.sidebar-permissions.index', [
            'groups' => $groupData,
            'roles' => $roles,
            'roleLabels' => $roleLabels,
            'guard' => $guard,
            'missingPermissions' => $missingPermissions,
            'totalSidebarLinks' => $totalSidebarLinks,
            'assignedCount' => $assignedCount,
        ]);
    }

    public function update(Request $request)
    {
        $guard = (string) (config('auth.defaults.guard') ?? 'web');

        $roles = Role::query()
            ->where('guard_name', $guard)
            ->pluck('name')
            ->all();

        $primaryPermissionNames = SidebarPermissionRegistry::primaryPermissionNames();

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['string', Rule::in($roles)],
        ]);

        $submitted = collect($data['permissions'] ?? [])
            ->map(fn ($value) => is_array($value) ? $value : [])
            ->map(fn ($roleNames) => array_values(array_intersect($roleNames, $roles)));

        $permissions = Permission::query()
            ->whereIn('name', $primaryPermissionNames)
            ->where('guard_name', $guard)
            ->get()
            ->keyBy('name');

        foreach ($primaryPermissionNames as $permissionName) {
            $permission = $permissions->get($permissionName);

            if (!$permission) {
                continue;
            }

            $roleNames = $submitted->get($permissionName, []);

            $permission->syncRoles($roleNames);
        }

        return redirect()
            ->route('settings.sidebar-permissions.index')
            ->with('success', __('sidebar-permissions.updated'));
    }
}
