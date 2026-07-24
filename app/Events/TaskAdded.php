<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// ShouldBroadcastNow (not ShouldBroadcast) on purpose: this dispatches the
// broadcast synchronously, so a room's realtime sync works out of the box
// without needing a `php artisan queue:work` process running alongside
// Reverb — one less moving part for a demo. If this app grows real usage,
// switch to ShouldBroadcast + a queue worker so a slow Reverb connection
// can't block the HTTP request.
class TaskAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Task $task)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('room.' . $this->task->room_id)];
    }

    public function broadcastAs(): string
    {
        return 'TaskAdded';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->task->id,
            'content' => $this->task->content,
            'is_done' => $this->task->is_done,
            'created_by' => $this->task->created_by,
            'creator_name' => $this->task->creator->name,
        ];
    }
}
