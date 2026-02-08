<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserApiTest extends WebTestCase
{
    public function testGetUsersCollection(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/users', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
    }

    public function testCreateUser(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'email' => "user-{$uid}@example.com",
            'username' => "user-{$uid}",
            'password' => 'TestPassword123!',
            'roles' => ['ROLE_USER'],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('createdAt', $data);
        // Password must not be exposed in response
        $this->assertArrayNotHasKey('password', $data);
    }

    public function testCreateUserWithOptionalFields(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'email' => "full-{$uid}@example.com",
            'username' => "full-{$uid}",
            'password' => 'TestPassword123!',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'roles' => ['ROLE_USER'],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('John', $data['firstName']);
        $this->assertSame('Doe', $data['lastName']);
    }

    public function testCreateUserWithoutRequiredFieldsFails(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'username' => 'nomail',
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateUserWithInvalidEmailFails(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'email' => 'not-an-email',
            'username' => 'bademail',
            'password' => 'TestPassword123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetSingleUser(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'email' => "get-{$uid}@example.com",
            'username' => "get-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $id = $data['id'];

        $client->request('GET', '/api/users/' . $id, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($id, $data['id']);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function testUpdateUser(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'email' => "patch-{$uid}@example.com",
            'username' => "patch-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $id = $data['id'];

        $client->request('PATCH', '/api/users/' . $id, [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'firstName' => 'Updated',
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Updated', $data['firstName']);
    }

    public function testDeleteUser(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'email' => "delete-{$uid}@example.com",
            'username' => "delete-{$uid}",
            'password' => 'TestPassword123!',
        ]));

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $id = $data['id'];

        $client->request('DELETE', '/api/users/' . $id);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testCreateUserWithOrganization(): void
    {
        $client = static::createClient();
        $uid = uniqid();

        // Create organization first
        $client->request('POST', '/api/organizations', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'label' => "User Org {$uid}",
        ]));

        $orgData = json_decode((string) $client->getResponse()->getContent(), true);
        $orgId = $orgData['id'];

        // Create user linked to organization
        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'email' => "orguser-{$uid}@example.com",
            'username' => "orguser-{$uid}",
            'password' => 'TestPassword123!',
            'roles' => ['ROLE_ORG_MEMBER'],
            'organization' => "/api/organizations/{$orgId}",
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertNotNull($data['organization']);
    }
}
