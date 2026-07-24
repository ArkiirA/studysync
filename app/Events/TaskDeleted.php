<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $roomId, public int $taskId)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('room.' . $this->roomId)];
    }

    public function broadcastAs(): string
    {
        return 'TaskDeleted';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->taskId];
    }
}
