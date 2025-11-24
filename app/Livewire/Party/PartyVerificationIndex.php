<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Classes\eHealth\EHealth;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PartyVerificationIndex extends Component
{
    use WithPagination;

    public LegalEntity $legalEntity;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->legalEntity = $legalEntity;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConnectionException
     */
    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
    {
        $token = session()->get(config('ehealth.api.oauth.bearer_token'));

        $apiResponse = EHealth::party()
            ->withToken($token)
            ->getMany([], $this->getPage());

        $apiData = $apiResponse->json();

        $paging = $apiData['paging'] ?? [];
        $totalFromApi = $paging['total_entries'] ?? 0;

        $items = $apiData['data'] ?? [];

        $partyUuids = collect($items)->pluck('party_id')->unique()->toArray();

        $localPartiesObjects = Party::whereIn('uuid', $partyUuids)
            ->get()
            ->keyBy('uuid');

        $mergedItems = collect($items)->map(function ($item) use ($localPartiesObjects) {
            $uuid = $item['party_id'];
            $localParty = $localPartiesObjects->get($uuid);

            if (!$localParty) {
                return null;
            }

            $item['party_name'] = $localParty->fullName;
            $item['local_id'] = $localParty->id;

            return $item;
        })->filter()->values();

        $perPage = 50;

        $total = $totalFromApi ?: ($this->getPage() * $perPage + ($mergedItems->count() < $perPage ? 0 : 1));

        $paginator = new LengthAwarePaginator(
            $mergedItems,
            $total,
            $perPage,
            $this->getPage(),
            ['path' => request()->url()]
        );

        return view('livewire.party.party-verification-index', [
            'verifications' => $paginator,
        ]);
    }
}
