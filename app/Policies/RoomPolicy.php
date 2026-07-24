<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    /** Any member can view the room (task list, pomodoro state). */
    public function view(User $user, Room $room): bool
    {
        return $room->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Any member can update room state (add/check tasks, start/pause the
     * timer) — v1 has no host/member role distinction, by design.
     */
    public function update(User $user, Room $room): bool
    {
        return $this->view($user, $room);
    }
}
