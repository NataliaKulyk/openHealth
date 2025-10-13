<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypePermissionsSeeder extends Seeder
{
    /**
     * Seed the legal_entity_type_permissions pivot from config('ehealth.legal_entity_types').
     *
     * - Maps each type name to its ID in legal_entity_types
     * - Maps each permission name to all matching permission IDs (across guards)
     * - Inserts unique pairs (permission_id, legal_entity_type_id)
     */
    public function run(): void
    {
        $typesData= config('ehealth.legal_entity_types', []);

        // Fetch type ids keyed by name
        $typeIds = DB::table('legal_entity_types')->pluck('id', 'name');

        // Map permission name => [permission_id => [...guards]] (include all guards)
        $permMap = DB::table('permissions')
            ->select('id', 'name')
            ->get()
            ->groupBy('name')
            ->map(fn ($dataToInsert) => $dataToInsert->pluck('id')->all());

        $dataToInsert = [];

        foreach ($typesData as $typeName => $permNames) {

            $typeId = (int) $typeIds[$typeName];

            foreach ($permNames as $name) {

                foreach ($permMap[$name] as $pid) {
                    $dataToInsert[] = [
                        'permission_id' => (int) $pid,
                        'legal_entity_type_id' => $typeId,
                    ];
                }
            }
        }

        // Insert in chunks using insertOrIgnore for idempotency
        foreach (array_chunk($dataToInsert, 1000) as $chunk) {
            DB::table('legal_entity_type_permissions')->insertOrIgnore($chunk);
        }

        $this->command?->info("\tINFO: legal_entity_type_permissions seeded\n");
    }
}
