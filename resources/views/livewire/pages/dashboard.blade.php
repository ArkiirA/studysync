<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    //
}; ?>

<div>
    <x-slot:header>
        <h1 class="h4 fw-bold mb-0">Welcome back, {{ auth()->user()->name }}</h1>
    </x-slot:header>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <div class="card tool-card p-1">
                <div class="card-body">
                    <h2 class="h6 fw-bold">GPA Calculator</h2>
                    <p class="text-secondary small mb-3">Jump back into your course list.</p>
                    <a href="{{ route('tools.gpa') }}" class="small fw-semibold" wire:navigate>Open →</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card tool-card p-1">
                <div class="card-body">
                    <h2 class="h6 fw-bold">Citation Generator</h2>
                    <p class="text-secondary small mb-3">Format your next source.</p>
                    <a href="{{ route('tools.citation') }}" class="small fw-semibold" wire:navigate>Open →</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card tool-card p-1">
                <div class="card-body">
                    <h2 class="h6 fw-bold">Study Rooms</h2>
                    <p class="text-secondary small mb-3">Create or join a room for shared tasks and a synced timer.</p>
                    <a href="{{ route('rooms.index') }}" class="small fw-semibold" wire:navigate>Open →</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card tool-card p-1">
                <div class="card-body">
                    <h2 class="h6 fw-bold">Notes Summarizer</h2>
                    <p class="text-secondary small mb-3">Turn a PDF or pasted notes into a bullet-point summary.</p>
                    <a href="{{ route('tools.summarizer') }}" class="small fw-semibold" wire:navigate>Open →</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card tool-card p-1">
                <div class="card-body">
                    <h2 class="h6 fw-bold">Flashcards</h2>
                    <p class="text-secondary small mb-3">Generate flippable Q&amp;A cards and revisit saved sets.</p>
                    <a href="{{ route('tools.flashcards') }}" class="small fw-semibold" wire:navigate>Open →</a>
                </div>
            </div>
        </div>
    </div>
</div>
