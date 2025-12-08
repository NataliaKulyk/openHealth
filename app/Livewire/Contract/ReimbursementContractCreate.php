<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Classes\eHealth\Api\MedicalProgram;
use App\Livewire\Contract\Forms\ReimbursementContractRequestForm as Form;
use App\Models\LegalEntity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Log;

class ReimbursementContractCreate extends ContractComponent
{
    public Form $form;
    public array $medicalProgramsList = [];

    protected array $dictionaryNames = [
        'REIMBURSEMENT_CONTRACT_TYPE',
        'REIMBURSEMENT_CONTRACT_CONSENT_TEXT'
    ];

    public function mount(LegalEntity $legalEntity): void
    {
        $this->baseMount($legalEntity);
        $this->loadMedicalPrograms();
    }


    protected function loadMedicalPrograms(): void
    {

         Cache::forget('ehealth_medical_programs_reimbursement');

        $programs = Cache::remember('ehealth_medical_programs_reimbursement', 3600, function () { //Cache for 1 hour is enough
            try {
                // Create a new instance of the request right here
                $request = new MedicalProgram();

                // Switching to the context of MIS
                $response = $request->asMis()->getMany(
                    [
                        'page_size' => 100,
                    ]
                );

                return $response->getData();

            } catch (\Exception $e) {
                Log::error('Medical Programs Fetch Error: ' . $e->getMessage());
                return [];
            }
        });


        $formattedList = [];
        foreach ($programs as $item) {
            $formattedList[] = [
                'id' => $item['id'],
                'name' => $item['name'] . ' (' . ($item['type'] ?? 'N/A') . ')',
            ];
        }

        $this->medicalProgramsList = $formattedList;
    }

    protected function getContractType(): string
    {
        return 'reimbursement';
    }

    protected function collectPayload(array $data): array
    {
        $consentTextString = $this->dictionaries['REIMBURSEMENT_CONTRACT_CONSENT_TEXT']['APPROVED']
            ?? 'Я підтверджую достовірність наданих даних...';

        $payerAccount = str_replace(' ', '', $data['contractorPaymentDetails']['payerAccount'] ?? '');

        $insulinProgramId = ['1a227396-a0e4-4c4f-a0a9-6b358c8929d2'];

        $idForm = 'GENERAL';

        $payload = [
            'contractor_owner_id' => $this->form->contractorOwnerId,
            'contractor_base'     => $data['contractorBase'],
            'contractor_payment_details' => [
                'payer_account' => $payerAccount,
                'bank_name'     => $data['contractorPaymentDetails']['bankName'] ?? '',
            ],
            'start_date'      => Carbon::now()->addDay()->format('Y-m-d'),
            'end_date'        => Carbon::parse($data['endDate'])->format('Y-m-d'),

            'id_form'         => $idForm,

            'statute_md5'             => $data['statuteMd5'] ?? null,
            'additional_document_md5' => $data['additionalDocumentMd5'] ?? null,

            'consent_text'    => $consentTextString,

            'medical_programs' => $insulinProgramId,
        ];

        if (!empty($data['previousRequestId'])) {
            $payload['previous_request_id'] = $data['previousRequestId'];
        }

        return $payload;
    }

    /** Helper to generate a dummy number if NULL is rejected.
     *  Pattern: 4 digits - 4 letters - 4 digits
     */
    private function generateContractNumber(): string
    {
        return sprintf(
            '%04d-%s-%04d',
            rand(1000, 9999),
            strtoupper(\Illuminate\Support\Str::random(4)),
            rand(1000, 9999)
        );
    }

    public function render(): View
    {
        return view('livewire.contract.reimbursement-contract-create');
    }
}
