<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('rooms.view');
    }

    public function view(User $user, Room $room): bool
    {
        return $this->viewAny($user);
    }

    public function search(User $user): bool
    {
        return $user->hasAnyPermission(['rooms.search', 'rooms.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('rooms.manage');
    }

    public function update(User $user, Room $room): bool
    {
        return $user->hasPermissionTo('rooms.manage');
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->hasPermissionTo('rooms.manage');
    }
}
