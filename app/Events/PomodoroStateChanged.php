<?php

namespace App\Events;

use App\Models\PomodoroSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PomodoroStateChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PomodoroSession $session)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('room.' . $this->session->room_id)];
    }

    public function broadcastAs(): string
    {
        return 'PomodoroStateChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'status' => $this->session->status,
            // ISO 8601 string — the client parses this and computes remaining
            // time itself. The server never ticks a clock (see room page).
            'started_at' => $this->session->started_at?->toIso8601String(),
            'duration_seconds' => $this->session->duration_seconds,
            'elapsed_before_pause' => $this->session->elapsed_before_pause,
        ];
    }
}
