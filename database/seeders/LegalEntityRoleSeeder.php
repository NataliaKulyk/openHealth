<?php

declare(strict_types=1);

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class LegalEntityRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $now = now();

        $rolesToInsert = [];

        // Get all specified guards from section 'guards' from file config/auth.php (except sanctum)
        $guards = collect(array_keys((array) config('auth.guards')))
            ->reject(fn ($guard) => $guard === 'sanctum')
            ->values();

        $roleList = array_keys(config('ehealth.roles'));

        // Prepare Role's and Permission's data to insert into DB
        foreach ($guards as $guard) {
            foreach ($roleList as $roleName) {
                $rolesToInsert[] = [
                    'name' => $roleName,
                    'guard_name' => $guard,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        }

        try {
            Role::insert($rolesToInsert);
        } catch (Exception $err) {
            $this->command->error('ERROR: ' . $err->getMessage());
        }

        $this->command->info("\tINFO: Legal entity roles have been successfully inserted into the database\n");
    }
}
