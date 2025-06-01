<?php

declare(strict_types=1);

namespace App\Livewire\License;

use App\Classes\eHealth\Api\LicenseApi;
use App\Models\LegalEntity;
use App\Models\License;
use App\Traits\FormTrait;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class LicenseIndex extends Component
{
    use FormTrait;
    use WithPagination;

    public const CACHE_PREFIX = 'licenses_user_id';

    public object $licenses;

    public array $tableHeaders = [];
    public string $selectedLicenseTypeOption = 'all';
    public string $selectedStatusOption = 'all';
    protected string $licenseCacheKey;
    protected array $licenseTypes = [];
    public int $storeId = 0;

    public function boot(): void
    {
        $user = Auth::user();

        $this->licenseCacheKey = self::CACHE_PREFIX . '-' . $user->id;
    }

    public function mount(LegalEntity $legalEntity): void
    {
        $this->tableHeaders();
        $this->getLastStoreId();
        $this->getLicenses();
        $this->getLicenseTypes();
    }

    #[On('refreshPage')]
    public function refreshPage()
    {
        $this->dispatch('$refresh');
    }

    protected function generateCacheKey(): string
    {
        $userId = Auth::id();
        $type = $this->selectedLicenseTypeOption;
        $status = $this->selectedStatusOption;
        return self::CACHE_PREFIX . "-{$userId}-{$type}-{$status}";
    }

    public function getLastStoreId()
    {
        if (Cache::has($this->licenseCacheKey) && !empty(Cache::get($this->licenseCacheKey)) && is_array(Cache::get($this->licenseCacheKey))) {
            $this->storeId = array_key_last(Cache::get($this->licenseCacheKey));
        }

        $this->storeId++;
    }

    protected function getQuery(): Builder
    {
        $legal_entity_id = legalEntity()->id;

        $query = DB::table('licenses')
                // ->join('users', 'licenses.legal_entity_id', '=', 'users.legal_entity_id')
                // ->where('users.legal_entity_id', $legal_entity_id)
                ->where('legal_entity_id', $legal_entity_id)
                ->select(
                    'licenses.id as id',
                    'licenses.type',
                    'licenses.issued_date',
                    'licenses.active_from_date',
                    'licenses.order_no',
                    'licenses.license_number',
                    'licenses.expiry_date',
                    'licenses.what_licensed',
                    'licenses.is_primary'
                );

        if ($this->selectedStatusOption === 'is_primary') {
            $query->where('licenses.is_primary', true);
        } elseif ($this->selectedStatusOption === 'is_additional') {
            $query->where('licenses.is_primary', false);
        }

        if ($this->selectedLicenseTypeOption !== 'all') {
            $query->where('licenses.type', $this->selectedLicenseTypeOption);
        }

        return $query;
    }

    public function getLicenses(): void
    {

        $cacheKey = $this->generateCacheKey();

        if (Cache::has($cacheKey)) {
            $this->licenses = collect(Cache::get($cacheKey));
        } else {
            $query = $this->getQuery();

            $this->licenses = $query->distinct()->get();

            // Cache the licenses data
            Cache::put($cacheKey, $this->licenses->toArray(), 60 * 60); // Cache for 1 hour
        }
    }

    public function getLicenseTypes(): void
    {
        $this->licenseTypes = dictionary()->getDictionaries(['LICENSE_TYPE']);
    }

    public function tableHeaders(): void
    {
        $this->tableHeaders = [
            __('Тип ліцензії'),
            __('Дата видачі'),
            __('Напрям діяльності, що ліцензовано'),
            __('forms.action'),
        ];
    }

    public function create()
    {
        return redirect()->route('license.view', [legalEntity(), 'store_id' => $this->storeId]);
    }

    public function sortTypeLicenses(): void
    {
        $this->getLicenses();

        // Update cache
        $cacheKey = $this->generateCacheKey();
        Cache::put($cacheKey, $this->licenses->toArray(), 60 * 60); // Cache for 1 hour
    }

    public function render()
    {
        $query = $this->getQuery();
        $perPage = config('pagination.per_page');

        // TODO: check for correctness when total amount of items in collection returned by $query will become more than $perPage
        /**
         * This need because $query must returns unique records only. If so the LengthAwarePaginator will contain wrong total amount.
         * To fix it 'groupBy' method rather used to. In this case  total will contain proper amount of items.
         */
        $licensesPagination = $query->distinct()->groupBy('licenses.id')->paginate($perPage);

        return view('livewire.license.license-index', ['licenseTypes' => $this->licenseTypes, 'licensesPagination' => $licensesPagination]);
    }

    /**
     * synchronize licenses with eHealth, the method overrides existing licenses if uuid is the same
     */
    public function sync()
    {
        try {
            $licences = LicenseApi::_get([
                'page' => 1,
            ]);
        } catch (\Exception $e) {
            $this->flashGeneralError();
            return;
        }

        foreach ($licences as $number => $license) {
            unset($licences[$number]['legal_entity_uuid']);
            $licences[$number]['legal_entity_id'] = legalEntity()->id;
        }

        try {
            License::upsert($licences, uniqueBy: ['uuid'], update: new License()->getFillable());
        } catch (\Exception $e) {
            $this->flashGeneralError();
            Log::error('Error while synchronizing licenses with eHealth: ' . $e->getMessage());
            return;
        }

        $this->dispatch('flashMessage', [
            'message' => __('forms.license.sync_success'),
            'type' => 'success',
        ]);
    }
}
