<?php

use App\Models\FlashcardSet;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public FlashcardSet $set;

    public function mount(FlashcardSet $set): void
    {
        $this->authorize('view', $set);
        $this->set = $set;
    }
}; ?>

<div>
    <x-slot:header>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h4 fw-bold mb-0">{{ $set->title }}</h1>
                <span class="text-secondary small">{{ count($set->cards_json) }} cards · saved {{ $set->created_at->diffForHumans() }}</span>
            </div>
            <a href="{{ route('flashcards.index') }}" class="btn btn-outline-secondary btn-sm" wire:navigate>All sets</a>
        </div>
    </x-slot:header>

    <div class="row g-3">
        @foreach ($set->cards_json as $card)
            <div class="col-sm-6 col-lg-4">
                <x-flashcard :question="$card['question']" :answer="$card['answer']" />
            </div>
        @endforeach
    </div>
</div>
