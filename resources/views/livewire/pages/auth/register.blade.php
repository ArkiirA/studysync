<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="h4 fw-bold mb-1">Create your account</h1>
    <p class="text-secondary mb-4">Free tools work instantly — sign up to unlock Study Rooms and AI features.</p>

    <form wire:submit="register" novalidate>
        <div class="mb-3">
            <x-input-label for="name" value="Name" />
            <x-text-input wire:model="name" id="name" type="text" autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" type="email" autocomplete="username" placeholder="you@university.edu" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" value="Password" />
            <x-text-input wire:model="password" id="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="mb-4">
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="w-100 py-2">Create account</x-primary-button>
    </form>

    <p class="text-center text-secondary small mt-4 mb-0">
        Already registered?
        <a href="{{ route('login') }}" wire:navigate>Log in</a>
    </p>
</div>
