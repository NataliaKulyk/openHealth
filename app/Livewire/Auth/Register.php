<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rules\Password;

#[Layout('layouts.guest')]
class Register extends Component
{
    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $userData = $this->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', Password::defaults()],
            'passwordConfirmation' => ['required', 'string', 'same:password']
        ]);

        $user = new User();
        $user->password = Hash::make($userData['password']);
        $user->email = $userData['email'];
        $user->save();
        $user->refresh();

        event(new Registered($user));

        session()->flash('success', __('auth.login.success.new_user_create'));

        $this->redirect(route('login', absolute: false), navigate: true);
    }

    public function messages(): array
    {
        return [
            'passwordConfirmation.required' => __('forms.field_empty'),
            'passwordConfirmation.same' => __('auth.login.error.validation.confirm_mismatch'),
        ];
    }
}
