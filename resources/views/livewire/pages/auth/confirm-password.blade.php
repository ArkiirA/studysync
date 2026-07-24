<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate(['password' => ['required', 'string']]);

        if (! Auth::guard('web')->validate(['email' => Auth::user()->email, 'password' => $this->password])) {
            throw ValidationException::withMessages(['password' => __('auth.password')]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirect(session()->pull('url.intended', route('dashboard', absolute: false)), navigate: true);
    }
}; ?>

<div>
    <h1 class="h4 fw-bold mb-1">Confirm your password</h1>
    <p class="text-secondary mb-4">This is a protected area — please confirm your password before continuing.</p>

    <form wire:submit="confirmPassword" novalidate>
        <div class="mb-4">
            <x-input-label for="password" value="Password" />
            <x-text-input wire:model="password" id="password" type="password" autofocus autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-primary-button class="w-100 py-2">Confirm</x-primary-button>
    </form>
</div>
