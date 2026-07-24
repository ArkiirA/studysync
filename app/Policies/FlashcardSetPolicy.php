<?php

namespace App\Policies;

use App\Models\FlashcardSet;
use App\Models\User;

class FlashcardSetPolicy
{
    public function view(User $user, FlashcardSet $set): bool
    {
        return $set->user_id === $user->id;
    }

    public function delete(User $user, FlashcardSet $set): bool
    {
        return $set->user_id === $user->id;
    }
}
