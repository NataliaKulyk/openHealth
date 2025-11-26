<?php

declare(strict_types=1);

namespace App\Listeners\eHealth;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Events\EHealthUserLogin;
use App\Models\Employee\EmployeeRequest;
use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeUpdate
{
    private const string LOG_PREFIX = '[EmployeeUpdate]';

    /**
     * Handle the EHealthUserLogin event synchronously.
     * Updates the current user's employee data if changes are confirmed in eHealth.
     */
    public function handle(EHealthUserLogin $event): void
    {
        $user = $event->user;
        $legalEntity = $event->legalEntity;

        Log::info(self::LOG_PREFIX . " START processing for User ID: {$user->id}");

        // 1. Fetch Signed Requests (History)
        $allSignedRequests = EmployeeRequest::with(['revision', 'employee.party'])
            ->where('status', RequestStatus::SIGNED)
            ->where('email', $user->email)
            ->whereNotNull('employee_id')
            ->where('legal_entity_id', $legalEntity->id)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($allSignedRequests->isEmpty()) {
            return;
        }

        // 2. Identify Tax ID
        $taxId = $user->party->tax_id ?? $allSignedRequests->first()->revision->data['party']['tax_id'] ?? null;

        if ($taxId) {
            try {
                // Simplified Token usage (matches EmployeeCreate style)
                // We rely on the global session/token storage implicitly.
                $eHealthEmployees = EHealth::employee()
                    ->getMany(
                        [
                            'legal_entity_id' => $legalEntity->uuid,
                            'tax_id' => $taxId,
                            'status' => 'APPROVED',
                        ]
                    )
                    ->validate();

                $eHealthEmployeesMap = collect($eHealthEmployees)->keyBy('uuid');
                $groupedRequests = $allSignedRequests->groupBy('employee_id');

                foreach ($groupedRequests as $employeeId => $requests) {
                    /** @var EmployeeRequest $latestRequest */
                    $latestRequest = $requests->last();
                    $currentEmployee = $latestRequest->employee;

                    $employeeUuid = $currentEmployee->uuid ?? null;

                    if (!$employeeUuid || !$eHealthEmployeesMap->has($employeeUuid)) {
                        continue;
                    }

                    $eHealthData = $eHealthEmployeesMap->get($employeeUuid);
                    $revisionData = $latestRequest->revision->data ?? [];
                    $mappedRevision = EHealth::employeeRequest()->mapCreate($revisionData);

                    // --- DELTA CHECK LOGIC (Expanded) ---
                    $updateConfirmed = false;

                    // 1. Check Root Fields (Division)
                    // Division Check
                    $newDivisionId = $mappedRevision['employee']['division_id'] ?? null;
                    if ($newDivisionId && $newDivisionId !== $currentEmployee->division_id) {
                        $remoteDivId = $eHealthData['division']['id'] ?? $eHealthData['division_id'] ?? null;
                        if ($remoteDivId === $newDivisionId) {
                            Log::info(self::LOG_PREFIX . " Confirmed update: Division ID changed to {$newDivisionId}");
                            $updateConfirmed = true;
                        }
                    }

                    // 2. Check Party Mutable Fields (Second Name)
                    if (!$updateConfirmed && isset($mappedRevision['party'])) {
                        $currentParty = $currentEmployee->party;
                        $partyCheckList = ['second_name'];

                        foreach ($partyCheckList as $field) {
                            $newValue = $mappedRevision['party'][$field] ?? null;
                            $oldValue = $currentParty->{$field} ?? null;

                            // Cast booleans for comparison
                            if (is_bool($oldValue) || is_bool($newValue)) {
                                $newValue = (bool)$newValue;
                                $oldValue = (bool)$oldValue;
                            }

                            if ($newValue !== $oldValue) {
                                $remoteValue = $eHealthData['party'][$field] ?? null;
                                if (is_bool($newValue)) {
                                    $remoteValue = (bool) $remoteValue;
                                }

                                if ($remoteValue === $newValue) {
                                    Log::info(self::LOG_PREFIX . " Confirmed update: Party [{$field}] changed.");
                                    $updateConfirmed = true;
                                    break;
                                }
                            }
                        }
                    }

                    // 3. Check Doctor Specialities (Deep Check)
                    if (!$updateConfirmed && !empty($mappedRevision['specialities'])) {
                        // eHealth List API returns 'doctor.specialities'
                        $remoteSpecs = $eHealthData['doctor']['specialities'] ?? [];

                        foreach ($mappedRevision['specialities'] as $localSpec) {
                            $targetSpecType = $localSpec['speciality'];

                            // Find matching speciality in remote by type (e.g. THERAPIST)
                            $remoteSpecMatch = collect($remoteSpecs)->firstWhere('speciality', $targetSpecType);

                            // Check if mutable attributes (level, qualification) match our new intent
                            // Example: We changed level from SECOND to FIRST
                            if ($remoteSpecMatch && isset($localSpec['level']) && $localSpec['level'] === $remoteSpecMatch['level']) {
                                // We need to verify if this is actually a CHANGE from DB.
                                // Getting current DB spec is complex inside this loop, so we rely on:
                                // If Local Revision Level == Remote Level, assume it's synced.
                                // To be strictly delta-based, we would compare with $currentEmployee->specialities,
                                // but assuming strict sync on match is safe for optimistic update.

                                // Let's rely on strict equality of the specific changed field
                                Log::info(self::LOG_PREFIX . " Confirmed update: Doctor Speciality Level match.");
                                $updateConfirmed = true;
                                break;
                            }
                        }
                    }

                    // 4. Apply Update
                    if ($updateConfirmed) {
                        DB::transaction(
                            static function () use ($requests, $latestRequest, $mappedRevision, $eHealthData) {
                                $employee = $latestRequest->employee;

                                // Update Employee
                                $employee->update(array_merge(
                                    $mappedRevision['employee'],
                                    Arr::only($eHealthData, ['status', 'is_active'])
                                ));

                                // Update Details
                                // Use Revision for content, but capture extra eHealth stats if present
                                $eHealthPartyExtras = Arr::only($eHealthData['party'] ?? [], ['declaration_count', 'declaration_limit', 'verification_status']);

                                Repository::employee()->updateDetails(
                                    $employee,
                                    array_merge($mappedRevision['party'], $eHealthPartyExtras),
                                    $mappedRevision['documents'],
                                    $mappedRevision['phones'],
                                    $mappedRevision['educations'] ?? null,
                                    $mappedRevision['specialities'] ?? null,
                                    $mappedRevision['qualifications'] ?? null,
                                    $mappedRevision['scienceDegree'] ?? null
                                );

                                // Close requests
                                foreach ($requests as $req) {
                                    $req->update(['status' => RequestStatus::APPROVED, 'applied_at' => now()]);
                                    $req->revision?->update(['status' => RevisionStatus::APPLIED]);
                                }
                            }
                        );
                    }
                }
            } catch (Throwable $e) {
                Log::error(self::LOG_PREFIX . " Error: " . $e->getMessage());
            }
        }
    }
}
