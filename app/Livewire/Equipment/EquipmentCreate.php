<?php

declare(strict_types=1);

namespace App\Livewire\Equipment;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Equipment\AvailabilityStatus;
use App\Enums\Equipment\Status;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\Equipment;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Traits\FormTrait;
use App\Livewire\Equipment\Forms\EquipmentForm as Form;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class EquipmentCreate extends Component
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

    protected array $dictionaryNames = ['device_definition_classification_type'];

    public function mount(LegalEntity $legalEntity): void
    {
        $this->getDictionary();

        $this->divisions = $legalEntity->divisions()->active()->get(['uuid', 'name'])->toArray();
        $this->equipments = $legalEntity->equipments()
            ->active()
            ->with('names:equipment_id,name,type')
            ->get(['id', 'uuid'])
            ->map(static fn (Equipment $equipment) => [
                'uuid' => $equipment->uuid,
                'name' => $equipment->names->first()->name
            ])
            ->toArray();

        $this->form->status = Status::ACTIVE->value;
        $this->form->availabilityStatus = AvailabilityStatus::AVAILABLE->value;

        $recorderData = Auth::user()->employees()
            ->activeRecorders($legalEntity->id)
            ->get(['uuid', 'party_id'])
            ->firstOrFail();
        $this->recorderFullName = $recorderData->fullName;
        $this->form->recorder = $recorderData->uuid;
    }

    public function create(): void
    {
        if (Auth::user()?->cannot('create', Equipment::class)) {
            Session::flash('error', 'У вас немає дозволу на створення обладнання');

            return;
        }

        try {
            $validated = $this->form->validate();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        // Create in eHealth
        try {
            $response = EHealth::equipment()->create(removeEmptyKeys(Arr::toSnakeCase($validated)));
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when creating equipment');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ.");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when creating equipment');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        try {
            $validated = $response->validate();
            Repository::equipment()->store($response->map($validated));

            Session::flash('success', 'Обладнання успішно створено.');
            //            $this->redirectRoute('equipment.index', [legalEntity()], navigate: true);
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Failed to store equipment');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.equipment.equipment-create');
    }
}
