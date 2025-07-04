<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            /*
             * populates permissions and roles tables, see spaties laravel-permissions docs
             * https://spatie.be/docs/laravel-permission/v6/introduction
             */
            RolesPermissionsSeeder::class
        ]);

        if (app()->isLocal()) {
            // Populates following tables legal_entities, users and model has roles with test data
            $this->call(TestUserMigrate::class);
        }
        $this->fixPostgresSequence('employees');
    }

    /**
     * Fixes the sequence for a given table in PostgreSQL.
     *
     * @param string $tableName
     * @return void
     */
    protected function fixPostgresSequence(string $tableName): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $sequenceName = $tableName . '_id_seq';
            DB::statement("SELECT setval('{$sequenceName}', (SELECT MAX(id) FROM {$tableName}))");
        }
    }
}
