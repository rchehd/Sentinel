<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Form;
use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMember;
use App\Enum\WorkspaceRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FormApiTest extends WebTestCase
{
    public function testUnauthenticatedCannotListForms(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/forms');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testNonMemberCannotListForms(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $outsider = $this->createActiveUser('x-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($outsider);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/forms');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListForms(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $this->createForm($workspace, $owner, 'Form A');
        $this->createForm($workspace, $owner, 'Form B');
        $client->loginUser($owner);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/forms', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('title', $data[0]);
        $this->assertArrayHasKey('status', $data[0]);
        $this->assertArrayHasKey('currentRevision', $data[0]);
        $this->assertArrayHasKey('createdAt', $data[0]);
    }

    public function testCreateForm(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($owner);

        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/forms', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode([
            'title' => 'Contact Form',
            'description' => 'A simple contact form.',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Contact Form', $data['title']);
        $this->assertSame('A simple contact form.', $data['description']);
        $this->assertSame('draft', $data['status']);
        $this->assertNull($data['currentRevision']);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function testCreateFormWithoutTitleFails(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($owner);

        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/forms', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['description' => 'No title here']));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testViewerCannotCreateForm(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $viewer = $this->createActiveUser('v-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $this->addMemberToWorkspace($workspace, $viewer, WorkspaceRole::Viewer);
        $client->loginUser($viewer);

        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/forms', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['title' => 'Sneaky Form']));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testEditorCanCreateForm(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $editor = $this->createActiveUser('e-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $this->addMemberToWorkspace($workspace, $editor, WorkspaceRole::Editor);
        $client->loginUser($editor);

        $client->request('POST', '/api/workspaces/' . $workspace->getId() . '/forms', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['title' => 'Editor Form']));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testGetSingleForm(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'My Form');
        $client->loginUser($owner);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId());

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame((string) $form->getId(), $data['id']);
        $this->assertSame('My Form', $data['title']);
    }

    public function testCannotGetFormFromOtherWorkspace(): void
    {
        $client = static::createClient();
        $ownerA = $this->createActiveUser('a-' . uniqid());
        $ownerB = $this->createActiveUser('b-' . uniqid());
        $workspaceA = $this->createWorkspaceForUser($ownerA, 'WS A ' . uniqid());
        $workspaceB = $this->createWorkspaceForUser($ownerB, 'WS B ' . uniqid());
        $formB = $this->createForm($workspaceB, $ownerB, 'Form B');
        $client->loginUser($ownerA);

        // Try to access workspace B's form through workspace A's URL
        $client->request('GET', '/api/workspaces/' . $workspaceA->getId() . '/forms/' . $formB->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateForm(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'Old Title');
        $client->loginUser($owner);

        $client->request('PATCH', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'title' => 'New Title',
            'status' => 'published',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('New Title', $data['title']);
        $this->assertSame('published', $data['status']);
        // Metadata-only PATCH does not create a revision; currentRevision stays null.
        $this->assertNull($data['currentRevision']);
    }

    public function testViewerCannotUpdateForm(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $viewer = $this->createActiveUser('v-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'Protected Form');
        $this->addMemberToWorkspace($workspace, $viewer, WorkspaceRole::Viewer);
        $client->loginUser($viewer);

        $client->request('PATCH', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['title' => 'Hacked']));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteForm(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'To Delete');
        $client->loginUser($owner);

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testEditorCannotDeleteForm(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $editor = $this->createActiveUser('e-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'Protected');
        $this->addMemberToWorkspace($workspace, $editor, WorkspaceRole::Editor);
        $client->loginUser($editor);

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanDeleteForm(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $admin = $this->createActiveUser('a-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'Admin Delete');
        $this->addMemberToWorkspace($workspace, $admin, WorkspaceRole::Admin);
        $client->loginUser($admin);

        $client->request('DELETE', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testSaveSchemaCreatesRevision(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'My Form');
        $client->loginUser($owner);

        $schema = ['stages' => [['id' => 'stage-1', 'title' => 'Step 1', 'elements' => []]]];

        $client->request('PUT', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId() . '/schema', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], (string) json_encode(['schema' => $schema]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame(1, $data['version']);
        $this->assertSame($schema, $data['schema']);
        $this->assertArrayHasKey('createdAt', $data);
    }

    public function testSaveSchemaIncrementsVersion(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'My Form');
        $client->loginUser($owner);

        $url = '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId() . '/schema';
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

        $client->request('PUT', $url, [], [], $headers, (string) json_encode(['schema' => ['stages' => []]]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request('PUT', $url, [], [], $headers, (string) json_encode(['schema' => ['stages' => [['id' => 's2']]]]));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(2, $data['version']);
    }

    public function testViewerCannotSaveSchema(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $viewer = $this->createActiveUser('v-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'Protected');
        $this->addMemberToWorkspace($workspace, $viewer, WorkspaceRole::Viewer);
        $client->loginUser($viewer);

        $client->request('PUT', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId() . '/schema', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['schema' => []]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testEditorCanSaveSchema(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $editor = $this->createActiveUser('e-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'Editor Form');
        $this->addMemberToWorkspace($workspace, $editor, WorkspaceRole::Editor);
        $client->loginUser($editor);

        $client->request('PUT', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId() . '/schema', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['schema' => ['stages' => []]]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testListRevisions(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'My Form');
        $client->loginUser($owner);

        $url = '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId();
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

        $client->request('PUT', $url . '/schema', [], [], $headers, (string) json_encode(['schema' => ['stages' => []]]));
        $client->request('PUT', $url . '/schema', [], [], $headers, (string) json_encode(['schema' => ['stages' => [['id' => 's1']]]]));

        $client->request('GET', $url . '/revisions', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertSame(2, $data[0]['version']);
        $this->assertSame(1, $data[1]['version']);
    }

    public function testFormResponseIncludesCurrentRevision(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'My Form');
        $client->loginUser($owner);

        $schema = ['stages' => [['id' => 'stage-1', 'title' => 'Intro', 'elements' => []]]];
        $client->request('PUT', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId() . '/schema', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['schema' => $schema]));

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId(), [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('currentRevision', $data);
        $this->assertSame(1, $data['currentRevision']['version']);
        $this->assertSame($schema, $data['currentRevision']['schema']);
    }

    // -------------------------------------------------------------------------
    // Export / Import tests
    // -------------------------------------------------------------------------

    public function testExportFormJson(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'Export Me JSON');
        $client->loginUser($owner);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId() . '/export?format=json');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString('application/json', (string) $client->getResponse()->headers->get('Content-Type'));
        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('title', $content);
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded);
        $this->assertSame('Export Me JSON', $decoded['title']);
    }

    public function testExportFormYaml(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'Export Me YAML');
        $client->loginUser($owner);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId() . '/export?format=yaml');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('application/yaml', (string) $client->getResponse()->headers->get('Content-Type'));
        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('title:', $content);
        $this->assertStringContainsString('Export Me YAML', $content);
    }

    public function testExportFormInvalidFormat(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'Export Test');
        $client->loginUser($owner);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId() . '/export?format=xml');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('xml', strtolower($data['error']));
    }

    public function testExportFormAttachesFilenameHeader(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'My Export Form');
        $client->loginUser($owner);

        $client->request('GET', '/api/workspaces/' . $workspace->getId() . '/forms/' . $form->getId() . '/export?format=json');

        $this->assertResponseIsSuccessful();
        $disposition = (string) $client->getResponse()->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('.json', $disposition);
    }

    public function testImportFormJson(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($owner);

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/import',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            (string) json_encode([
                'content' => '{"title":"Imported JSON Form","status":"draft"}',
                'format' => 'json',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Imported JSON Form', $data['title']);
    }

    public function testImportFormYaml(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($owner);

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/import',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            (string) json_encode([
                'content' => "title: Imported YAML Form\nstatus: draft\n",
                'format' => 'yaml',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Imported YAML Form', $data['title']);
    }

    public function testImportFormInvalidFormat(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($owner);

        // The DTO has a Choice constraint on format — 'xml' is rejected at the
        // validation layer (422) before the controller even inspects the registry.
        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/import',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'content' => '<form><title>XML</title></form>',
                'format' => 'xml',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testImportFormInvalidJson(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($owner);

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/import',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'content' => 'not json at all {{{',
                'format' => 'json',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Invalid file content', $data['error']);
    }

    public function testImportFormMissingTitle(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($owner);

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/import',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'content' => '{"status":"draft"}',
                'format' => 'json',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('title', $data['error']);
    }

    public function testViewerCannotImportForm(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser('o-' . uniqid());
        $viewer = $this->createActiveUser('v-' . uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $this->addMemberToWorkspace($workspace, $viewer, WorkspaceRole::Viewer);
        $client->loginUser($viewer);

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/import',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'content' => '{"title":"Sneaky Import"}',
                'format' => 'json',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testExportBulkJson(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $formA = $this->createForm($workspace, $owner, 'Bulk A');
        $formB = $this->createForm($workspace, $owner, 'Bulk B');
        $client->loginUser($owner);

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/export-bulk',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            (string) json_encode([
                'ids' => [(string) $formA->getId(), (string) $formB->getId()],
                'format' => 'json',
            ]),
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('application/json', (string) $client->getResponse()->headers->get('Content-Type'));
        $content = (string) $client->getResponse()->getContent();
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded);
        $this->assertArrayHasKey('forms', $decoded);
        $this->assertCount(2, $decoded['forms']);
    }

    public function testExportBulkYaml(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $formA = $this->createForm($workspace, $owner, 'YAML Bulk A');
        $client->loginUser($owner);

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/export-bulk',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'ids' => [(string) $formA->getId()],
                'format' => 'yaml',
            ]),
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('application/yaml', (string) $client->getResponse()->headers->get('Content-Type'));
        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('forms:', $content);
    }

    public function testExportBulkInvalidFormat(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $form = $this->createForm($workspace, $owner, 'Bulk Test');
        $client->loginUser($owner);

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/export-bulk',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'ids' => [(string) $form->getId()],
                'format' => 'xml',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testImportBulkJson(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($owner);

        $bulkContent = json_encode([
            'forms' => [
                ['title' => 'Bulk Import A', 'status' => 'draft'],
                ['title' => 'Bulk Import B', 'status' => 'published'],
            ],
        ], \JSON_THROW_ON_ERROR);

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/import-bulk',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            (string) json_encode([
                'content' => $bulkContent,
                'format' => 'json',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $titles = array_column($data, 'title');
        $this->assertContains('Bulk Import A', $titles);
        $this->assertContains('Bulk Import B', $titles);
    }

    public function testImportBulkMissingFormsKey(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($owner);

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/import-bulk',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'content' => '{"title":"Not a bulk file"}',
                'format' => 'json',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('forms', $data['error']);
    }

    public function testImportBulkYaml(): void
    {
        $client = static::createClient();
        $owner = $this->createActiveUser(uniqid());
        $workspace = $this->createWorkspaceForUser($owner, 'WS ' . uniqid());
        $client->loginUser($owner);

        $bulkYaml = "forms:\n  - title: YAML Bulk A\n    status: draft\n  - title: YAML Bulk B\n    status: draft\n";

        $client->request(
            'POST',
            '/api/workspaces/' . $workspace->getId() . '/forms/import-bulk',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            (string) json_encode([
                'content' => $bulkYaml,
                'format' => 'yaml',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createActiveUser(string $suffix): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail("form-{$suffix}@example.com");
        $user->setUsername("form-{$suffix}");
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

    private function addMemberToWorkspace(Workspace $workspace, User $user, WorkspaceRole $role): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $member = new WorkspaceMember();
        $member->setUser($user);
        $member->setRole($role);
        $workspace->addMember($member);

        $em->flush();
    }

    private function createForm(Workspace $workspace, User $author, string $title): Form
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $form = new Form();
        $form->setWorkspace($workspace);
        $form->setCreatedBy($author);
        $form->setTitle($title);

        $em->persist($form);
        $em->flush();

        return $form;
    }
}
