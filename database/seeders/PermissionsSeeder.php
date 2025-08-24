<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web'; // عدّله لو عندك غارد مختلف

        // 1) أنشئ الصلاحيات (مثال – عدل القائمة حسب مشروعك)
        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete',
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, $guard);
        }

        $adminRole   = Role::findOrCreate('admin', $guard);
        $manager = Role::findOrCreate('manager', $guard);
        $entry   = Role::findOrCreate('data-entry', $guard);
        $viewer  = Role::findOrCreate('viewer', $guard);

        // $adminRole->syncPermissions(Permission::where('guard_name', $guard)->get());
        // $adminRole->givePermissionTo(Permission::where('guard_name', $guard)->get());
        // $adminRole->givePermissionTo(Permission::all());
        $adminRole->syncPermissions(Permission::all());

        $adminuser = User::firstOrCreate(
            [
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin@123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $testnuser = User::firstOrCreate(
            [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => bcrypt('test@123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$adminuser->hasRole('admin')) {
            $adminuser->assignRole($adminRole);
        }

        
    }
}
