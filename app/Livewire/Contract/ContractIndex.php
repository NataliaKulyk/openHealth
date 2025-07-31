<?php

namespace App\Livewire\Contract;

use App\Livewire\Contract\Forms\Api\ContractRequestApi;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\LegalEntity;
use App\Services\DictionaryService;
use App\Traits\FormTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
class ContractIndex extends Component
{
    use FormTrait;

    const string CACHE_PREFIX = 'register_contract_form';

    public ?array $tableHeaders;

    public ?array $dictionaryNames = [
        'CONTRACT_TYPE',
    ];

    #[Validate('required')]
    public string $contractType;

    protected string $contractCacheKey;
    /**
     * @var true
     */
    public bool $hasInitContract = true;

    public ?LegalEntity $legalEntity;

    #[Locked]
    public int $legalEntityId;

    public?int $selectedContractId = null;

    public ?Contract $contract;

    public function getLegalEntity(): void
    {
        $this->legalEntity = legalEntity();
    }

    public function boot(): void
    {
        $this->contractCacheKey = self::CACHE_PREFIX . '-'. legalEntity()->uuid;
    }


    public function mount(LegalEntity $legalEntity): void
    {
        $this->tableHeaders();
        $this->getDictionary();
        $this->hasInitContract();
        $this->getLegalEntity();

//        dd(Cache::get($this->contractCacheKey));
    }

    #[Computed]
    public function tableHeaders(): void
    {
        $this->tableHeaders = [
            __('ID'),
            __('forms.number_contract'),
            __('forms.start_date'),
            __('forms.end_date'),
            __('forms.status'),
            __('forms.action'),
        ];
    }

    #[Computed]
    public function contractTypes(): array
    {
        // Assuming getDictionary() fetches this data
        // This logic should be moved to a dedicated service and called here
        return DictionaryService::get('CONTRACT_TYPE');
    }

    public function render()
    {
        $perPage = config('pagination.per_page');

        $contracts = $this->legalEntity->contract()->paginate($perPage);

        return view('livewire.contract.contract-index', compact('contracts'));
    }

    public function createRequest()
    {
        // This part handles resuming a form from cache
        if (Cache::has($this->contractCacheKey)){
            // FIX 1: Make the redirect explicit here
            return redirect()->route('contract.form', [
                'legalEntity' => legalEntity()
            ]);
        }

        $this->validate();

        $initContractRequestApi = ContractRequestApi::initContractRequestApi($this->contractType);
        if (!empty($initContractRequestApi)) {
            Cache::tags(['legal-entity:'. $this->legalEntity->uuid])
                ->put($this->contractCacheKey, $initContractRequestApi, now()->addHours(24));
        }

        // FIX 2: Make the main redirect explicit here as well
        return redirect()->route('contract.form', [
            'legalEntity' => legalEntity()
        ]);
    }

    public function hasInitContract(): void
    {
        if (Cache::has($this->contractCacheKey)){
            $this->hasInitContract = false;
        }
    }

    #[Computed]
    public function hasExistingFormCache(): bool
    {
        return Cache::has($this->contractCacheKey);
    }

    public function showContract($id):void
    {
        $this->contract = Contract::find($id);
        $this->openModal('show_contract');
    }

}
