<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration tests for SetupController (GET /api/setup/status, POST /api/setup/admin).
 *
 * Test order matters here: tests 2-4 require an empty user table so the
 * SetupWizardSubscriber lets requests through to the endpoint. Test 4 creates
 * the first admin, restoring count > 0 so tests 5-6 can verify the
 * "already configured" behaviour.
 */
class SetupApiTest extends WebTestCase
{
    // -------------------------------------------------------------------------
    // GET /api/setup/status
    // -------------------------------------------------------------------------

    /**
     * /api/setup/status is a setup path, so the subscriber always lets it
     * through regardless of whether users exist.
     */
    public function testSetupStatusIsAlwaysAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/setup/status');

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('configured', $data);
        $this->assertArrayHasKey('mode', $data);
    }

    // -------------------------------------------------------------------------
    // POST /api/setup/admin — validation (requires 0 users so subscriber passes)
    // -------------------------------------------------------------------------

    /**
     * Purges the user table so the SetupWizardSubscriber lets POST /api/setup/admin
     * through. Subsequent tests in this class rely on this purge.
     */
    public function testSetupAdminRejectsBlankPayload(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();

        $client->request('POST', '/api/setup/admin', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{}');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testSetupAdminRejectsInvalidEmail(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/setup/admin', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'not-an-email',
            'username' => 'admin',
            'password' => 'Password123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testSetupAdminRejectsShortPassword(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/setup/admin', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'admin@example.com',
            'username' => 'admin',
            'password' => 'short',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testSetupAdminRejectsShortUsername(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/setup/admin', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'admin@example.com',
            'username' => 'ab',
            'password' => 'Password123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // -------------------------------------------------------------------------
    // POST /api/setup/admin — success (still 0 users from purge above)
    // -------------------------------------------------------------------------

    public function testSetupAdminCreatesFirstAdmin(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/setup/admin', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'first-admin@example.com',
            'username' => 'firstadmin',
            'password' => 'Password123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Admin account created.', $data['message']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'first-admin@example.com']);
        $this->assertNotNull($user);
        $this->assertTrue($user->isActive());
        $this->assertContains(UserRole::SuperAdmin->value, $user->getRoles());
        $this->assertNull($user->getActivationToken());
    }

    // -------------------------------------------------------------------------
    // POST /api/setup/admin — already configured (1 user now exists)
    // -------------------------------------------------------------------------

    public function testSetupAdminBlockedWhenAlreadyConfigured(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/setup/admin', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'second-admin@example.com',
            'username' => 'secondadmin',
            'password' => 'Password123!',
        ]));

        // SetupWizardSubscriber returns 403 when users already exist
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // -------------------------------------------------------------------------
    // GET /api/setup/status — configured state
    // -------------------------------------------------------------------------

    public function testSetupStatusReturnsTrueWhenConfigured(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/setup/status');

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertTrue($data['configured']);
    }
}
