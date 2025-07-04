<?php

declare(strict_types=1);

namespace App\Repositories\MedicalEvents;

use App\Models\MedicalEvents\Sql\Procedure;
use App\Models\MedicalEvents\Sql\ProcedureComplicationDetail;
use App\Models\MedicalEvents\Sql\ProcedureReasonReference;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcedureRepository extends BaseRepository
{
    /**
     * Store procedure in DB.
     *
     * @param  array  $data
     * @param  int|null  $createdEncounterId
     * @return int|null
     * @throws Throwable
     */
    public function store(array $data, ?int $createdEncounterId = null): ?int
    {
        try {
            return DB::transaction(function () use ($data, $createdEncounterId) {
                $procedureId = null;

                foreach ($data as $datum) {
                    if (isset($datum['basedOn'])) {
                        $basedOn = Repository::identifier()->store($datum['basedOn']['identifier']['value']);
                        Repository::codeableConcept()->attach($basedOn, $datum['basedOn']);
                    }

                    $code = Repository::identifier()->store($datum['code']['identifier']['value']);
                    Repository::codeableConcept()->attach($code, $datum['code']);

                    if ($createdEncounterId) {
                        $encounter = Repository::identifier()->store($datum['encounter']['identifier']['value']);
                        Repository::codeableConcept()->attach($encounter, $datum['encounter']);
                    }

                    $recordedBy = Repository::identifier()->store($datum['recordedBy']['identifier']['value']);
                    Repository::codeableConcept()->attach($recordedBy, $datum['recordedBy']);

                    if (isset($datum['performer'])) {
                        $performer = Repository::identifier()->store($datum['performer']['identifier']['value']);
                        Repository::codeableConcept()->attach($performer, $datum['performer']);
                    }

                    if (isset($datum['reportOrigin'])) {
                        $reportOrigin = Repository::codeableConcept()->store($datum['reportOrigin']);
                    }

                    if (isset($datum['division'])) {
                        $division = Repository::identifier()->store($datum['division']['identifier']['value']);
                        Repository::codeableConcept()->attach($division, $datum['division']);
                    }

                    $managingOrganization = Repository::identifier()->store(
                        $datum['managingOrganization']['identifier']['value']
                    );
                    Repository::codeableConcept()->attach($managingOrganization, $datum['managingOrganization']);

                    if (isset($datum['outcome'])) {
                        $outcome = Repository::codeableConcept()->store($datum['outcome']);
                    }

                    $category = Repository::codeableConcept()->store($datum['category']);

                    /** @var Procedure $procedure */
                    $procedure = $this->model::create([
                        'uuid' => $datum['uuid'] ?? $datum['id'],
                        'encounter_internal_id' => $createdEncounterId,
                        'status' => $datum['status'],
                        'based_on_id' => $basedOn->id ?? null,
                        'code_id' => $code->id,
                        'encounter_id' => $encounter->id ?? null,
                        'recorded_by_id' => $recordedBy->id,
                        'primary_source' => $datum['primarySource'],
                        'performer_id' => $performer->id ?? null,
                        'report_origin_id' => $reportOrigin->id ?? null,
                        'division_id' => $division->id ?? null,
                        'managing_organization_id' => $managingOrganization->id,
                        'outcome_id' => $outcome->id ?? null,
                        'note' => $datum['note'] ?? null,
                        'category_id' => $category->id
                    ]);

                    $procedure->performedPeriod()->create([
                        'start' => $datum['performedPeriod']['start'],
                        'end' => $datum['performedPeriod']['end']
                    ]);

                    if (isset($datum['reasonReferences'])) {
                        foreach ($datum['reasonReferences'] as $reasonReference) {
                            $identifier = Repository::identifier()->store(
                                $reasonReference['identifier']['value']
                            );
                            Repository::codeableConcept()->attach($identifier, $reasonReference);

                            ProcedureReasonReference::create([
                                'procedure_id' => $procedure->id,
                                'identifier_id' => $identifier->id ?? null
                            ]);
                        }
                    }

                    if (isset($datum['complicationDetails'])) {
                        foreach ($datum['complicationDetails'] as $complicationDetail) {
                            $identifier = Repository::identifier()->store(
                                $complicationDetail['identifier']['value']
                            );
                            Repository::codeableConcept()->attach($identifier, $complicationDetail);

                            ProcedureComplicationDetail::create([
                                'procedure_id' => $procedure->id,
                                'identifier_id' => $identifier->id ?? null
                            ]);
                        }
                    }

                    if (isset($datum['paperReferral'])) {
                        $procedure->paperReferral()->create([
                            'requisition' => $datum['paperReferral']['requisition'] ?? null,
                            'requester_legal_entity_name' => $datum['paperReferral']['requesterLegalEntityName'] ?? null,
                            'requester_legal_entity_edrpou' => $datum['paperReferral']['requesterLegalEntityEdrpou'],
                            'requester_employee_name' => $datum['paperReferral']['requesterEmployeeName'],
                            'service_request_date' => $datum['paperReferral']['serviceRequestDate'],
                            'note' => $datum['paperReferral']['note'] ?? null
                        ]);
                    }

                    $usedCodeIds = [];
                    foreach ($datum['usedCodes'] as $usedCodeData) {
                        $usedCode = Repository::codeableConcept()->store($usedCodeData);

                        $usedCodeIds[] = $usedCode->id;
                    }

                    $procedure->usedCodes()->attach($usedCodeIds);
                }

                // Return the ID when creating separately
                return $createdEncounterId === null ? $procedureId : null;
            });
        } catch (Exception $e) {
            Log::channel('db_errors')->error('Error saving procedure', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            throw $e;
        }
    }
}
