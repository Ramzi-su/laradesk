<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * An attachment is visible to whoever can see its ticket. A soft-deleted
     * ticket is not loaded by the relation, so its files are admin-only.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        return $user->isAdmin()
            || ($attachment->ticket !== null && $user->can('view', $attachment->ticket));
    }
}
