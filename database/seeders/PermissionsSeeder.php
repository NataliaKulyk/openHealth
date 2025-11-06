<?php

declare(strict_types=1);

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure that no cached permission state during seeding
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Collect all types permissions from config source
        $typePermissions = collect((array) config('ehealth.legal_entity_types'))
            ->flatMap(fn ($arr) => (array) $arr);

        // Collect all roles permissions from config source
        $rolePermissions = collect((array) config('ehealth.roles'))
            ->flatMap(fn ($arr) => (array) $arr);

        // Combine and deduplicate permission names
        $allNames = $typePermissions
            ->merge($rolePermissions)
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => trim($v))
            ->unique()
            ->values();

        // Get available guards except sanctum
        $guards = collect(array_keys((array) config('auth.guards')))
            ->reject(fn ($g) => $g === 'sanctum')
            ->values();

        $now = now();
        $dataToInsert = [];

        foreach ($guards as $guard) {
            foreach ($allNames as $name) {
                $dataToInsert[] = [
                    'name' => $name,
                    'guard_name' => $guard,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Insert data into table by chunks or ignore duplicates
        try {
            foreach (array_chunk($dataToInsert, 1000) as $chunk) {
                DB::table(config('permission.table_names.permissions'))
                    ->insertOrIgnore($chunk);
            }
        } catch (Exception $e) {
            $this->command?->error('ERROR: ' . $e->getMessage());
        }

        // Clear cached permissions again
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info("\tINFO: permissions table seeded for guards: " . $guards->implode(', ') . "\n");
    }
}
