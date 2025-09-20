<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Support\Concerns\FormatsPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionManagementController extends Controller
{
    use FormatsPermissions;

    public function index(Request $request)
    {
        $guards = Permission::query()
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

        $guardOptions = $guards->map(fn ($guard) => (string) $guard)->filter()->values();
        if ($defaultGuard !== '' && !$guardOptions->contains($defaultGuard)) {
            $guardOptions->prepend($defaultGuard);
        }
        $guardOptions = $guardOptions->unique()->values();

        $permissionsQuery = Permission::query()
            ->with('roles')
            ->orderBy('name');

        if (!$showAllGuards) {
            $permissionsQuery->where('guard_name', $selectedGuard);
        }

        $permissions = $permissionsQuery->get();

        $permissionGroups = $this->groupPermissions($permissions);
        $permissionLabels = $permissions
            ->mapWithKeys(fn (Permission $permission) => [
                $permission->name => $this->formatPermissionLabel($permission->name),
            ]);

        $roleLabels = Role::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Role $role) => [
                $role->name => $this->formatRoleLabel($role->name),
            ]);

        $totalPermissions = $permissions->count();
        $totalRoles = Role::count();
        $assignedPermissionCount = $permissions
            ->filter(fn (Permission $permission) => $permission->roles->isNotEmpty())
            ->count();
        $unassignedPermissionCount = max(0, $totalPermissions - $assignedPermissionCount);
        $groupCount = $permissionGroups->count();

        $guardLabel = $showAllGuards
            ? __('permissions.All Guards Label')
            : ($selectedGuard !== '' ? Str::headline($selectedGuard) : $defaultGuard);

        return view('settings.permissions.index', [
            'permissionGroups' => $permissionGroups,
            'permissionLabels' => $permissionLabels,
            'roleLabels' => $roleLabels,
            'guardOptions' => $guardOptions,
            'selectedGuard' => $selectedGuard,
            'showAllGuards' => $showAllGuards,
            'guardLabel' => $guardLabel,
            'defaultGuard' => $defaultGuard,
            'totalPermissions' => $totalPermissions,
            'totalRoles' => $totalRoles,
            'assignedPermissionCount' => $assignedPermissionCount,
            'unassignedPermissionCount' => $unassignedPermissionCount,
            'groupCount' => $groupCount,
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
                Rule::unique('permissions', 'name')->where(fn ($query) => $query->where('guard_name', $guard)),
            ],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'redirect_guard' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => $guard,
        ]);

        $redirectGuard = $data['redirect_guard'] ?? $guard;

        return redirect()
            ->route('settings.permissions.index', ['guard' => $redirectGuard])
            ->with('success', __('permissions.permission_created', [
                'permission' => $this->formatPermissionLabel($permission->name),
            ]));
    }

    public function destroy(Request $request, Permission $permission)
    {
        $redirectGuard = $request->input('redirect_guard', $permission->guard_name);

        $permissionLabel = $this->formatPermissionLabel($permission->name);
        $permission->delete();

        return redirect()
            ->route('settings.permissions.index', ['guard' => $redirectGuard])
            ->with('success', __('permissions.permission_deleted', [
                'permission' => $permissionLabel,
            ]));
    }
}
