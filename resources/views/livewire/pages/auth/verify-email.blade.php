<?php

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirect(route('dashboard', absolute: false), navigate: true);
            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        session()->flash('status', 'verification-link-sent');
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <h1 class="h4 fw-bold mb-1">Verify your email</h1>
    <p class="text-secondary mb-4">
        Thanks for signing up! Before getting started, click the link we emailed you to verify your address.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success py-2 small mb-4">
            A new verification link has been sent to the email address you provided.
        </div>
    @endif

    <div class="d-flex gap-2">
        <button wire:click="sendVerification" class="btn btn-primary">
            Resend verification email
        </button>
        <button wire:click="logout" class="btn btn-outline-secondary">
            Log out
        </button>
    </div>
</div>
