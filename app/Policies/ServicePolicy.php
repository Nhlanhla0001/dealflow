<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSeller();
    }

    public function view(User $user, Service $service): bool
    {
        return $service->isPublished()
            || $service->seller_id === $user->id
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSeller();
    }

    public function update(User $user, Service $service): bool
    {
        return $service->seller_id === $user->id || $user->isAdmin();
    }

    public function delete(User $user, Service $service): bool
    {
        return $service->seller_id === $user->id || $user->isAdmin();
    }

    public function publish(User $user, Service $service): bool
    {
        return $service->seller_id === $user->id || $user->isAdmin();
    }
}
