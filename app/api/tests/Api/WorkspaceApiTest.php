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

class WorkspaceApiTest extends WebTestCase
{
    public function testUnauthenticatedCannotListWorkspaces(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/workspaces');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListWorkspacesReturnsOwnWorkspaces(): void
    {
        $client = static::createClient();
        $user = $this->createActiveUser(uniqid());
        $this->createWorkspaceForUser($user, 'My Workspace ' . uniqid());
        $client->loginUser($user);

        $client->request('GET', '/api/workspaces', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('createdAt', $data[0]);
    }

    public function testListWorkspacesDoesNotReturnOtherUsersWorkspaces(): void
    {
        $client = static::createClient();
        $userA = $this->createActiveUser('a-' . uniqid());
        $userB = $this->createActiveUser('b-' . uniqid());
        $this->createWorkspaceForUser($userA, 'A Workspace ' . uniqid());
        $client->loginUser($userB);

        $client->request('GET', '/api/workspaces', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(0, $data);
    }

    public function testCreateWorkspace(): void
    {
        $client = static::createClient();
        $user = $this->createActiveUser(uniqid());
        $client->loginUser($user);

        $client->request('POST', '/api/workspaces', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'name' => 'Test Workspace ' . uniqid(),
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('updatedAt', $data);
    }

    public function testCreateWorkspaceWithoutNameFails(): void
    {
        $client = static::createClient();
        $user = $this->createActiveUser(uniqid());
        $client->loginUser($user);

        $client->request('POST', '/api/workspaces', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'slug' => 'no-name',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetSingleWorkspace(): void
    {
        $client = static::createClient();
        $user = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($user, 'Get Test ' . uniqid());
        $client->loginUser($user);

        $client->request('GET', '/api/workspaces/' . $workspace->getId(), [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame((string) $workspace->getId(), $data['id']);
    }

    public function testCannotViewOtherUserWorkspace(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('owner-' . uniqid());
        $visitor = $this->createActiveUser('visitor-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Private ' . uniqid());
        $client->loginUser($visitor);

        $client->request('GET', '/api/workspaces/' . $workspace->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateWorkspace(): void
    {
        $client = static::createClient();
        $user = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($user, 'Update Test ' . uniqid());
        $client->loginUser($user);

        $newName = 'Updated Workspace ' . uniqid();
        $client->request('PATCH', '/api/workspaces/' . $workspace->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'name' => $newName,
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($newName, $data['name']);
    }

    public function testNonAdminCannotUpdateWorkspace(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('owner-' . uniqid());
        $viewer = $this->createActiveUser('viewer-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'Protected ' . uniqid());
        $this->addMemberToWorkspace($workspace, $viewer, WorkspaceRole::Viewer);
        $client->loginUser($viewer);

        $client->request('PATCH', '/api/workspaces/' . $workspace->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['name' => 'Hacked']));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteWorkspace(): void
    {
        $client = static::createClient();
        $user = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($user, 'Delete Test ' . uniqid());
        $client->loginUser($user);

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testNonOwnerCannotDeleteWorkspace(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('owner-' . uniqid());
        $admin = $this->createActiveUser('admin-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'ND Workspace ' . uniqid());
        $this->addMemberToWorkspace($workspace, $admin, WorkspaceRole::Admin);
        $client->loginUser($admin);

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createActiveUser(string $suffix): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail("ws-{$suffix}@example.com");
        $user->setUsername("ws-{$suffix}");
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
