<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest;
use App\Models\Division;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;

class Employee extends EHealthRequest
{
    public const string URL = '/api/employees';

    /**
     * Get a list of employees from E-Health with pagination and optional filters.
     * Renamed from getEmployeesList for clarity.
     *
     * @param array $filters An associative array of query parameters to filter the results.
     *
     * @return array
     * @throws ConnectionException
     */
    public function getMany(array $filters): array
    {
        $employees = [];
        $page = 1;
        $perPage = config('ehealth.api.page_size', 150);
        $totalPages = 1;

        while ($page <= $totalPages) {
            $queryParams = array_merge($filters, [
                'page'      => $page,
                'page_size' => $perPage
            ]);

            $response = $this->get(self::URL, $queryParams);

            if (isset($response['data']) && is_array($response['data'])) {
                array_push($employees, ...$response['data']);
            }

            $totalPages = $response['paging']['total_pages'] ?? 1;
            $page++;
        }

        if (count($employees) > 1) {
            $ownerIndex = array_search('OWNER', array_column($employees, 'employee_type'), true);
            if ($ownerIndex !== false && isset($employees[0])) {
                $tmp = $employees[$ownerIndex];
                $employees[$ownerIndex] = $employees[0];
                $employees[0] = $tmp;
            }
        }

        return $employees;
    }

    public static function prepareEmployeeDataForDb(array $ehealthData, LegalEntity $legalEntity, ?User $user = null): array
    {
        $prepared = [
            'uuid' => $ehealthData['id'],
            'status' => $ehealthData['status'],
            'position' => $ehealthData['position'],
            'employee_type' => $ehealthData['employee_type'],
            'start_date' => Carbon::parse($ehealthData['start_date'])->toDateString(),
            'end_date' => isset($ehealthData['end_date']) ? Carbon::parse($ehealthData['end_date'])->toDateString() : null,
            'inserted_at' => Carbon::now(),
            'legal_entity_id' => $legalEntity->id,
            'legal_entity_uuid' => $legalEntity->uuid,
            'party_id' => null,
            'user_id' => null,
            'division_id' => null,
        ];

        if (isset($ehealthData['party']['id'])) {
            $party = Party::firstWhere('uuid', $ehealthData['party']['id']);
            if ($party) {
                $prepared['party_id'] = $party->id;
                $user = $user ?? $party->user;
            }
        }

        if ($user) {
            $prepared['user_id'] = $user->id;
        }

        if (isset($ehealthData['division']['id'])) {
            $division = Division::firstWhere('uuid', $ehealthData['division']['id']);
            if ($division) {
                $prepared['division_id'] = $division->id;
            }
        }

        return $prepared;
    }
}
