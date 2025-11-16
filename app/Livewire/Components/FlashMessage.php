<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class FlashMessage extends Component
{
    public string $message = '';

    public string $type = 'success';

    public array $errors = [];

    protected $listeners = ['flashMessage'];

    public function mount()
    {
        // This need for displaying flash messages after redirects (via ->with() method)
        $this->message = session('success') ?? session('error') ?? '';
        $this->type = session('success') ? 'success' : (session('error') ? 'error' : '');
        $this->errors = session('errors') ? session('errors')->all() : [];
    }

    public function flashMessage($flash): void
    {
        $this->message = $flash['message'] ?? '';
        $this->type = $flash['type'];
        $this->errors = $flash['errors'] ?? [];
    }

    public function render(): View
    {
        return view('livewire.components.flash-message');
    }
}
