<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;

trait MemberRoleTrait
{
    private function getMemberRole(User $user, Workspace $workspace): ?WorkspaceRole
    {
        foreach ($workspace->getMembers() as $member) {
            if ((string) $member->getUser()?->getId() === (string) $user->getId()) {
                return $member->getRole();
            }
        }

        return null;
    }
}
