<?php

declare(strict_types=1);

namespace App\Livewire\Declaration;

use App\Core\Arr;
use App\Enums\Declaration\Status;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use Exception;
use Illuminate\Validation\ValidationException;

class DeclarationCreate extends DeclarationComponent
{
    public function mount(LegalEntity $legalEntity, int $patientId): void
    {
        $this->baseMount($patientId);
    }

    /**
     * @return void
     */
    public function createLocally(): void
    {
        if (!$this->ensureAbility('create', 'У вас немає дозволу на створення заявки на подання декларації')) {
            return;
        }

        $this->setDivisionId();

        try {
            $validated = $this->form->validate($this->form->rulesForCreating());
        } catch (ValidationException $exception) {
            session()?->flash('error', $exception->validator->errors()->first());

            return;
        }

        try {
            $validated['status'] = Status::DRAFT->value;

            Repository::declarationRequest()->store(Arr::toSnakeCase($validated));

            $this->redirectRoute('declaration.index', [legalEntity()], navigate: true);
        } catch (Exception $exception) {
            $this->logDatabaseErrors($exception, 'Error saving declaration request');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }
}
