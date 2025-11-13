<?php

declare(strict_types=1);

namespace App\Livewire\Equipment;

use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\EHealthResponse;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\Equipment;
use App\Models\LegalEntity;
use App\Traits\FormTrait;
use App\Livewire\Equipment\Forms\EquipmentForm as Form;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EquipmentComponent extends Component
{
    use FormTrait;

    public Form $form;

    /**
     * List of device definition.
     *
     * @var array
     */
    public array $deviceDefinitions;

    /**
     * List of active divisions.
     *
     * @var array
     */
    public array $divisions;

    /**
     * List of parent equipments.
     *
     * @var array
     */
    public array $equipments;

    /**
     * Full name recorder.
     *
     * @var string
     */
    public string $recorderFullName;

    /**
     * Used to indicate is it edit page, if so update DB row instead of create new one.
     *
     * @var int|null
     */
    #[Locked]
    public ?int $equipmentId = null;

    public function baseMount(LegalEntity $legalEntity): void
    {
        $this->divisions = $legalEntity->divisions()->active()->get(['uuid', 'name'])->toArray();
        $this->equipments = $legalEntity->equipments()
            ->active()
            ->with('names:equipment_id,name,type')
            ->get(['id', 'uuid', 'status', 'availability_status'])
            ->map(static fn (Equipment $equipment) => [
                'uuid' => $equipment->uuid,
                'name' => $equipment->names->first()->name,
                'type' => $equipment->names->first()->type,
                'status' => $equipment->status,
                'availabilityStatus' => $equipment->availabilityStatus
            ])
            ->toArray();

        $recorderData = Auth::user()->employees()
            ->activeRecorders($legalEntity->id)
            ->get(['uuid', 'party_id'])
            ->firstOrFail();
        $this->recorderFullName = $recorderData->fullName;
        $this->form->recorder = $recorderData->uuid;
    }

    protected function loadEquipmentToForm(Equipment $equipment): void
    {
        $equipment->loadMissing(['names', 'recorder:id,uuid', 'division:id,uuid']);

        $formData = $equipment->toArray();

        $formData['recorder'] = $equipment->recorder()->value('uuid');
        $formData['divisionId'] = $equipment->division()->value('uuid');
        $formData['parentId'] = $equipment->value('parent_id');

        $this->form->fill($formData);
    }

    /**
     * Validate form, if valid return validated data.
     *
     * @return array|false
     */
    protected function validateForm(): array|false
    {
        try {
            return $this->form->validate();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return false;
        }
    }

    /**
     * Send a request to the API; if successful, return it; otherwise, show and log errors.
     *
     * @param  array  $validated
     * @return EHealthResponse|PromiseInterface|null
     */
    protected function createInEHealth(array $validated): EHealthResponse|PromiseInterface|null
    {
        try {
            return EHealth::equipment()->create(removeEmptyKeys(Arr::toSnakeCase($validated)));
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when creating equipment');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ.");

            return null;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when creating equipment');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return null;
        }
    }
}
