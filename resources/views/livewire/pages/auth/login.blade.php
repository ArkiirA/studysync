<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new \Illuminate\Auth\Events\Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}; ?>

<div>
    <h1 class="h4 fw-bold mb-1">Welcome back</h1>
    <p class="text-secondary mb-4">Log in to keep working where you left off.</p>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form wire:submit="login" novalidate>
        <div class="mb-3">
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" type="email" autofocus autocomplete="username" placeholder="you@university.edu" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" value="Password" />
            <x-text-input wire:model="password" id="password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input wire:model="remember" class="form-check-input" type="checkbox" id="remember">
                <label class="form-check-label small text-secondary" for="remember">Remember me</label>
            </div>

            @if (Route::has('password.request'))
                <a class="small" href="{{ route('password.request') }}" wire:navigate>Forgot password?</a>
            @endif
        </div>

        <x-primary-button class="w-100 py-2">Log in</x-primary-button>
    </form>

    <p class="text-center text-secondary small mt-4 mb-0">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate>Sign up</a>
    </p>
</div>
