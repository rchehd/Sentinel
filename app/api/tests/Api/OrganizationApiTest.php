<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OrganizationApiTest extends WebTestCase
{
    public function testGetOrganizationsCollection(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/organizations', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
    }

    public function testCreateOrganization(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/organizations', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'label' => 'Test Organization ' . uniqid(),
            'domain' => 'test-' . uniqid() . '.com',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('label', $data);
        $this->assertArrayHasKey('domain', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('updatedAt', $data);
    }

    public function testCreateOrganizationWithoutLabelFails(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/organizations', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'domain' => 'no-label.com',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetSingleOrganization(): void
    {
        $client = static::createClient();

        // Create first
        $client->request('POST', '/api/organizations', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'label' => 'Get Test Org ' . uniqid(),
        ]));

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $id = $data['id'];

        // Fetch it
        $client->request('GET', '/api/organizations/' . $id, [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($id, $data['id']);
    }

    public function testUpdateOrganization(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/organizations', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'label' => 'Update Test Org ' . uniqid(),
        ]));

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $id = $data['id'];

        $newLabel = 'Updated Org ' . uniqid();
        $client->request('PATCH', '/api/organizations/' . $id, [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'label' => $newLabel,
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($newLabel, $data['label']);
    }

    public function testDeleteOrganization(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/organizations', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'label' => 'Delete Test Org ' . uniqid(),
        ]));

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $id = $data['id'];

        $client->request('DELETE', '/api/organizations/' . $id);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }
}
