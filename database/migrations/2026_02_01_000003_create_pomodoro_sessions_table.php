<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pomodoro_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            // 'idle' | 'running' | 'paused'
            $table->string('status')->default('idle');
            // When the current run started. Combined with duration_seconds,
            // clients compute remaining time locally — see PomodoroTimer note
            // in the room page. Null while idle/paused.
            $table->timestamp('started_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(1500); // 25 min
            // How much time was already consumed before the most recent pause,
            // so resuming doesn't reset the countdown.
            $table->unsignedInteger('elapsed_before_pause')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pomodoro_sessions');
    }
};
