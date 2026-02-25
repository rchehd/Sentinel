<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMember;
use App\Enum\WorkspaceRole;
use PHPUnit\Framework\TestCase;

class WorkspaceMemberTest extends TestCase
{
    public function testDefaultRole(): void
    {
        $member = new WorkspaceMember();

        $this->assertSame(WorkspaceRole::Viewer, $member->getRole());
    }

    public function testSetRole(): void
    {
        $member = new WorkspaceMember();
        $member->setRole(WorkspaceRole::Admin);

        $this->assertSame(WorkspaceRole::Admin, $member->getRole());
    }

    public function testJoinedAtIsSetOnConstruct(): void
    {
        $member = new WorkspaceMember();

        $this->assertInstanceOf(\DateTimeImmutable::class, $member->getJoinedAt());
    }

    public function testMemberBelongsToWorkspaceAndUser(): void
    {
        $workspace = new Workspace();
        $workspace->setName('Test Workspace');

        $user = new User();
        $user->setEmail('user@example.com');
        $user->setUsername('testuser');
        $user->setPassword('password');

        $member = new WorkspaceMember();
        $member->setWorkspace($workspace);
        $member->setUser($user);
        $member->setRole(WorkspaceRole::Owner);

        $this->assertSame($workspace, $member->getWorkspace());
        $this->assertSame($user, $member->getUser());
        $this->assertSame(WorkspaceRole::Owner, $member->getRole());
    }
}
