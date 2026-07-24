<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public string $name = '';
    public $avatar = null;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->name = $validated['name'];

        if ($this->avatar) {
            $path = $this->avatar->store('avatars', 'public');
            $user->avatar_url = Storage::url($path);
        }

        $user->save();
        $this->avatar = null;

        session()->flash('profile-status', 'Profile updated.');
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        Auth::user()->update(['password' => Hash::make($validated['password'])]);

        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('password-status', 'Password updated.');
    }
}; ?>

<div>
    <x-slot:header>
        <h1 class="h4 fw-bold mb-0">Profile</h1>
    </x-slot:header>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Profile information</h2>

                    @if (session('profile-status'))
                        <div class="alert alert-success py-2 small">{{ session('profile-status') }}</div>
                    @endif

                    <form wire:submit="updateProfile" novalidate>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=2E3192&color=fff' }}"
                                 class="rounded-circle" width="56" height="56" alt="Current avatar">
                            <div class="flex-grow-1">
                                <x-input-label for="avatar" value="Change avatar" />
                                <input wire:model="avatar" id="avatar" type="file" accept="image/*" class="form-control form-control-sm">
                                <x-input-error :messages="$errors->get('avatar')" />
                            </div>
                        </div>

                        <div class="mb-3">
                            <x-input-label for="name" value="Name" />
                            <x-text-input wire:model="name" id="name" type="text" />
                            <x-input-error :messages="$errors->get('name')" />
                        </div>

                        <div class="mb-3">
                            <x-input-label value="Email" />
                            <input type="email" class="form-control" value="{{ auth()->user()->email }}" disabled>
                            <div class="form-text">Email changes aren't supported yet.</div>
                        </div>

                        <x-primary-button>Save changes</x-primary-button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Change password</h2>

                    @if (session('password-status'))
                        <div class="alert alert-success py-2 small">{{ session('password-status') }}</div>
                    @endif

                    <form wire:submit="updatePassword" novalidate>
                        <div class="mb-3">
                            <x-input-label for="current_password" value="Current password" />
                            <x-text-input wire:model="current_password" id="current_password" type="password" autocomplete="current-password" />
                            <x-input-error :messages="$errors->get('current_password')" />
                        </div>

                        <div class="mb-3">
                            <x-input-label for="password" value="New password" />
                            <x-text-input wire:model="password" id="password" type="password" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" />
                        </div>

                        <div class="mb-3">
                            <x-input-label for="password_confirmation" value="Confirm new password" />
                            <x-text-input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password_confirmation')" />
                        </div>

                        <x-primary-button>Update password</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
