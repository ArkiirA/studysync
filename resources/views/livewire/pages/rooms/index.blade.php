<?php

use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $name = '';
    public string $joinCode = '';
    public string $joinError = '';

    public function createRoom(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $room = Room::create([
            'name' => $this->name,
            'created_by' => Auth::id(),
        ]);

        $room->members()->attach(Auth::id(), ['joined_at' => now()]);

        $this->redirect(route('rooms.show', $room), navigate: true);
    }

    public function joinRoom(): void
    {
        $this->joinError = '';
        $code = strtoupper(trim($this->joinCode));

        if (! $code) {
            $this->joinError = 'Enter a room code.';
            return;
        }

        $room = Room::where('code', $code)->first();

        if (! $room) {
            $this->joinError = "No room found with code {$code}.";
            return;
        }

        if (! $room->members()->where('users.id', Auth::id())->exists()) {
            $room->members()->attach(Auth::id(), ['joined_at' => now()]);
        }

        $this->redirect(route('rooms.show', $room), navigate: true);
    }

    public function with(): array
    {
        return [
            'myRooms' => Auth::user()->rooms()->latest('rooms.created_at')->get(),
        ];
    }
}; ?>

<div>
    <x-slot:header>
        <h1 class="h4 fw-bold mb-0">Study Rooms</h1>
    </x-slot:header>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Create a room</h2>
                    <form wire:submit="createRoom">
                        <div class="mb-3">
                            <x-input-label for="name" value="Room name" />
                            <x-text-input wire:model="name" id="name" type="text" placeholder="Midterm Review" />
                            <x-input-error :messages="$errors->get('name')" />
                        </div>
                        <x-primary-button>Create room</x-primary-button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Join with a code</h2>
                    @if ($joinError)
                        <div class="alert alert-warning py-2 small">{{ $joinError }}</div>
                    @endif
                    <form wire:submit="joinRoom">
                        <div class="mb-3">
                            <x-input-label for="joinCode" value="Room code" />
                            <x-text-input wire:model="joinCode" id="joinCode" type="text" placeholder="ABC123" class="text-uppercase" />
                        </div>
                        <button type="submit" class="btn btn-accent">Join room</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($myRooms->isNotEmpty())
        <h2 class="h6 fw-bold mb-3">Your rooms</h2>
        <div class="list-group">
            @foreach ($myRooms as $room)
                <a href="{{ route('rooms.show', $room) }}" wire:navigate
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span>{{ $room->name }}</span>
                    <span class="badge bg-light text-secondary border font-monospace">{{ $room->code }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
