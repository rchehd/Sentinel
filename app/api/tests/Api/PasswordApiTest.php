<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordApiTest extends WebTestCase
{
    public function testUnauthenticatedCannotChangePassword(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/password/change', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'newPassword' => 'NewPassword1!',
            'confirmPassword' => 'NewPassword1!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testForcedChangeRequiresNoCurrentPassword(): void
    {
        $client = static::createClient();
        $user = $this->createUser(uniqid(), true);
        $client->loginUser($user);

        $client->request('POST', '/api/password/change', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'newPassword' => 'NewPassword1!',
            'confirmPassword' => 'NewPassword1!',
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function testForcedChangeClearsMustChangePasswordFlag(): void
    {
        $client = static::createClient();
        $user = $this->createUser(uniqid(), true);
        $client->loginUser($user);

        $client->request('POST', '/api/password/change', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'newPassword' => 'NewPassword1!',
            'confirmPassword' => 'NewPassword1!',
        ]));

        $this->assertResponseIsSuccessful();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->refresh($user);
        $this->assertFalse($user->isMustChangePassword());
    }

    public function testPasswordMismatchFails(): void
    {
        $client = static::createClient();
        $user = $this->createUser(uniqid(), true);
        $client->loginUser($user);

        $client->request('POST', '/api/password/change', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'newPassword' => 'NewPassword1!',
            'confirmPassword' => 'DifferentPassword1!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('password_mismatch', $data['code']);
    }

    public function testVoluntaryChangeMissingCurrentPasswordFails(): void
    {
        $client = static::createClient();
        $user = $this->createUser(uniqid(), false);
        $client->loginUser($user);

        $client->request('POST', '/api/password/change', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'newPassword' => 'NewPassword1!',
            'confirmPassword' => 'NewPassword1!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('current_password_required', $data['code']);
    }

    public function testVoluntaryChangeWrongCurrentPasswordFails(): void
    {
        $client = static::createClient();
        $user = $this->createUser(uniqid(), false);
        $client->loginUser($user);

        $client->request('POST', '/api/password/change', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'currentPassword' => 'WrongPassword!',
            'newPassword' => 'NewPassword1!',
            'confirmPassword' => 'NewPassword1!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('invalid_current_password', $data['code']);
    }

    public function testVoluntaryChangeWithCorrectCurrentPassword(): void
    {
        $client = static::createClient();
        $user = $this->createUser(uniqid(), false, 'OldPassword1!');
        $client->loginUser($user);

        $client->request('POST', '/api/password/change', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'currentPassword' => 'OldPassword1!',
            'newPassword' => 'NewPassword1!',
            'confirmPassword' => 'NewPassword1!',
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testLoginResponseIncludesMustChangePassword(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        // Register + activate
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "pw-{$uid}@example.com",
            'username' => "pw-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => "pw-{$uid}@example.com"]);
        $client->request('GET', '/api/activate/' . $user->getActivationToken());

        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => "pw-{$uid}@example.com",
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('mustChangePassword', $data);
        $this->assertFalse($data['mustChangePassword']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createUser(string $suffix, bool $mustChangePassword, string $plainPassword = 'TestPassword123!'): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail("pw-{$suffix}@example.com");
        $user->setUsername("pw-{$suffix}");
        $user->setIsActive(true);
        $user->setMustChangePassword($mustChangePassword);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));

        $em->persist($user);
        $em->flush();

        return $user;
    }
}
