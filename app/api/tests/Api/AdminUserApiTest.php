<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminUserApiTest extends WebTestCase
{
    public function testUnauthenticatedCannotListUsers(): void
    {
        $client = static::createClient();
        // Ensure at least one user exists so SetupWizardSubscriber doesn't intercept
        $this->createActiveUser(uniqid());

        $client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegularUserCannotListAdminUsers(): void
    {
        $client = static::createClient();
        $user = $this->createActiveUser(uniqid());
        $client->loginUser($user);

        $client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testSuperAdminCanListUsers(): void
    {
        $client = static::createClient();
        $admin = $this->createSuperAdmin(uniqid());
        $client->loginUser($admin);

        $client->request('GET', '/api/admin/users', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testSuperAdminCanCreateUser(): void
    {
        $client = static::createClient();
        $admin = $this->createSuperAdmin(uniqid());
        $client->loginUser($admin);
        $uid = uniqid();

        $client->request('POST', '/api/admin/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "admin-created-{$uid}@example.com",
            'username' => "admin-created-{$uid}",
            'password' => 'TestPassword123!',
            'mustChangePassword' => true,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame("admin-created-{$uid}@example.com", $data['email']);
        $this->assertTrue($data['mustChangePassword']);
        $this->assertArrayNotHasKey('generatedPassword', $data);
    }

    public function testCreateUserWithoutPasswordGeneratesOne(): void
    {
        $client = static::createClient();
        $admin = $this->createSuperAdmin(uniqid());
        $client->loginUser($admin);
        $uid = uniqid();

        $client->request('POST', '/api/admin/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "gen-{$uid}@example.com",
            'username' => "gen-{$uid}",
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('generatedPassword', $data);
        $this->assertNotEmpty($data['generatedPassword']);
        $this->assertTrue($data['mustChangePassword']); // default true
    }

    public function testCreatedUserIsActiveWithoutEmailVerification(): void
    {
        $client = static::createClient();
        $admin = $this->createSuperAdmin(uniqid());
        $client->loginUser($admin);
        $uid = uniqid();

        $client->request('POST', '/api/admin/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "active-{$uid}@example.com",
            'username' => "active-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => "active-{$uid}@example.com"]);
        $this->assertNotNull($user);
        $this->assertTrue($user->isActive());
    }

    public function testCreateUserWithInvalidEmailFails(): void
    {
        $client = static::createClient();
        $admin = $this->createSuperAdmin(uniqid());
        $client->loginUser($admin);

        $client->request('POST', '/api/admin/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'not-an-email',
            'username' => 'baduser',
            'password' => 'TestPassword123!',
        ]));

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
        $user->setEmail("adm-{$suffix}@example.com");
        $user->setUsername("adm-{$suffix}");
        $user->setIsActive(true);
        $user->setPassword($hasher->hashPassword($user, 'TestPassword123!'));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createSuperAdmin(string $suffix): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail("superadmin-{$suffix}@example.com");
        $user->setUsername("superadmin-{$suffix}");
        $user->setIsActive(true);
        $user->setRoles([UserRole::SuperAdmin->value]);
        $user->setPassword($hasher->hashPassword($user, 'TestPassword123!'));

        $em->persist($user);
        $em->flush();

        return $user;
    }
}
