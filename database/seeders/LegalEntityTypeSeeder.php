<?php

declare(strict_types=1);

namespace Database\Seeders;

use Exception;
use App\Models\LegalEntity;
use App\Models\LegalEntityType;
use Illuminate\Database\Seeder;

class LegalEntityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $now = now();

        $availableTypes = [
            ['name' => LegalEntity::TYPE_EMERGENCY, 'localized_name' => 'legal-entity.types.emergency'],
            ['name' => LegalEntity::TYPE_MIS, 'localized_name' => 'legal-entity.types.mis'],
            ['name' => LegalEntity::TYPE_MSP, 'localized_name' => 'legal-entity.types.msp'],
            ['name' => LegalEntity::TYPE_MSP_PHARMACY, 'localized_name' => 'legal-entity.types.msp_pharmacy'],
            ['name' => LegalEntity::TYPE_NHS, 'localized_name' => 'legal-entity.types.nhs'],
            ['name' => LegalEntity::TYPE_OUTPATIENT, 'localized_name' => 'legal-entity.types.outpatient'],
            ['name' => LegalEntity::TYPE_PHARMACY, 'localized_name' => 'legal-entity.types.pharmacy'],
            ['name' => LegalEntity::TYPE_PRIMARY_CARE, 'localized_name' => 'legal-entity.types.primary_care'],
            ['name' => LegalEntity::TYPE_MSP_LIMITED, 'localized_name' => 'legal-entity.types.msp_limited']
        ];

        foreach ($availableTypes as $typeRecord) {
            $typeRecord['created_at'] = $now;
            $typeRecord['updated_at'] = $now;
        }

        try {
            LegalEntityType::insert($availableTypes);
        } catch (Exception $err) {
            $this->command->error('ERROR: ' . $err->getMessage());
        }

        $this->command->info("\tINFO: Legal entity types have been successfully inserted into the database\n");
    }
}
