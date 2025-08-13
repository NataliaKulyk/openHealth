<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use App\Models\Division;
use App\Models\LegalEntity;
use App\Traits\AddressSearch;
use App\Models\Relations\Phone;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use App\Traits\WorkTimeUtilities;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class DivisionCreate extends DivisionComponent
{
    use WorkTimeUtilities,
        AddressSearch;

    /**
     * Array containing dictionary names only used within the component.
     *
     * @var array
     */
    protected array $allowedDictionaryItems = [];

    public function mount(LegalEntity $legalEntity)
    {
        $this->divisionForm->initWorkingHours($this->weekdays);

        $this->allowedDictionaryItems = [
            'PHONE_TYPE' => Phone::getPhoneTypes(),
            'DIVISION_TYPE' => Division::getValidDivisionTypes()
        ];

        // Throw out unused dictionary items
        $this->dictionaries = $this
            ->setDictionary()
            ->filterDictionaries($this->dictionaries, $this->allowedDictionaryItems);
    }

    /**
     * Validate the data coming from the form(s)
     *
     * @return bool
     */
    public function validateDivision(): bool
    {
        $error = $this->divisionForm->doValidation();

        if ($error) {
            session()->flash('error', $error);

            return false;
        } else {
            return true;
        }
    }

    /**
     * Store data from the Division's form into the DB
     *
     * @return void
     */
    public function store(): void
    {
        if (Auth::user()->cannot('create', Division::class)) {
            session()->flash('error', __('У вас немає дозволу на створення місця надання послуг'));

            return;
        }

        if ($this->validateDivision()) {
            // TODO: will return to it on the next PRs
            // try {
            //     Repository::division()->saveDivisionData($this->divisionForm->division, legalEntity());
                session()->flash('success', __('forms.saved_successfully'));
            // } catch (Exception $err) {
            //     Log::channel('db_errors')->error('Cannot save Divisiion\'s data!', ['error' => $err->getMessage()]);

            //     session()->flash(__('Не вдалося зберегти дані в БД'));

                return;
            // }
        }
    }

    /**
     * Combined method used both creation and modification Division's data
     *
     * @return Redirector|RedirectResponse|null
     */
    public function create(): Redirector|RedirectResponse|null
    {
        $this->store();

        $response = $this->createDivision();

        if ($response) {
            // Repository::division()->syncDivisionData($this->divisionForm->division, legalEntity()); // TODO: realize it on the next PRs
            Repository::division()->saveDivisionData($response, legalEntity()); // TODO: Remove it after the syncDivisionData() will works

            return redirect()->route('division.index', [legalEntity()])->with('success', __('Запит виконано успішно'));
        }

        session()->flash('error', __('Помилка в процесі обробки запиту'));

        return null;
    }

    /**
     * Create the new Division
     * Note: all the data should be present into $this->division property up to now
     *
     * @return array
     */
    protected function createDivision(): array|null
    {
        $division = removeEmptyKeys($this->divisionForm->division);
        $division['addresses'] = $this->convertArrayKeysToSnakeCase($division['addresses']);

        try {
            return EHealth::division()->create(data: $division)->validate();
        } catch (\Exception $err) {
            Log::error(self::class . ':updateDivision', ['error' => $err->getMessage()]);
        }

        return null;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.division.division-create');
    }
}
