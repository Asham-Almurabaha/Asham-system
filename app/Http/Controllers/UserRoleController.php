<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function index()
    {
        // هنعرض كل المستخدمين مع أدوارهم
        $users = User::with('roles')->orderBy('id','asc')->paginate(15);
        return view('users.index', compact('users'));
    }

    public function edit(User $user)
    {
        // كل الأدوار المتاحة
        $roles   = Role::orderBy('name')->pluck('name', 'name'); // ['admin'=>'admin', ...]
        $current = $user->roles->pluck('name')->toArray();

        return view('users.edit-roles', compact('user','roles','current'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'roles'   => ['array'],
            'roles.*' => ['string','exists:roles,name'],
        ]);

        // استبدال الأدوار الحالية بالجديدة
        $user->syncRoles($data['roles'] ?? []);

        return redirect()
            ->route('users.index')
            ->with('success', 'تم تحديث أدوار المستخدم بنجاح.');
    }
}
