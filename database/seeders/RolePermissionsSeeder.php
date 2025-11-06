<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionsSeeder extends Seeder
{
    protected const int CHUNK_SIZE = 1000;

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Get all guards except sanctum
        $guards = collect(array_keys((array) config('auth.guards')))
            ->reject(fn ($g) => $g === 'sanctum')
            ->values();

        // Get roles from config
        $rolesData = (array) config('ehealth.roles');

        // Extract names of roles
        $roleNames = array_keys($rolesData);

        foreach ($guards as $guard) {
            // Prepare permission map for this guard: permission name -> id
            $permMap = Permission::where('guard_name', $guard)
                ->get(['id', 'name'])
                ->keyBy('name')
                ->map(fn ($p) => (int) $p->id);

            // Get roles existing for this guard: role name -> id
            $roleMap = Role::whereIn('name', $roleNames)
                ->where('guard_name', $guard)
                ->get(['id', 'name'])
                ->keyBy('name')
                ->map(fn ($r) => (int) $r->id);

            $dataToInsert = [];

            foreach ($rolesData as $roleName => $roleScopes) {
                $roleId = $roleMap[$roleName] ?? null;

                if (!$roleId) {
                    // If role not present for this guard
                    continue;
                }

                // Get scopes from config
                $roleScopes = collect((array) $roleScopes)
                    ->filter(fn ($v) => is_string($v) && $v !== '');

                // Get permission IDs for this role's scopes
                $permIds = $roleScopes
                    ->unique()
                    ->map(fn ($name) => $permMap[$name] ?? null) // Here should be array of IDs
                    ->filter()
                    ->values();

                foreach ($permIds as $pid) {
                    $dataToInsert[] = [
                        'role_id' => $roleId,
                        'permission_id' => (int) $pid,
                    ];
                }
            }

            // Insert data into table by chunks for this guard
            if (!empty($dataToInsert)) {
                foreach (array_chunk($dataToInsert, self::CHUNK_SIZE) as $chunk) {
                    DB::table(config('permission.table_names.role_has_permissions'))
                        ->insertOrIgnore($chunk);
                }
            }
        }

        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info("\tINFO: role_has_permissions seeded (base role scopes; per-type filtering happens via legal_entity_type_permissions).\n");
    }
}
