<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate(['email' => ['required', 'string', 'email']]);

        Password::sendResetLink(['email' => $this->email]);

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }
}; ?>

<div>
    <h1 class="h4 fw-bold mb-1">Forgot your password?</h1>
    <p class="text-secondary mb-4">Enter your email and we'll send you a reset link.</p>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" novalidate>
        <div class="mb-4">
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" type="email" autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button class="w-100 py-2">Email password reset link</x-primary-button>
    </form>

    <p class="text-center text-secondary small mt-4 mb-0">
        <a href="{{ route('login') }}" wire:navigate>Back to log in</a>
    </p>
</div>
