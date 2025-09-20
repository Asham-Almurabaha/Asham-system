<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Concerns\FormatsPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    use FormatsPermissions;

    public function index(Request $request)
    {
        $guards = Role::query()
            ->select('guard_name')
            ->distinct()
            ->orderBy('guard_name')
            ->pluck('guard_name')
            ->filter(fn ($value) => $value !== null && $value !== '');

        $defaultGuard = (string) (config('auth.defaults.guard') ?? 'web');

        $selectedGuard = trim((string) $request->query('guard', ''));
        $showAllGuards = false;

        if ($selectedGuard === 'all') {
            $showAllGuards = true;
        } elseif ($selectedGuard === '') {
            if ($guards->isNotEmpty()) {
                $selectedGuard = $guards->contains($defaultGuard)
                    ? $defaultGuard
                    : (string) $guards->first();
            } else {
                $selectedGuard = $defaultGuard;
            }
        }

        $guardOptions = $guards
            ->map(fn ($guard) => (string) $guard)
            ->filter()
            ->values();

        if ($defaultGuard !== '' && !$guardOptions->contains($defaultGuard)) {
            $guardOptions->prepend($defaultGuard);
        }

        $guardOptions = $guardOptions->unique()->values();

        $rolesQuery = Role::query()
            ->with('permissions')
            ->orderBy('name');

        if (!$showAllGuards) {
            $rolesQuery->where('guard_name', $selectedGuard);
        }

        $roles = $rolesQuery->get();

        $permissionsQuery = Permission::query()
            ->orderBy('name');

        if (!$showAllGuards) {
            $permissionsQuery->where('guard_name', $selectedGuard);
        }

        $permissions = $permissionsQuery->get();

        $permissionLabels = $permissions->mapWithKeys(fn (Permission $permission) => [
            $permission->name => $this->formatPermissionLabel($permission->name),
        ]);

        $roleLabels = $roles->mapWithKeys(fn (Role $role) => [
            $role->name => $this->formatRoleLabel($role->name),
        ]);

        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->get();

        $totalUsers = $users->count();

        $roleUsers = $roles->mapWithKeys(function (Role $role) use ($users) {
            $assigned = $users
                ->filter(fn (User $user) => $user->roles->contains('name', $role->name))
                ->map(function (User $user) {
                    $display = trim((string) ($user->name ?? ''));

                    if ($display === '') {
                        $display = trim((string) ($user->email ?? ''));
                    }

                    if ($display === '') {
                        $display = __('roles.User Placeholder', ['id' => $user->getKey()]);
                    }

                    return [
                        'id' => $user->getKey(),
                        'display' => $display,
                        'email' => $user->email,
                    ];
                })
                ->sortBy('display', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return [$role->name => $assigned];
        });

        $rolesWithUsersCount = $roleUsers
            ->filter(fn ($assigned) => $assigned->count() > 0)
            ->count();

        $totalRoles = $roles->count();
        $rolesWithoutUsersCount = max(0, $totalRoles - $rolesWithUsersCount);

        $permissionCount = $permissions->count();

        $guardLabel = $showAllGuards
            ? __('permissions.All Guards Label')
            : ($selectedGuard !== '' ? Str::headline($selectedGuard) : $defaultGuard);

        return view('settings.roles.index', [
            'roles' => $roles,
            'roleLabels' => $roleLabels,
            'permissionLabels' => $permissionLabels,
            'guardOptions' => $guardOptions,
            'selectedGuard' => $selectedGuard,
            'showAllGuards' => $showAllGuards,
            'guardLabel' => $guardLabel,
            'defaultGuard' => $defaultGuard,
            'totalRoles' => $totalRoles,
            'permissionCount' => $permissionCount,
            'rolesWithUsersCount' => $rolesWithUsersCount,
            'rolesWithoutUsersCount' => $rolesWithoutUsersCount,
            'roleUsers' => $roleUsers,
            'totalUsers' => $totalUsers,
        ]);
    }

    public function store(Request $request)
    {
        $requestedGuard = trim((string) $request->input('guard_name', ''));
        $guard = $requestedGuard !== '' ? $requestedGuard : (string) (config('auth.defaults.guard') ?? 'web');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where(fn ($query) => $query->where('guard_name', $guard)),
            ],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'redirect_guard' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $guard,
        ]);

        $redirectGuard = $data['redirect_guard'] ?? $guard;

        return redirect()
            ->route('settings.roles.index', ['guard' => $redirectGuard])
            ->with('success', __('roles.role_created', [
                'role' => $this->formatRoleLabel($role->name),
            ]));
    }

    public function destroy(Request $request, Role $role)
    {
        $redirectGuard = $request->input('redirect_guard', $role->guard_name);

        $roleLabel = $this->formatRoleLabel($role->name);
        $role->delete();

        return redirect()
            ->route('settings.roles.index', ['guard' => $redirectGuard])
            ->with('success', __('roles.role_deleted', [
                'role' => $roleLabel,
            ]));
    }
}
