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

        $adminUser = $this->firstOrCreateUser('admin@admin.com', 'Admin', 'admin@123');
        $testUser  = $this->firstOrCreateUser('test@test.com', 'Test', 'test@123');

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

    private function firstOrCreateUser(string $email, string $name, string $password): User
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            return $user;
        }

        return User::create([
            'name'              => $name,
            'email'             => $email,
            'password'          => Hash::make($password),
            'email_verified_at' => now(),
        ]);
    }
}
