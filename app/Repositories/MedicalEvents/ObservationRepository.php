<?php

declare(strict_types=1);

namespace App\Repositories\MedicalEvents;

use App\Models\MedicalEvents\Sql\ObservationComponent;
use App\Models\MedicalEvents\Sql\Quantity;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ObservationRepository extends BaseRepository
{
    /**
     * Store observation in DB.
     *
     * @param  array  $data
     * @param  int  $encounterId
     * @return void
     * @throws Throwable
     */
    public function store(array $data, int $encounterId): void
    {
        DB::transaction(function () use ($data, $encounterId) {
            try {
                foreach ($data as $datum) {
                    $code = Repository::codeableConcept()->store($datum['code']);

                    if (isset($datum['performer'])) {
                        $performer = Repository::identifier()->store($datum['performer']['identifier']['value']);
                        Repository::codeableConcept()->attach($performer, $datum['performer']);
                    }

                    if (isset($datum['reportOrigin'])) {
                        $reportOrigin = Repository::codeableConcept()->store($datum['reportOrigin']);
                    }

                    if (isset($datum['interpretation'])) {
                        $interpretation = Repository::codeableConcept()->store($datum['interpretation']);
                    }

                    if (isset($datum['bodySite'])) {
                        $bodySite = Repository::codeableConcept()->store($datum['bodySite']);
                    }

                    if (isset($datum['method'])) {
                        $method = Repository::codeableConcept()->store($datum['method']);
                    }

                    if (isset($datum['valueQuantity'])) {
                        $valueQuantity = Quantity::create([
                            'value' => $datum['valueQuantity']['value'],
                            'comparator' => $datum['valueQuantity']['comparator'] ?? null,
                            'unit' => $datum['valueQuantity']['unit'] ?? null,
                            'system' => $datum['valueQuantity']['system'] ?? null,
                            'code' => $datum['valueQuantity']['code'] ?? null
                        ]);
                    }

                    if (isset($datum['valueCodeableConcept'])) {
                        $valueCodeableConcept = Repository::codeableConcept()->store($datum['valueCodeableConcept']);
                    }

                    if (isset($datum['context'])) {
                        $context = Repository::identifier()->store($datum['context']['identifier']['value']);
                        Repository::codeableConcept()->attach($context, $datum['context']);
                    }

                    $observation = $this->model::create([
                        'uuid' => $datum['uuid'] ?? $datum['id'],
                        'encounter_id' => $encounterId,
                        'status' => $datum['status'],
                        'code_id' => $code->id,
                        'effective_date_time' => $datum['effectiveDateTime'] ?? null,
                        'issued' => $datum['issued'],
                        'primary_source' => $datum['primarySource'],
                        'performer_id' => $performer->id ?? null,
                        'report_origin_id' => $reportOrigin->id ?? null,
                        'interpretation_id' => $interpretation->id ?? null,
                        'comment' => $datum['comment'] ?? null,
                        'body_site_id' => $bodySite->id ?? null,
                        'method_id' => $method->id ?? null,
                        'value_quantity_id' => $valueQuantity->id ?? null,
                        'value_codeable_concept_id' => $valueCodeableConcept->id ?? null,
                        'value_string' => $datum['valueString'] ?? null,
                        'value_boolean' => $datum['valueBoolean'] ?? null,
                        'value_date_time' => $datum['valueDateTime'] ?? null,
                        'context_id' => $context->id ?? null
                    ]);

                    $categoriesIds = [];

                    foreach ($datum['categories'] as $categoryData) {
                        $category = Repository::codeableConcept()->store($categoryData);

                        $categoriesIds[] = $category->id;
                    }

                    $observation->categories()->attach($categoriesIds);

                    if (isset($datum['components'])) {
                        foreach ($datum['components'] as $componentData) {
                            $valueCodeableConcept = Repository::codeableConcept()->store($componentData['valueCodeableConcept']);
                            $interpretation = Repository::codeableConcept()->store($componentData['interpretation']);

                            ObservationComponent::create([
                                'observation_id' => $observation->id,
                                'codeable_concept_id' => $valueCodeableConcept->id,
                                'interpretation_id' => $interpretation->id
                            ]);
                        }
                    }
                }
            } catch (Exception $e) {
                Log::channel('db_errors')->error('Error saving observation', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Get observation data that is related to the encounter.
     *
     * @param  int  $encounterId
     * @return array|null
     */
    public function get(int $encounterId): ?array
    {
        return $this->model::with([
            'categories.coding',
            'code.coding',
            'performer.type.coding',
            'reportOrigin.coding',
            'interpretation.coding',
            'bodySite.coding',
            'method.coding',
            'valueQuantity',
            'valueCodeableConcept.coding',
            'reactionOn.type.coding',
            'components.valueCodeableConcept.coding',
            'components.interpretation.coding'
        ])
            ->where('encounter_id', $encounterId)
            ->get()
            ?->toArray();
    }

    /**
     * Formatting observations to show on the frontend.
     *
     * @param  array  $observations
     * @return array
     */
    public function formatForView(array $observations): array
    {
        return array_map(static function (array $observation) {
            $observation['issuedDate'] = CarbonImmutable::parse($observation['issued'])->format('Y-m-d');
            $observation['issuedTime'] = CarbonImmutable::parse($observation['issued'])->format('H:i');
            $observation['effectiveDate'] = CarbonImmutable::parse($observation['effectiveDateTime'])->format('Y-m-d');
            $observation['effectiveTime'] = CarbonImmutable::parse($observation['effectiveDateTime'])->format('H:i');

            unset($observation['issued'], $observation['effectiveDateTime']);

            if (empty($observation['reportOrigin'])) {
                $observation['reportOrigin'] = [
                    'coding' => [
                        ['code' => '']
                    ]
                ];
            }

            if ($observation['categories'][0]['coding'][0]['system'] === 'eHealth/observation_categories') {
                $observation['codingSystem'] = 'loinc';
            } else {
                $observation['codingSystem'] = 'icf';
            }

            return $observation;
        }, $observations);
    }
}
