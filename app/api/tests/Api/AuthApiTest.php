<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
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
            'role' => 'ROLE_USER',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function testRegisterWithOrganization(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "orgauth-{$uid}@example.com",
            'username' => "orgauth-{$uid}",
            'password' => 'TestPassword123!',
            'role' => 'ROLE_ORG_OWNER',
            'organizationLabel' => "Org {$uid}",
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testRegisterOrgOwnerWithoutLabelFails(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "nolabel-{$uid}@example.com",
            'username' => "nolabel-{$uid}",
            'password' => 'TestPassword123!',
            'role' => 'ROLE_ORG_OWNER',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('org_label_required', $data['code']);
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

        // Register (account is inactive by default)
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "inactive-{$uid}@example.com",
            'username' => "inactive-{$uid}",
            'password' => 'TestPassword123!',
            'role' => 'ROLE_USER',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Try to login
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

        // Register
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "activate-{$uid}@example.com",
            'username' => "activate-{$uid}",
            'password' => 'TestPassword123!',
            'role' => 'ROLE_USER',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Get activation token from DB
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => "activate-{$uid}@example.com"]);
        $this->assertNotNull($user);
        $token = $user->getActivationToken();
        $this->assertNotNull($token);

        // Activate
        $client->request('GET', "/api/activate/{$token}");
        $this->assertResponseIsSuccessful();

        // Login should now succeed
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

        // Register
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "already-{$uid}@example.com",
            'username' => "already-{$uid}",
            'password' => 'TestPassword123!',
            'role' => 'ROLE_USER',
        ]));

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => "already-{$uid}@example.com"]);
        $token = $user->getActivationToken();

        // First activation — should succeed
        $client->request('GET', "/api/activate/{$token}");
        $this->assertResponseIsSuccessful();

        // Second activation with same token — should return 409
        $client->request('GET', "/api/activate/{$token}");
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('already_activated', $data['code']);
    }

    public function testLoginResponseContainsUserData(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        // Register + activate
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "data-{$uid}@example.com",
            'username' => "data-{$uid}",
            'password' => 'TestPassword123!',
            'role' => 'ROLE_USER',
        ]));

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => "data-{$uid}@example.com"]);
        $client->request('GET', '/api/activate/' . $user->getActivationToken());

        // Login
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
        // Password must not be in response
        $this->assertArrayNotHasKey('password', $data);
    }
}
