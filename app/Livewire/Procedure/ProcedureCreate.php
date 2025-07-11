<?php

declare(strict_types=1);

namespace App\Livewire\Procedure;

use App\Classes\eHealth\Api\PatientApi;
use App\Core\Arr as CoreArr;
use App\Repositories\MedicalEvents\Repository;
use App\Traits\HandlesReasonReferences;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcedureCreate extends ProcedureComponent
{
    use HandlesReasonReferences;

    /**
     * Validate and save data.
     *
     * @param  array  $data
     * @return void
     */
    public function save(array $data): void
    {
        $formattedData = Repository::procedure()->formatEHealthRequest($data);

        if (!$this->validateFormatted($formattedData)) {
            return;
        }

        try {
            $this->storeValidatedData($formattedData);
        } catch (Throwable $e) {
            $this->flashGeneralError();

            Log::channel('db_errors')->error('Error saving procedure', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return;
        }
    }

    /**
     * Submit encrypted data.
     *
     * @param  array  $data
     * @return void
     */
    public function sign(array $data): void
    {
        $formattedData = Repository::procedure()->formatEHealthRequest($data);

        if (!$this->validateFormatted($formattedData)) {
            return;
        }

        try {
            $this->storeValidatedData($formattedData);

            $base64EncryptedData = $this->sendEncryptedData(CoreArr::toSnakeCase($formattedData), Auth::user()->tax_id ?? '2690710542');
            PatientApi::submitProcedurePackage($this->patientUuid, ['signed_data' => $base64EncryptedData]);
        } catch (Throwable $e) {
            $this->flashGeneralError();

            Log::channel('db_errors')->error('Error saving procedure', [
                'context' => __CLASS__ . '::' . __FUNCTION__,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return;
        }

        to_route('patient.index', [legalEntity()])->with('flashMessage', [
            'message' => 'Процедура успішно створена',
            'type' => 'success'
        ]);
    }

    /**
     * Validate formatted data.
     *
     * @param  array  $formattedData
     * @return bool
     */
    protected function validateFormatted(array $formattedData): bool
    {
        try {
            $this->form->validateForm($formattedData);

            return true;
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return false;
        }
    }

    /**
     * Store validated formatted data into DB.
     *
     * @param  array  $formattedData
     * @return void
     * @throws Throwable
     */
    protected function storeValidatedData(array $formattedData): void
    {
        DB::transaction(function () use ($formattedData) {
            Repository::procedure()->store([$formattedData]);

            // Save the selected condition and observation locally if they don't exist in our database.
            $this->processReasonReferences($formattedData);
        });
    }
}
