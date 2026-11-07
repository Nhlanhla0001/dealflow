<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WizardDraft;

class WizardDraftPolicy
{
    public function view(User $user, WizardDraft $draft): bool
    {
        return $draft->isOwnedBy($user);
    }

    public function update(User $user, WizardDraft $draft): bool
    {
        return $draft->isOwnedBy($user);
    }

    public function delete(User $user, WizardDraft $draft): bool
    {
        return $draft->isOwnedBy($user);
    }

    public function publish(User $user, WizardDraft $draft): bool
    {
        return $draft->isOwnedBy($user);
    }
}
