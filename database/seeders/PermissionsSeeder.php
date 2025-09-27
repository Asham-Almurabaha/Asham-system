<?php
namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\SeedsRolesAndPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    use SeedsRolesAndPermissions;

    protected string $guard = 'web';

    public function run(): void
    {
        $this->seedRolesAndPermissions();

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name'              => 'Admin',
                'password'          => Hash::make('admin@123'),
                'email_verified_at' => now(),
            ]
        );

        $testUser = User::firstOrCreate(
            ['email' => 'test@test.com'],
            [
                'name'              => 'Test',
                'password'          => Hash::make('test@123'),
                'email_verified_at' => now(),
            ]
        );

        $adminRole = Role::where('name', 'admin')
            ->where('guard_name', $this->guardName())
            ->first();

        if ($adminRole && !$adminUser->hasRole($adminRole)) {
            $adminUser->assignRole($adminRole);
        }

        $viewerRole = Role::where('name', 'viewer')
            ->where('guard_name', $this->guardName())
            ->first();

        if ($viewerRole && !$testUser->hasRole($viewerRole)) {
            $testUser->assignRole($viewerRole);
        }
    }
}
