<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::query()->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('name')->get();

        $selectedRole = null;
        $selectedRoleName = (string) $request->string('role');
        if ($selectedRoleName !== '') {
            $selectedRole = $roles->firstWhere('name', $selectedRoleName);
        }
        if (!$selectedRole && $roles->isNotEmpty()) {
            $selectedRole = $roles->first();
        }

        $permissionGroups = $this->groupPermissions($permissions);
        $permissionLabels = $permissions
            ->mapWithKeys(fn(Permission $permission) => [
                $permission->name => $this->formatPermissionLabel($permission->name),
            ]);

        $selectedPermissions = $selectedRole
            ? $selectedRole->permissions->pluck('name')->all()
            : [];

        $users = User::query()->with('roles')->orderBy('name')->get();
        $totalUsers = $users->count();
        $usersWithRoles = $users->filter(fn(User $user) => $user->roles->isNotEmpty())->count();
        $usersWithoutRoles = $totalUsers - $usersWithRoles;

        $usersAssignedToSelectedRole = 0;
        $roleUsagePercentage = null;

        if ($selectedRole) {
            $usersAssignedToSelectedRole = $users
                ->filter(fn(User $user) => $user->roles->contains('name', $selectedRole->name))
                ->count();

            if ($totalUsers > 0) {
                $roleUsagePercentage = (int) round(($usersAssignedToSelectedRole / $totalUsers) * 100);
            }
        }

        $selectedRolePermissionCount = count($selectedPermissions);

        return view('settings.roles-permissions', [
            'roles' => $roles,
            'permissionGroups' => $permissionGroups,
            'permissionLabels' => $permissionLabels,
            'selectedRole' => $selectedRole,
            'selectedPermissions' => $selectedPermissions,
            'users' => $users,
            'totalUsers' => $totalUsers,
            'usersWithRoles' => $usersWithRoles,
            'usersWithoutRoles' => $usersWithoutRoles,
            'totalRoles' => $roles->count(),
            'permissionCount' => $permissions->count(),
            'roleLabels' => $roles->mapWithKeys(fn(Role $role) => [
                $role->name => $this->formatRoleLabel($role->name),
            ]),
            'selectedRoleLabel' => $selectedRole ? $this->formatRoleLabel($selectedRole->name) : null,
            'selectedRolePermissionCount' => $selectedRolePermissionCount,
            'usersAssignedToSelectedRole' => $usersAssignedToSelectedRole,
            'roleUsagePercentage' => $roleUsagePercentage,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()
            ->route('settings.roles.permissions', ['role' => $role->name])
            ->with('success', __('permissions.role_permissions_updated', [
                'role' => $this->formatRoleLabel($role->name),
            ]));
    }

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
