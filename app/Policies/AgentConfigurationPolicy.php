<?php

namespace App\Policies;

use App\Models\AgentConfiguration;
use App\Models\User;

class AgentConfigurationPolicy
{
    public function view(User $user, AgentConfiguration $configuration): bool
    {
        return $configuration->user_id === $user->id;
    }

    public function update(User $user, AgentConfiguration $configuration): bool
    {
        return $configuration->user_id === $user->id;
    }

    public function delete(User $user, AgentConfiguration $configuration): bool
    {
        return $configuration->user_id === $user->id;
    }
}
