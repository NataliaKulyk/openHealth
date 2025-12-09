<?php

namespace App\Livewire\LegalEntity;

use Arr;
use Throwable;
use App\Models\LegalEntity;
use App\Classes\eHealth\EHealth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\LegalEntity\LegalEntity as LegalEntityComponent;

class LegalEntityDetails extends LegalEntityComponent
{
    public array $edrStatuses = [];

    public array $edrLegalForms = [];

    public array $mainKVED = [];

    public array $additionalKVEDs = [];

    public function mount(?LegalEntity $legalEntity = null): void
    {
        $this->legalEntity = $this->getLegalEntity();

        parent::mount();

        $this->getLegalEntityForm();

        $this->edrStatuses = dictionary()->getDictionary('EDR_STATE');
        $this->edrLegalForms = dictionary()->getDictionary('LEGAL_FORM');

        $this->filterKveds();
    }

    /**
     * Try to get the LegalEntity assigned for the user
     *
     * @return LegalEntity|null
     */
    protected function getLegalEntity(): ?LegalEntity
    {
        return legalEntity()?->loadMissing(['licenses', 'addresses', 'phones', 'revisions']) ?? null;
    }


    protected function setLegalEntity(): bool
    {
        $isNotNew = parent::setLegalEntity();

        if ($isNotNew) {
            $address = data_get($this->legalEntity->toArray(), 'addresses.0', []);

            $this->mergeAddress($this->convertArrayKeysToCamelCase($address));
        }

        return $isNotNew;
    }

   /**
     * Retrieves the legal entity form data.
     */
    protected function getLegalEntityForm(): void
    {
        $this->setLegalEntity(); // Retrieve basic legal entity data
        $this->getLicenseForm(); // Get the license form data
        $this->getArchiveForm(); // Get the archive form data
        $this->getOwnerLegalEntity(); // Get the owner's legal entity data
        $this->getAccreditationForm(); // Get the accreditation form data status

        $this->legalEntityForm->residenceAddress = $this->address;
    }

    /**
     * Retrieves and sets only specific fields related to the license from the legal entity form.
     */
    protected function getLicenseForm(): void
    {
        $license = $this->legalEntity->licenses()?->first();

        if ($license) {
            $this->legalEntityForm->license = Arr::only(
                $this->convertArrayKeysToCamelCase($license->toArray()),
                [
                    'type',
                    'licenseNumber',
                    'issuedBy',
                    'issuedDate',
                    'expiryDate',
                    'activeFromDate',
                    'whatLicensed',
                    'orderNo'
                ]
            );
        }
    }

    /**
     * Retrieves and formats specific fields from the archive form.
     */
    protected function getArchiveForm(): void
    {
        // Extracting only 'date' and 'place' fields from the first element of the archive
        if (!empty($this->legalEntityForm->archive)) {
            // if the legal entity has an archive, the 'archivationShow' property is set to true
            $this->legalEntityForm->archivationShow = true;
        }
    }

    /**
     * Get the accreditation status of the legal entity
     * (if the legal entity has an accreditation, the 'accreditationShow' property is set to true)
     *
     * @return void
     */
    protected function getAccreditationForm(): void
    {
        if (!empty($this->legalEntityForm->accreditation) && $this->legalEntityForm->accreditation['category'] !== null) {
            $this->legalEntityForm->accreditationShow = true;
        }
    }

    /**
     * Retrieves and sets the owner legal entity for the current legal entity.
     *
     * @return void
     */
    protected function getOwnerLegalEntity(): void
    {
        $owner = $this->legalEntity->getOwner();

        if (!$owner->exists()) {
            return;
        }

        $ownerData = $owner->party->toArray() ?? [];

        $ownerData['phones'] = $owner->party->phones->toArray() ?? [];
        $ownerData['documents'] = $this->prepareDocumentsData($owner->party->documents->toArray());
        $ownerData['position'] = $owner->position;
        $ownerData['employee_id'] = $owner->uuid;
        $ownerData['email'] = $owner->user->email;

        // TODO: remove it when all other entity will use the same date format
        $ownerData['birthDate'] = convertToAppDateFormat($ownerData['birthDate']);

        $this->legalEntityForm->owner = array_merge($this->legalEntityForm->owner ?? [], $ownerData);
    }

