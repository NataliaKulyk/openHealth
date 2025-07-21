<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\On;

class FlashMessage extends Component
{
    public string $message = '';
    public string $type = 'success';
    public array $errors = [];

    protected $listeners = ['flashMessage'];

    public function flashMessage($flash): void
    {
        $this->message = $flash['message'] ?? '';
        $this->type = $flash['type'];
        $this->errors = $flash['errors'] ?? [];
    }

    #[On('show-notification')]
    public function showNotification($data): void
    {
        $this->message = $data['message'] ?? '';
        $this->type = $data['type'] ?? 'success';
        $this->errors = $data['errors'] ?? [];
        $this->visible = true;

        // This will automatically hide the message after 5 seconds
        $this->dispatch('start-flash-timer');
    }

    public function hideNotification(): void
    {
        $this->visible = false;
    }

    public function render(): View
    {
        return view('livewire.components.flash-message');
    }
}
