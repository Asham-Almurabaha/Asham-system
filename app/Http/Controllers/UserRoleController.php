<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim();

        $userQuery = User::query()
            ->with('roles')
            ->orderBy('name')
            ->when($search->isNotEmpty(), function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $value = '%' . $search->value() . '%';

                    $inner->where('name', 'like', $value)
                        ->orWhere('email', 'like', $value);
                });
            });

        // هنعرض كل المستخدمين مع أدوارهم
        $users = $userQuery->paginate(15)->withQueryString();

        $totalUsers        = User::count();
        $usersWithRoles    = User::whereHas('roles')->count();
        $usersWithoutRoles = User::whereDoesntHave('roles')->count();

        return view('users.index', [
            'users'              => $users,
            'totalUsers'         => $totalUsers,
            'usersWithRoles'     => $usersWithRoles,
            'usersWithoutRoles'  => $usersWithoutRoles,
            'searchTerm'         => $search->value(),
            'hasSearch'          => $search->isNotEmpty(),
            'filteredCount'      => $users->total(),
        ]);
    }

    public function edit(User $user)
    {
        // كل الأدوار المتاحة
        $roles                = Role::orderBy('name')->pluck('name', 'name'); // ['admin'=>'admin', ...]
        $current              = $user->roles->pluck('name')->toArray();
        $permissions          = Permission::orderBy('name')->get();
        $directPermissions    = $user->getDirectPermissions()->pluck('name')->sort()->values()->toArray();
        $inheritedPermissions = $user->getPermissionsViaRoles()->pluck('name')->unique()->sort()->values()->toArray();

        return view('users.edit-roles', compact(
            'user',
            'roles',
            'current',
            'permissions',
            'directPermissions',
            'inheritedPermissions'
        ));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'roles'        => ['array'],
            'roles.*'      => ['string','exists:roles,name'],
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // استبدال الأدوار الحالية بالجديدة
        $user->syncRoles($data['roles'] ?? []);
        $user->syncPermissions($data['permissions'] ?? []);

        return redirect()
            ->route('users.index')
            ->with('success', 'تم تحديث أدوار المستخدم بنجاح.');
    }
}
