<?php

declare(strict_types=1);

namespace Database\Seeders;

use Exception;
use App\Models\LegalEntityType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class LegalEntityTypeAndRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $now = now();

        $roleAndTypePairs = [];

        $availableRoles = Role::get(['id', 'name'])
                ->groupBy('name')
                ->map(fn ($group) => $group->pluck('id')->values()->all())
                ->toArray();

        $roleNames = array_keys($availableRoles);

        $legalEntityTypes = LegalEntityType::all()->keyBy('name')->toArray();

        foreach (config('ehealth.legal_entity_employee_types') as $legalEntityType => $roles) {
            foreach ($roles as $role) {
                if (in_array($role, $roleNames, true)) {
                    $legalEtntityTypeId = $legalEntityTypes[$legalEntityType]['id'];

                    foreach ($availableRoles[$role] as $roleId) {
                        $roleAndTypePairs[] = [
                            'legal_entity_type_id' => $legalEtntityTypeId,
                            'role_id' => $roleId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                } else {
                    $this->command->error("ERROR: Role '{$role}' not found in the configuration.");
                }
            }
        }

        try {
            DB::table('legal_entity_type_roles')->insert($roleAndTypePairs);
        } catch (Exception $err) {
            $this->command->error('ERROR: ' . $err->getMessage());
        }

        $this->command->info("\tINFO: dependencies between Legal entity types and roles have been successfully inserted into the database\n");
    }
}