    /**
     * Prepare documents data for display or processing.
     *
     * @param array $documents The raw documents data to be prepared
     *
     * @return array
     */
    private function prepareDocumentsData(array $documents): array
    {
        if (empty($documents)) {
            return [];
        }

        // TODO: remove it when all other entity will use the same date format
        // $documents[0]['issuedAt'] = Carbon::parse($documents[0]['issuedAt'])->format(config('app.date_format'));

        return $this->convertArrayKeysToCamelCase($documents[0]);
    }

     /**
     * Filters the KVED (Classification of Types of Economic Activities) codes.
     * This method processes and filters the collection of KVED codes associated
     *
     * @return void
     */
    protected function filterKveds(): void
    {
        $mainKved = [];
        $additionalKveds = [];

        foreach ($this->legalEntity->edr['kveds'] as $kved) {
            $kvedArr = ['code' => $kved['code'], 'name' => $kved['name']];

            if (data_get($kved, 'is_primary')) {
                $mainKved = $kvedArr;
            } else {
                $additionalKveds[] = $kvedArr;
            }
        }

        $this->mainKVED = $this->convertArrayKeysToCamelCase($mainKved);
        $this->additionalKVEDs = $this->convertArrayKeysToCamelCase($additionalKveds);
    }

    /**
     * Synchronizes the legal entity data.
     *
     * @return void
     */
    public function sync(): void
    {
        /*
         * This is need by Livewire behavior.
         * On the first render, mount() runs and assigns $this->legalEntity.
         * On subsequent requests (e.g., when clicking synchronize button), Livewire does NOT run mount() again
         * and does NOT rehydrate protected typed properties.
         * Code below allows to ensure that property is set before use.
         */
        $this->legalEntity ??= $this->getLegalEntity();

        if (Auth::user()->cannot('sync', $this->legalEntity)) {
            session()->flash('error', __('legal-entity.policy.deny.sync'));

            return;
        }

        $oldType = $this->legalEntity->type->name;

        try {
            $response = EHealth::legalEntity()->getDetails();

            $legalEntityData = ['data' => $response->validate()];

            // Set accreditation and archive to null concerns on the storda data in the DB table
            $legalEntityData = $this->filterUnprovidedFields($legalEntityData, $this->legalEntityForm->toArray());

            $this->modifyLegalEntity($legalEntityData);
        } catch (EHealthResponseException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncLegalEntity', ['error' => $err->getMessage()]);

            session()->flash('error', __('errors.ehealth.messages.server_error'));

            return;
        } catch (EHealthValidationException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncLegalEntity', ['error' => $err->getDetails()]);

            session()->flash('error', __('errors.ehealth.messages.server_error'));

            return;
        } catch (Throwable $err) {
            Log::channel('db_errors')->error(static::class . ': [syncLegalEntity]: ', ['error' => $err->getMessage()]);

            session()->flash('error', __('legal-entity.request.sync.errors.fail'));

            return;
        }

        if ($legalEntityData['data']['type'] !== $oldType) {
            Log::channel('e_health_warnings')->warning(
                static::class . ': [syncLegalEntity]: Legal Entity type changed',
                [
                    'legal_entity_uuid' => $this->legalEntity->uuid,
                    'old_type' => $oldType,
                    'new_type' => $legalEntityData['data']['type'],
                ]
            );

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            Auth::user()->unsetRelation('roles')->unsetRelation('permissions');
        }

        $this->redirect(route('legal-entity.details', [legalEntity()]), navigate: true);

        session()->flash('success', __('forms.update_successfull'));

        return;
    }

    public function render()
    {
        return view('livewire.legal-entity.legal-entity-details');
    }
}
