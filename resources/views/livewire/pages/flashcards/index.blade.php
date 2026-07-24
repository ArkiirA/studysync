<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public function with(): array
    {
        return [
            'sets' => Auth::user()->flashcardSets()->latest()->get(),
        ];
    }

    public function delete(int $id): void
    {
        $set = Auth::user()->flashcardSets()->findOrFail($id);
        $set->delete();
    }
}; ?>

<div>
    <x-slot:header>
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 fw-bold mb-0">Your flashcard sets</h1>
            <a href="{{ route('tools.flashcards') }}" class="btn btn-primary btn-sm" wire:navigate>+ New set</a>
        </div>
    </x-slot:header>

    @if ($sets->isEmpty())
        <div class="card">
            <div class="card-body p-4 text-center text-secondary">
                No saved sets yet.
                <a href="{{ route('tools.flashcards') }}" wire:navigate>Generate your first one →</a>
            </div>
        </div>
    @else
        <div class="list-group">
            @foreach ($sets as $set)
                <div wire:key="set-{{ $set->id }}" class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="{{ route('flashcards.show', $set) }}" class="text-decoration-none flex-grow-1" wire:navigate>
                        <div class="fw-medium">{{ $set->title }}</div>
                        <div class="small text-secondary">
                            {{ count($set->cards_json) }} cards · {{ $set->created_at->diffForHumans() }}
                        </div>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="delete({{ $set->id }})" wire:confirm="Delete this set?">
                        Delete
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
