<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Livewire\Component;
use App\Models\LegalEntity;
use Livewire\Attributes\Layout;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

#[Layout('layouts.guest')]
class SelectLegalEntity extends Component
{
    public array $accessibleLegalEntities = [];

    public ?string $selectedLegalEntityId = null;

    protected ?User $user;

    /**
     * Get all legal entities founded in the system.
     * Reformat it data to the array looks like:
     * [
     *  ['<id-1>', 'Legal Entity 1 Name']
     *  ['<id-2>', 'Legal Entity 2 Name']
     * ]
     *
     * @return array
     */
    protected function getLegalEntitesList(array $legalEntityIds): array
    {
        $edrList = LegalEntity::whereIn('id', $legalEntityIds)
            ->select(['id', 'edr'])
            ->get()
            ->toArray();

        return array_map(function ($data) {
            $edr = $data['edr'];
            $arr['id'] = $data['id'];

            if (!empty($edr['name'])) {
                $arr['name'] = $edr['name'];
            } else if(!empty($arr['public_name'])) {
                $arr['name'] = $edr['public_name'];
            }

            return $arr;
        }, $edrList);
    }

    /*
     * This method executed on every request to the Livewire component
     * after restoring state of the public properties
     */
    public function hydrate(): void
    {
        $this->user = Auth::user();
    }

    public function mount()
    {
        $this->user = Auth::user(); // Do not remove this!

        if (legalEntity()) {
            return null;
        }

        // This shouldn't happen never but who knows...
        if (!$this->user) {
            Log::error(__("Accidentally lost user authentication on redirect to 'select-legal-entity' page"));

            return Redirect::route('login');
        }

        // Get array of the all LegalEntity ids available to the User
        $this->accessibleLegalEntities = session()->has('user_accessible_legal_entities')
            ? session()->get('user_accessible_legal_entities')
            : $this->user->accessibleLegalEntities()->toArray();

        // This shouldn't happen never but who knows...
        if (empty($this->accessibleLegalEntities)) {
            Log::error(__("Cannot find any suitable LegalEntities for user {$this->user->id} for 'select-legal-entity' page"));

            return redirect( route('create.legalEntities'));
        }

        // If user has access to only one Legal Entity
        if (count($this->accessibleLegalEntities) === 1) {
            // Get first ID (here it is only one) from array
            $this->selectedLegalEntityId = $this->accessibleLegalEntities[0];

            $legalEntity = LegalEntity::find($this->selectedLegalEntityId);

            return Redirect::route('dashboard', [$legalEntity]);
        } else {
            // Get array with the id and names of the all LegalEntittes available to the User
            $this->accessibleLegalEntities = $this->getLegalEntitesList($this->accessibleLegalEntities);
        }

        return null;
    }

    /*
     * Proceed selected Legal Entity from the form
     * ID of the selected Legal Entity will be stored in $this->selectedLegalEntityId
     */
    public function finalizeSelection()
    {
        $this->validate();

        $legalEntity = LegalEntity::find($this->selectedLegalEntityId);

        return Redirect::route('dashboard', [$legalEntity]);
    }

    protected function rules(): array
    {
        $uuids = array_map(fn($arr) => $arr['id'], $this->accessibleLegalEntities);

        return[
            'selectedLegalEntityId' => ['required', Rule::in($uuids)]
        ];
    }

    public function messages()
    {
        return [
            'selectedLegalEntityId.required' => __('forms.choose_legal_entity'),
            'selectedLegalEntityId.in' => __('forms.del_and_choose_value'),
        ];
    }
}
