<?php

namespace App\Livewire\Employee\Traits;

use App\Core\Arr;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Revision;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use App\Repositories\Repository;

trait ManagesEmployeeForm
{
    use WithFileUploads;

    protected ?Employee $employee = null;

    #[Locked]
    public ?int $employeeId = null;
    public ?EmployeeRequest $employeeRequest = null;
    public bool $lockEmailAndTaxId = false;

    public function save(): void
    {
        // Section for data cleaning remains the same.
        if (isset($this->form->party['phones'])) {
            $cleanedPhones = [];
            foreach ($this->form->party['phones'] as $phone) {
                if (isset($phone['number']) && is_string($phone['number'])) {
                    $digits = preg_replace('/[^0-9]/', '', $phone['number']);
                    $phone['number'] = !empty($digits) ? '+' . $digits : '';
                }
                $cleanedPhones[] = $phone;
            }
            $this->form->party['phones'] = $cleanedPhones;
        }

        if (isset($this->form->documents) && is_array($this->form->documents)) {
            foreach ($this->form->documents as $key => $document) {
                if (!empty($document['issuedAt'])) {
                    $this->form->documents[$key]['issuedAt'] = Carbon::parse($document['issuedAt'])->format('Y-m-d');
                }
            }
        }

        try {
            $this->form->validate($this->form->rulesForSave());
            $preparedDataForDb = $this->form->getPreparedData();

            if (!$this->employeeRequest || $this->employeeRequest->uuid) {

                // --- Case 1: CREATE a new EmployeeRequest ---
                // This happens when creating from scratch or re-saving a signed request.
                $newRequest = Repository::employee()->store(
                    $preparedDataForDb,
                    legalEntity(),
                    new EmployeeRequest(),
                    null,
                    true
                );

                $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);
                $this->saveRevisionForRequest($newRequest, $nestedDataForRevision);
                $this->employeeRequest = $newRequest;

            } else {
                // --- Case 2: UPDATE an existing draft (which has no UUID) ---
                DB::transaction(function () use ($preparedDataForDb) {
                    $requestAttributes = Arr::only($preparedDataForDb, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
                    $this->employeeRequest->fill($requestAttributes)->save();

                    if ($this->employeeRequest->party) {
                        $partyAttributes = Arr::only($preparedDataForDb, ['last_name', 'first_name', 'second_name', 'gender', 'birth_date', 'tax_id', 'no_tax_id', 'email', 'working_experience', 'about_myself']);
                        $this->employeeRequest->party->update($partyAttributes);
                    }

                    $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);

                    if ($this->employeeRequest->revision) {
                        $this->employeeRequest->revision->update(['data' => $nestedDataForRevision]);
                    } else {
                        $this->saveRevisionForRequest($this->employeeRequest, $nestedDataForRevision);
                    }
                });
            }

            if ($this->employeeRequest) {
                $this->employeeRequestId = $this->employeeRequest->id;
            }

            session()->flash('success', __('forms.employee_request_saved_successfully'));

        } catch (ValidationException $e) {
            $this->dispatch('employee-form-failed');
            session()->flash('error-modal', __('forms.validation_failed_check_form'));
            throw $e;
        } catch (\Exception $e) {
            $this->handleException($e);
            throw $e;
        }
    }

    /**
     * Helper method to prepare the nested data structure required for a Revision.
     *
     * @param array $flatData The flat data array from the form.
     * @return array The nested data array.
     */
    private function prepareDataForRevision(array $flatData): array
    {
        $employeeChunk = Arr::only($flatData, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
        $partyChunk = Arr::only($flatData, ['last_name', 'first_name', 'second_name', 'gender', 'birth_date', 'tax_id', 'no_tax_id', 'email', 'working_experience', 'about_myself']);
        $documentsChunk = $flatData['documents'] ?? [];
        $phonesChunk = $flatData['phones'] ?? [];
        $doctorChunk = $flatData['doctor'] ?? [];

        return [
            'employee_request_data' => $employeeChunk,
            'party' => $partyChunk,
            'documents' => $documentsChunk,
            'phones' => $phonesChunk,
            'doctor' => $doctorChunk,
        ];
    }

    /**
     * Helper to encapsulate saving the revision.
     * Now accepts the EmployeeRequest model to be more explicit.
     */
    private function saveRevisionForRequest(EmployeeRequest $request, array $nestedData): void
    {
        $revision = new Revision();
        $revision->data = $nestedData;
        $revision->status = Revision::STATUS_PENDING;
        // Save the revision specifically for the provided request.
        $request->revision()->save($revision);
    }

    /**
     * NEW METHOD: This is the single entry point for the final "Sign" button in the modal.
     * It validates everything, saves, signs, and sends.
     */
    public function sign()
    {
        $this->save();
        try {
            if ($this->employeeRequestId && !$this->employeeRequest) {
                $this->employeeRequest = EmployeeRequest::find($this->employeeRequestId);
            }

            $this->form->validate($this->form->rulesForKepOnly());

            if (!$this->employeeRequest) {
                throw new \RuntimeException('Employee request could not be saved or found before signing.');
            }

            $dataForSigning = $this->formatEHealthRequest($this->employeeRequest->revision->data);
            $signedContent = signatureService()->signData(
                $dataForSigning,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                'Person',
                $this->form->party['taxId']
            );

            if ($this->sendSignedContentToEhealth($signedContent)) {
                session()->flash('success', __('forms.requestSignedAndSentToEHealth'));

                return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);
            }

        } catch (Exception $e) {
            session()->flash('error-modal', $e->getMessage());
            $this->handleException($e);
        }
    }

    /**
     * REFACTORED: Now uses the form's unpackRevisionData helper.
     */
    private function formatEHealthRequest(array $revisionData): array
    {
        $employeeData = $revisionData['employee_request_data'];
        $partyData = $revisionData['party'];
        $documentsData = $revisionData['documents'];
        $phonesData = $revisionData['phones'];
        $doctorData = $revisionData['doctor'];

        $apiEmployeeRequest = [
            'position' => $employeeData['position'] ?? null,
            'status' => 'NEW',
            'employee_type' => $employeeData['employee_type'] ?? null,
            'legal_entity_id' => (string)($employeeData['legal_entity_id'] ?? legalEntity()->id),
            'start_date' => isset($employeeData['start_date']) ? Carbon::parse($employeeData['start_date'])->format('Y-m-d') : null,
        ];

        if (!empty($employeeData['end_date'])) {
            $apiEmployeeRequest['end_date'] = Carbon::parse($employeeData['end_date'])->format('Y-m-d');
        }

        $apiEmployeeRequest['party'] = [
            'first_name' => $partyData['first_name'] ?? null,
            'last_name' => $partyData['last_name'] ?? null,
            'second_name' => $partyData['second_name'] ?? null,
            'birth_date' => isset($partyData['birth_date']) ? Carbon::parse($partyData['birth_date'])->format('Y-m-d') : null,
            'gender' => $partyData['gender'] ?? null,
            'no_tax_id' => (bool)($partyData['no_tax_id'] ?? false),
            'tax_id' => $partyData['tax_id'] ?? null,
            'email' => $partyData['email'] ?? null,
            'working_experience' => isset($partyData['working_experience']) ? (int)$partyData['working_experience'] : null,
            'about_myself' => $partyData['about_myself'] ?? null,

            'phones' => array_map(
                fn($phone) => ['type' => $phone['type'], 'number' => $phone['number']],
                $phonesData
            ),

            'documents' => array_map(
                fn($doc) => [
                    'type' => $doc['type'],
                    'number' => $doc['number'],
                    'issued_by' => $doc['issued_by'] ?? null,
                    'issued_at' => isset($doc['issued_at']) && !empty($doc['issued_at']) ? Carbon::parse($doc['issued_at'])->format('Y-m-d') : null
                ],
                $documentsData
            ),
        ];

        if (($employeeData['employee_type'] ?? null) === 'DOCTOR' && !empty($doctorData)) {
            $doctorPayload = [];
            if (!empty($doctorData['educations'])) $doctorPayload['educations'] = $doctorData['educations'];
            if (!empty($doctorData['qualifications'])) $doctorPayload['qualifications'] = $doctorData['qualifications'];
            if (!empty($doctorData['specialities'])) $doctorPayload['specialities'] = $doctorData['specialities'];
            if (!empty($doctorData['science_degrees'])) $doctorPayload['science_degree'] = $doctorData['science_degrees'][0];

            if (!empty($doctorPayload)) $apiEmployeeRequest['doctor'] = $doctorPayload;
        }

        return ['employee_request' => $apiEmployeeRequest];
    }

    /**
     * Sends the signed content to eHealth API and returns success status.
     */
    protected function sendSignedContentToEhealth(string $signedContent): bool
    {
        try {
            $ehealthResponse = EmployeeRequestApi::createEmployeeRequest(
                [
                    'signed_content' => $signedContent,
                    'signed_content_encoding' => 'base64',
                ]
            );

            if (isset($ehealthResponse['id'])) {
                $this->employeeRequest->uuid = $ehealthResponse['id'];
                $this->employeeRequest->inserted_at = $ehealthResponse['inserted_at'];
                $this->employeeRequest->legal_entity_uuid = $ehealthResponse['legal_entity_id'];
                $this->employeeRequest->updated_at = $ehealthResponse['updated_at'];
                $this->employeeRequest->save();

                $this->dispatch('signature-successful');
                return true;
            } else {
                $errorMessage = $ehealthResponse['error']['message'] ?? __('forms.failed_to_send_request_to_esoz_unknown_error');
                if (isset($ehealthResponse['error']['invalid']) && is_array($ehealthResponse['error']['invalid'])) {
                    $detailedErrors = collect($ehealthResponse['error']['invalid'])->map(fn($error) => $error['description'] ?? $error['rule'] ?? __('forms.details_unknown'))->implode('; ');
                    $errorMessage .= ' ' . $detailedErrors;
                }
                session()->flash('error', $errorMessage);
                $this->dispatch('employee-form-failed');
                return false;
            }
        } catch (Exception $e) {
            $this->handleException($e);
            return false;
        }
    }

    private function handleException(Exception $e): void
    {
        Log::error('Process failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        $message = $e instanceof ValidationException
            ? __('forms.validation_failed_check_form')
            : __('forms.failed_to_save_employee_unexpected_error');
        session()->flash('error', $message);
        $this->dispatch('employee-form-failed');
    }
}
