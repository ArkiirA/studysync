<?php

use App\Models\FlashcardSet;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    const MAX_CHARS = 10000;

    public string $inputText = '';
    public array $cards = [];
    public string $title = '';
    public bool $loading = false;
    public string $errorMessage = '';
    public string $saveStatus = '';

    public function generate(): void
    {
        $this->errorMessage = '';
        $this->saveStatus = '';
        $this->cards = [];

        $text = trim($this->inputText);

        if (mb_strlen($text) < 40) {
            $this->errorMessage = 'Paste a bit more text — at least a few sentences — to generate useful flashcards.';
            return;
        }

        $text = mb_substr($text, 0, self::MAX_CHARS);

        $key = 'ai-flashcards:' . Auth::id();
        $limit = (int) config('services.gemini.daily_limit', 15);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            $hours = ceil($seconds / 3600);
            $this->errorMessage = "You've hit today's limit of {$limit} flashcard generations. Try again in about {$hours}h.";
            return;
        }

        $this->loading = true;

        try {
            RateLimiter::hit($key, 86400);
            $this->cards = app(GeminiService::class)->generateFlashcards($text);

            if (empty($this->cards)) {
                $this->errorMessage = "Couldn't generate flashcards from that text — try adding more detail.";
            } else {
                $this->title = mb_substr(trim(strtok($text, "\n")), 0, 60) ?: 'Untitled set';
            }
        } catch (\Throwable $e) {
            $this->errorMessage = 'Something went wrong generating flashcards. Please try again.';
        } finally {
            $this->loading = false;
        }
    }

    public function save(): void
    {
        if (empty($this->cards)) {
            return;
        }

        $this->validate(['title' => ['required', 'string', 'max:100']]);

        $set = FlashcardSet::create([
            'user_id' => Auth::id(),
            'title' => $this->title,
            'cards_json' => $this->cards,
        ]);

        $this->redirect(route('flashcards.show', $set), navigate: true);
    }
}; ?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="text-center mb-4">
            <div class="eyebrow mb-2">AI tool — {{ config('services.gemini.daily_limit', 15) }}/day</div>
            <h1 class="h3 fw-bold mb-2">Flashcard Generator</h1>
            <p class="text-secondary">
                Paste your notes, get flippable Q&amp;A flashcards.
                <a href="{{ route('flashcards.index') }}" wire:navigate>View saved sets →</a>
            </p>
        </div>

        <div class="card mb-4">
            <div class="card-body p-4">
                @if ($errorMessage)
                    <div class="alert alert-warning py-2 small">{{ $errorMessage }}</div>
                @endif

                <div class="mb-2">
                    <x-input-label for="inputText" value="Notes" />
                    <textarea id="inputText" rows="8" class="form-control" wire:model="inputText"
                              placeholder="Paste study notes here…"></textarea>
                    <div class="form-text d-flex justify-content-between">
                        <span>Capped at {{ number_format(self::MAX_CHARS) }} characters.</span>
                        <span>{{ number_format(mb_strlen($inputText)) }} / {{ number_format(self::MAX_CHARS) }}</span>
                    </div>
                </div>

                <button type="button" class="btn btn-primary mt-2" wire:click="generate" wire:loading.attr="disabled" wire:target="generate">
                    <span wire:loading.remove wire:target="generate">Generate flashcards</span>
                    <span wire:loading wire:target="generate">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span> Generating…
                    </span>
                </button>
            </div>
        </div>

        @if (! empty($cards))
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <input type="text" class="form-control" style="max-width: 320px;" wire:model="title" placeholder="Name this set">
                <button type="button" class="btn btn-accent" wire:click="save">Save this set</button>
                @if ($saveStatus)
                    <span class="small text-success">{{ $saveStatus }}</span>
                @endif
                <x-input-error :messages="$errors->get('title')" />
            </div>

            <div class="row g-3">
                @foreach ($cards as $card)
                    <div class="col-sm-6 col-lg-4">
                        <x-flashcard :question="$card['question']" :answer="$card['answer']" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
