<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRolesAndPermissions;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    use SeedsRolesAndPermissions;

    protected string $guard = 'web';

    public function run(): void
    {
        $this->seedRolesAndPermissions();

        // (اختياري) عيّن admin لأول مستخدم موجود
        $firstUserModel = config('auth.providers.users.model');
        /** @var \Illuminate\Database\Eloquent\Model|\Spatie\Permission\Traits\HasRoles|null $first */
        $first = $firstUserModel::query()->orderBy('id')->first();
        if ($first && method_exists($first, 'assignRole')) {
            $first->assignRole('admin');
        }
    }
}
