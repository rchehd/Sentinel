<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Enum\WorkspaceRole;
use App\Repository\WorkspaceMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthApiTest extends WebTestCase
{
    public function testRegisterUser(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "auth-{$uid}@example.com",
            'username' => "auth-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function testRegisterCreatesWorkspaceMembership(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "ws-{$uid}@example.com",
            'username' => "ws-{$uid}",
            'password' => 'TestPassword123!',
            'workspaceName' => "My Workspace {$uid}",
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => "ws-{$uid}@example.com"]);
        $this->assertNotNull($user);

        /** @var WorkspaceMemberRepository $memberRepo */
        $memberRepo = static::getContainer()->get(WorkspaceMemberRepository::class);
        $members = $memberRepo->findBy(['user' => $user]);

        $this->assertCount(1, $members);
        $this->assertSame(WorkspaceRole::Owner, $members[0]->getRole());
        $this->assertSame("My Workspace {$uid}", $members[0]->getWorkspace()?->getName());
        $this->assertNotNull($members[0]->getWorkspace()?->getSlug());
    }

    public function testRegisterWithoutWorkspaceNameFallsBackToDefault(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "fb-{$uid}@example.com",
            'username' => "fb-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => "fb-{$uid}@example.com"]);
        $this->assertNotNull($user);

        /** @var WorkspaceMemberRepository $memberRepo */
        $memberRepo = static::getContainer()->get(WorkspaceMemberRepository::class);
        $members = $memberRepo->findBy(['user' => $user]);

        $this->assertCount(1, $members);
        $this->assertSame("fb-{$uid}'s workspace", $members[0]->getWorkspace()?->getName());
        $this->assertNotNull($members[0]->getWorkspace()?->getSlug());
    }

    public function testRegistrationDisabledInSelfHostedMode(): void
    {
        $_ENV['APP_MODE'] = 'self_hosted';
        static::ensureKernelShutdown();
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "blocked-{$uid}@example.com",
            'username' => "blocked-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('registration_disabled', $data['code']);

        // Restore
        $_ENV['APP_MODE'] = 'saas';
        static::ensureKernelShutdown();
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'nonexistent@example.com',
            'password' => 'wrong',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('invalid_credentials', $data['code']);
    }

    public function testLoginWithInactiveAccount(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "inactive-{$uid}@example.com",
            'username' => "inactive-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "inactive-{$uid}@example.com",
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('account_not_activated', $data['code']);
    }

    public function testActivateAndLogin(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "activate-{$uid}@example.com",
            'username' => "activate-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => "activate-{$uid}@example.com"]);
        $this->assertNotNull($user);
        $token = $user->getActivationToken();
        $this->assertNotNull($token);

        $client->request('GET', "/api/activate/{$token}");
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "activate-{$uid}@example.com",
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame("activate-{$uid}@example.com", $data['email']);
        $this->assertArrayHasKey('roles', $data);
    }

    public function testActivateWithInvalidToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/activate/invalid-token-123');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testActivateAlreadyActivatedAccount(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "already-{$uid}@example.com",
            'username' => "already-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => "already-{$uid}@example.com"]);
        $token = $user->getActivationToken();

        $client->request('GET', "/api/activate/{$token}");
        $this->assertResponseIsSuccessful();

        $client->request('GET', "/api/activate/{$token}");
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('already_activated', $data['code']);
    }

    public function testLoginResponseContainsUserData(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "data-{$uid}@example.com",
            'username' => "data-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => "data-{$uid}@example.com"]);
        $client->request('GET', '/api/activate/' . $user->getActivationToken());

        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "data-{$uid}@example.com",
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function testSetupStatusReturnsMode(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/setup/status');

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('configured', $data);
        $this->assertArrayHasKey('mode', $data);
        $this->assertSame('saas', $data['mode']);
    }
}
