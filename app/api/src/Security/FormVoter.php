<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Form;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Form>
 */
class FormVoter extends Voter
{
    public const string VIEW = 'form_view';
    public const string EDIT = 'form_edit';
    public const string DELETE = 'form_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Form;
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

        $workspace = $subject->getWorkspace();

        if (null === $workspace) {
            return false;
        }

        $role = $this->getMemberRole($user, $workspace);

        return match ($attribute) {
            self::VIEW => null !== $role,
            self::EDIT => \in_array($role, [WorkspaceRole::Owner, WorkspaceRole::Admin, WorkspaceRole::Editor], true),
            self::DELETE => \in_array($role, [WorkspaceRole::Owner, WorkspaceRole::Admin], true),
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
