<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Workspace>
 */
class WorkspaceVoter extends Voter
{
    public const string VIEW = 'workspace_view';
    public const string EDIT = 'workspace_edit';
    public const string MANAGE_MEMBERS = 'workspace_manage_members';
    public const string DELETE = 'workspace_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::MANAGE_MEMBERS, self::DELETE], true)
            && $subject instanceof Workspace;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (\in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $role = $this->getMemberRole($user, $subject);

        return match ($attribute) {
            self::VIEW => null !== $role,
            self::EDIT, self::MANAGE_MEMBERS => \in_array($role, [WorkspaceRole::Owner, WorkspaceRole::Admin], true),
            self::DELETE => WorkspaceRole::Owner === $role,
            default => false,
        };
    }

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
