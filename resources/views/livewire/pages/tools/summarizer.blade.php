<?php

use App\Services\GeminiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    const MAX_CHARS = 10000;

    public string $inputText = '';
    public $pdf = null;

    public array $bullets = [];
    public bool $loading = false;
    public string $errorMessage = '';

    public function updatedPdf(): void
    {
        $this->errorMessage = '';

        $this->validate(['pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240']]);

        if (! $this->pdf) {
            return;
        }

        // Requires: composer require smalot/pdfparser
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $document = $parser->parseFile($this->pdf->getRealPath());
            $text = trim($document->getText());

            if (! $text) {
                $this->errorMessage = "Couldn't extract any text from that PDF — it may be a scanned image without a text layer.";
                return;
            }

            $this->inputText = mb_substr($text, 0, self::MAX_CHARS);
        } catch (\Throwable $e) {
            $this->errorMessage = "Couldn't read that PDF. Try pasting the text directly instead.";
        }
    }

    public function summarize(): void
    {
        $this->errorMessage = '';
        $this->bullets = [];

        $text = trim($this->inputText);

        if (mb_strlen($text) < 40) {
            $this->errorMessage = 'Paste a bit more text — at least a few sentences — for a useful summary.';
            return;
        }

        $text = mb_substr($text, 0, self::MAX_CHARS);

        $key = 'ai-summarize:' . Auth::id();
        $limit = (int) config('services.gemini.daily_limit', 15);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            $hours = ceil($seconds / 3600);
            $this->errorMessage = "You've hit today's limit of {$limit} summaries. Try again in about {$hours}h.";
            return;
        }

        $this->loading = true;

        try {
            RateLimiter::hit($key, 86400); // 24-hour window
            $this->bullets = app(GeminiService::class)->summarize($text);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Something went wrong generating the summary. Please try again.';
        } finally {
            $this->loading = false;
        }
    }
}; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="text-center mb-4">
            <div class="eyebrow mb-2">AI tool — {{ config('services.gemini.daily_limit', 15) }}/day</div>
            <h1 class="h3 fw-bold mb-2">Notes Summarizer</h1>
            <p class="text-secondary">Paste your notes or upload a PDF, get a clean bullet-point summary.</p>
        </div>

        <div class="card mb-4">
            <div class="card-body p-4">
                @if ($errorMessage)
                    <div class="alert alert-warning py-2 small">{{ $errorMessage }}</div>
                @endif

                <div class="mb-3">
                    <x-input-label for="pdf" value="Upload a PDF (optional)" />
                    <input type="file" id="pdf" class="form-control" accept="application/pdf" wire:model="pdf">
                    <div wire:loading wire:target="pdf" class="small text-secondary mt-1">Reading PDF…</div>
                    <x-input-error :messages="$errors->get('pdf')" />
                </div>

                <div class="mb-2">
                    <x-input-label for="inputText" value="Or paste text" />
                    <textarea id="inputText" rows="8" class="form-control" wire:model="inputText"
                              placeholder="Paste lecture notes, an article, or a chapter here…"></textarea>
                    <div class="form-text d-flex justify-content-between">
                        <span>Capped at {{ number_format(self::MAX_CHARS) }} characters (~a few pages).</span>
                        <span>{{ number_format(mb_strlen($inputText)) }} / {{ number_format(self::MAX_CHARS) }}</span>
                    </div>
                </div>

                <button type="button" class="btn btn-primary mt-2" wire:click="summarize" wire:loading.attr="disabled" wire:target="summarize">
                    <span wire:loading.remove wire:target="summarize">Summarize</span>
                    <span wire:loading wire:target="summarize">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span> Summarizing…
                    </span>
                </button>
            </div>
        </div>

        @if (! empty($bullets))
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Summary</h2>
                    <ul class="mb-0">
                        @foreach ($bullets as $bullet)
                            <li class="mb-2">{{ $bullet }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
</div>
