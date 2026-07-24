<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('auth')->group(function () {
    Volt::route('dashboard', 'pages.dashboard')
        ->middleware('verified')
        ->name('dashboard');

    Volt::route('profile', 'pages.profile')->name('profile');

    Volt::route('rooms', 'pages.rooms.index')->name('rooms.index');
    Volt::route('rooms/{room:code}', 'pages.rooms.show')->name('rooms.show');

    // Phase 4 — AI tools require login (unlike the free Phase 2 utilities).
    Volt::route('tools/summarizer', 'pages.tools.summarizer')->name('tools.summarizer');
    Volt::route('tools/flashcards', 'pages.tools.flashcards')->name('tools.flashcards');
    Volt::route('flashcards', 'pages.flashcards.index')->name('flashcards.index');
    Volt::route('flashcards/{set}', 'pages.flashcards.show')->name('flashcards.show');
});

// Phase 2 utilities — public, no login required.
Volt::route('/tools/gpa', 'pages.tools.gpa')->name('tools.gpa');
Volt::route('/tools/citation', 'pages.tools.citation')->name('tools.citation');

require __DIR__.'/auth.php';
