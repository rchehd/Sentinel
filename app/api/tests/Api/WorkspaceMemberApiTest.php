<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMember;
use App\Enum\WorkspaceRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class WorkspaceMemberApiTest extends WebTestCase
{
    public function testListMembers(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('owner-' . uniqid());
        $member = $this->createActiveUser('member-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Members Test ' . uniqid());
        $this->addMemberToWorkspace($workspace, $member, WorkspaceRole::Editor);
        $client->loginUser($owner);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/members');

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('userId', $data[0]);
        $this->assertArrayHasKey('username', $data[0]);
        $this->assertArrayHasKey('role', $data[0]);
        $this->assertArrayHasKey('joinedAt', $data[0]);
    }

    public function testNonMemberCannotListMembers(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $outsider = $this->createActiveUser('x-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Private ' . uniqid());
        $client->loginUser($outsider);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/members');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAddMember(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $newMember = $this->createActiveUser('nm-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Add Member ' . uniqid());
        $client->loginUser($owner);

        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/members', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'userId' => (string) $newMember->getId(),
            'role' => 'editor',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame((string) $newMember->getId(), $data['userId']);
        $this->assertSame('editor', $data['role']);
    }

    public function testAddMemberWithInvalidRoleFails(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $newMember = $this->createActiveUser('nm-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Add Bad Role ' . uniqid());
        $client->loginUser($owner);

        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/members', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'userId' => (string) $newMember->getId(),
            'role' => 'superuser',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testAddDuplicateMemberFails(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $existing = $this->createActiveUser('ex-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Dup Test ' . uniqid());
        $this->addMemberToWorkspace($workspace, $existing, WorkspaceRole::Viewer);
        $client->loginUser($owner);

        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/members', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'userId' => (string) $existing->getId(),
            'role' => 'viewer',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testViewerCannotAddMember(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $viewer = $this->createActiveUser('v-' . uniqid());
        $newUser = $this->createActiveUser('nu-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Viewer Test ' . uniqid());
        $this->addMemberToWorkspace($workspace, $viewer, WorkspaceRole::Viewer);
        $client->loginUser($viewer);

        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/members', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'userId' => (string) $newUser->getId(),
            'role' => 'viewer',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateMemberRole(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $editor = $this->createActiveUser('e-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Role Update ' . uniqid());
        $member = $this->addMemberToWorkspace($workspace, $editor, WorkspaceRole::Editor);
        $client->loginUser($owner);

        $client->request('PATCH', '/api/workspaces/' . $workspace->getId() . '/members/' . $member->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['role' => 'admin']));

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('admin', $data['role']);
    }

    public function testCannotDemoteLastOwner(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Last Owner ' . uniqid());
        $ownerMember = $workspace->getMembers()->first();
        $client->loginUser($owner);

        $client->request('PATCH', '/api/workspaces/' . $workspace->getId() . '/members/' . $ownerMember->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['role' => 'admin']));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRemoveMember(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $editor = $this->createActiveUser('e-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Remove Test ' . uniqid());
        $member = $this->addMemberToWorkspace($workspace, $editor, WorkspaceRole::Editor);
        $client->loginUser($owner);

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/members/' . $member->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testMemberCanLeaveWorkspace(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $editor = $this->createActiveUser('e-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Leave Test ' . uniqid());
        $member = $this->addMemberToWorkspace($workspace, $editor, WorkspaceRole::Editor);
        $client->loginUser($editor);

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/members/' . $member->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testCannotRemoveLastOwner(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Last Owner Remove ' . uniqid());
        $ownerMember = $workspace->getMembers()->first();
        $client->loginUser($owner);

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/members/' . $ownerMember->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createActiveUser(string $suffix): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail("wm-{$suffix}@example.com");
        $user->setUsername("wm-{$suffix}");
        $user->setIsActive(true);
        $user->setPassword($hasher->hashPassword($user, 'TestPassword123!'));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createWorkspaceForUser(User $user, string $name): Workspace
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $workspace = new Workspace();
        $workspace->setName($name);

        $member = new WorkspaceMember();
        $member->setUser($user);
        $member->setRole(WorkspaceRole::Owner);
        $workspace->addMember($member);

        $em->persist($workspace);
        $em->flush();

        return $workspace;
    }

    private function addMemberToWorkspace(Workspace $workspace, User $user, WorkspaceRole $role): WorkspaceMember
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $member = new WorkspaceMember();
        $member->setUser($user);
        $member->setRole($role);
        $workspace->addMember($member);

        $em->flush();

        return $member;
    }
}
