<?php

namespace App\Policies;

use App\Models\SectionRequest;
use App\Models\User;

class SectionRequestPolicy
{

    /**
     * Determine if the given user can update the section request.
     */
    public function update(User $user, SectionRequest $sectionRequest)
    {
        return $user->id === $sectionRequest->requester_id || $user->hasRole('admin') || $user->hasRole('moderator');
    }

    /**
     * Determine if the given user can delete the section request.
     */
    public function delete(User $user, SectionRequest $sectionRequest)
    {
        return $user->id === $sectionRequest->requester_id || $user->hasRole('admin') || $user->hasRole('moderator');
    }
}
