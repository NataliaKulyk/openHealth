<?php

declare(strict_types=1);

namespace App\Livewire\EmployeeRequest;

use App\Classes\eHealth\EHealth;
use App\Enums\Employee\RequestStatus;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Jobs\EmployeeRequestsSyncAll;
use App\Livewire\Employee\EmployeeComponent;
use App\Models\Employee\EmployeeRequest;
use App\Services\Employee\EmployeeRequestProcessor;
use Auth;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class EmployeeRequestIndex extends EmployeeComponent
{
    use WithPagination;

    public string $search = '';
    public string $status       = '';

    public function mount(): void
    {
        $this->loadDictionaries();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function syncOne(int $requestId, EmployeeRequestProcessor $processor): void
    {
        Log::info("[SyncOne] Started for Request ID: {$requestId}");

        $localRequest = EmployeeRequest::with(['revision', 'employee', 'party'])->find($requestId);

        if (!$localRequest || !$localRequest->uuid) {
            $this->dispatch('flashMessage', ['message' => 'Request has no UUID.', 'type' => 'error']);

            return;
        }

        $token = session()->get(config('ehealth.api.oauth.bearer_token'));
        if (!$token) {
            $this->dispatch('flashMessage', ['message' => 'Session token missing. Please re-login.', 'type' => 'error']);

            return;
        }

        try {
            Log::info("[SyncOne] Fetching Status via User Token for UUID: {$localRequest->uuid}");

            // Call standard getById (User Token)
            $response = EHealth::employeeRequest()
                ->withToken($token)
                ->getDetails($localRequest->uuid);

            // Expecting 'data' key. Note: User Token response DOES NOT contain 'employee_id'.
            $remoteData = $response->json('data');

            if (!$remoteData) {
                $this->dispatch('flashMessage', ['message' => 'eHealth returned empty data.', 'type' => 'warning']);

                return;
            }

            $remoteStatus = $remoteData['status'] ?? 'UNKNOWN';
            Log::info("[SyncOne] Remote status: {$remoteStatus}.");

            if ($remoteStatus === 'APPROVED') {
                // Delegate to Processor. It will search by Tax ID since employee_id is missing.
                $processor->applyApprovedRequest($localRequest, $remoteData);

                $this->dispatch('flashMessage', [
                    'message' => __('employees.sync.employee_request_success'),
                    'type' => 'success'
                ]);

            } elseif (in_array($remoteStatus, ['REJECTED', 'EXPIRED'])) {
                $newStatus = ($remoteStatus === 'REJECTED') ? RequestStatus::REJECTED : RequestStatus::EXPIRED;
                $localRequest->update(['status' => $newStatus, 'applied_at' => now()]);

                $this->dispatch('flashMessage', ['message' => "Status updated to {$remoteStatus}.", 'type' => 'info']);
            } else {
                $this->dispatch('flashMessage', ['message' => "Status unchanged: {$remoteStatus}", 'type' => 'info']);
            }

        } catch (\Exception $e) {
            Log::error("[SyncOne] ERROR: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->dispatch('flashMessage', [
                'message' => 'Sync Error: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Mass synchronization.
     * Process 1st page synchronously, dispatch Job for the rest.
     */
    public function sync(EmployeeRequestProcessor $processor): void
    {
        $user = Auth::user();

        // Notify start
        $this->dispatch('flashMessage', [
            'message' => __('employees.sync.started'),
            'type' => 'success'
        ]);

        try {
            // 1. Synchronous request for Page 1
            $response = EHealth::employeeRequest()->getMany(['edrpou' => legalEntity()->edrpou]); // Page 1 is default

        } catch (ConnectionException $e) {
            Log::error('Employee Request sync failed: No connection.', ['error' => $e->getMessage()]);
            $this->dispatch('flashMessage', ['message' => 'Немає зв\'язку з ЕСОЗ', 'type' => 'error']);

            return;
        } catch (EHealthResponseException $e) {
            Log::error('Employee Request sync failed: API error.', ['error' => $e->getMessage()]);
            $this->dispatch('flashMessage', ['message' => 'Помилка API ЕСОЗ: ' . $e->getMessage(), 'type' => 'error']);

            return;
        } catch (\Exception $e) {
            Log::error('Employee Request sync failed: Unexpected error.', ['error' => $e->getMessage()]);
            $this->dispatch('flashMessage', ['message' => 'Виникла помилка при ініціалізації синхронізації', 'type' => 'error']);

            return;
        }

        // 2. Process Page 1 immediately
        $validatedData = $response->validate();
        $processedCount = $processor->processBatch($validatedData, legalEntity());

        // 3. Check if there are more pages
        if ($response->isNotLast()) {
            // Dispatch Job starting from Page 2
            EmployeeRequestsSyncAll::dispatch(legalEntity(), 2);

            $this->dispatch('flashMessage', [
                'message' => "Сторінка 1 оброблена ({$processedCount} оновлень). Решта завантажується фоново.",
                'type' => 'success'
            ]);
        } else {
            // If it was the only page - we are done
            $this->dispatch('flashMessage', [
                'message' => "Синхронізацію завершено. Оновлено заявок: {$processedCount}.",
                'type' => 'success'
            ]);
        }

        // Force refresh of the table
        $this->resetPage();
    }

    /**
     * Fetches the paginated list of all requests.
     * English annotations used as requested.
     */
    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        return EmployeeRequest::query()
            ->with(['party', 'division', 'revision'])
            ->where('legal_entity_id', legalEntity()->id)
            ->when($this->search, function ($query) {
                $searchTerm = '%' . $this->search . '%';

                $query->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->whereHas('party', function ($q) use ($searchTerm) {
                        $q->whereRaw("CONCAT(last_name, ' ', first_name, ' ', second_name) ILIKE ?", [$searchTerm]);
                    })
                        ->orWhereHas('revision', function ($q) use ($searchTerm) {
                            $q->whereRaw("
                        (data->'party'->>'last_name') ILIKE ? OR
                        (data->'party'->>'first_name') ILIKE ? OR
                        (data->'party'->>'second_name') ILIKE ?
                    ", [$searchTerm, $searchTerm, $searchTerm]);
                        });
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function render(): object
    {
        return view('livewire.employee-request.employee-request-index', [
            'requests' => $this->requests,
            'statuses' => RequestStatus::cases(),
            'dictionaries' => $this->dictionaries,
        ]);
    }
}
