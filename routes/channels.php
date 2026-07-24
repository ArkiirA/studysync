<?php

use App\Models\Room;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    $room = Room::find($roomId);

    if (! $room) {
        return false;
    }

    return $room->members()->where('users.id', $user->id)->exists();
});
