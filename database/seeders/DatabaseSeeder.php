<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Database\Seeders\AccountsDatabaseSeeder;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            AccountsDatabaseSeeder::class,
            LookupsDatabaseSeeder::class,
            RolesAndPermissionsSeeder::class,
            PermissionsSeeder::class,
        ]);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'              => 'Test User',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

    }
}
