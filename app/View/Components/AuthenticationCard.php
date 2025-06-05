<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AuthenticationCard extends Component
{
    public bool $showLogo;

    public function __construct(bool $showLogo = true)
    {
        $this->showLogo = $showLogo;
    }

    public function render()
    {
        return view('components.authentication-card');
    }
}
