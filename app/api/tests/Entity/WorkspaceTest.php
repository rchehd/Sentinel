<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMember;
use App\Enum\WorkspaceRole;
use PHPUnit\Framework\TestCase;

class WorkspaceTest extends TestCase
{
    public function testCreateWorkspace(): void
    {
        $workspace = new Workspace();
        $workspace->setName('Test Workspace');
        $workspace->setSlug('test-workspace');

        $this->assertSame('Test Workspace', $workspace->getName());
        $this->assertSame('test-workspace', $workspace->getSlug());
        $this->assertNull($workspace->getId());
    }

    public function testSlugIsNullable(): void
    {
        $workspace = new Workspace();
        $workspace->setName('No Slug Workspace');

        $this->assertNull($workspace->getSlug());
    }

    public function testAddAndRemoveMembers(): void
    {
        $workspace = new Workspace();
        $workspace->setName('Test Workspace');

        $user = new User();
        $user->setEmail('member@example.com');
        $user->setUsername('member');
        $user->setPassword('password');

        $member = new WorkspaceMember();
        $member->setUser($user);
        $member->setRole(WorkspaceRole::Editor);

        $workspace->addMember($member);

        $this->assertCount(1, $workspace->getMembers());
        $this->assertSame($workspace, $member->getWorkspace());

        $workspace->removeMember($member);

        $this->assertCount(0, $workspace->getMembers());
    }

    public function testAddMemberIsIdempotent(): void
    {
        $workspace = new Workspace();
        $workspace->setName('Test Workspace');

        $user = new User();
        $user->setEmail('member@example.com');
        $user->setUsername('member');
        $user->setPassword('password');

        $member = new WorkspaceMember();
        $member->setUser($user);
        $member->setRole(WorkspaceRole::Admin);

        $workspace->addMember($member);
        $workspace->addMember($member);

        $this->assertCount(1, $workspace->getMembers());
    }
}
