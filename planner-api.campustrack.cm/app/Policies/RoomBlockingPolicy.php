<?php

namespace App\Policies;

use App\Models\RoomBlocking;
use App\Models\User;

class RoomBlockingPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['rooms.view', 'rooms.block']);
    }

    public function view(User $user, RoomBlocking $roomBlocking): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('rooms.block');
    }

    public function update(User $user, RoomBlocking $roomBlocking): bool
    {
        return $user->hasPermissionTo('rooms.block');
    }

    public function delete(User $user, RoomBlocking $roomBlocking): bool
    {
        return $user->hasPermissionTo('rooms.block');
    }
}
