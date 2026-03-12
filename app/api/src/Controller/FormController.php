<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateFormRequest;
use App\Dto\ImportFormRequest;
use App\Dto\SaveSchemaRequest;
use App\Dto\UpdateFormRequest;
use App\Entity\Form;
use App\Entity\FormRevision;
use App\Entity\User;
use App\Enum\FormStatus;
use App\Repository\FormRepository;
use App\Repository\FormRevisionRepository;
use App\Repository\WorkspaceRepository;
use App\Security\FormVoter;
use App\Security\WorkspaceVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

#[Route('/api/workspaces/{workspaceId}/forms')]
class FormController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkspaceRepository $workspaceRepository,
        private readonly FormRepository $formRepository,
        private readonly FormRevisionRepository $formRevisionRepository,
    ) {
    }

    #[Route('', name: 'api_forms_list', methods: ['GET'])]
    public function index(string $workspaceId, #[CurrentUser] User $user): JsonResponse
    {
        $workspace = $this->workspaceRepository->find($workspaceId);

        if (null === $workspace) {
            return $this->json(['error' => 'Workspace not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('workspace_view', $workspace);

        $forms = $this->formRepository->findByWorkspace($workspace);

        return $this->json($forms, context: ['groups' => ['form:read']]);
    }

    #[Route('', name: 'api_forms_create', methods: ['POST'])]
    public function create(
        string $workspaceId,
        #[CurrentUser] User $user,
        #[MapRequestPayload] CreateFormRequest $dto,
    ): JsonResponse {
        $workspace = $this->workspaceRepository->find($workspaceId);

        if (null === $workspace) {
            return $this->json(['error' => 'Workspace not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(WorkspaceVoter::FORM_CREATE, $workspace);

        $form = new Form();
        $form->setWorkspace($workspace);
        $form->setCreatedBy($user);
        $form->setTitle($dto->title);

        if (null !== $dto->description) {
            $form->setDescription($dto->description);
        }

        $this->em->persist($form);
        $this->em->flush();

        return $this->json($form, Response::HTTP_CREATED, [], ['groups' => ['form:read']]);
    }

    #[Route('/{formId}', name: 'api_forms_show', methods: ['GET'])]
    public function show(string $workspaceId, string $formId): JsonResponse
    {
        $form = $this->findFormInWorkspace($workspaceId, $formId);

        if ($form instanceof JsonResponse) {
            return $form;
        }

        $this->denyAccessUnlessGranted(FormVoter::VIEW, $form);

        return $this->json($form, context: ['groups' => ['form:read']]);
    }

    #[Route('/{formId}', name: 'api_forms_update', methods: ['PATCH'])]
    public function update(
        string $workspaceId,
        string $formId,
        #[MapRequestPayload] UpdateFormRequest $dto,
    ): JsonResponse {
        $form = $this->findFormInWorkspace($workspaceId, $formId);

        if ($form instanceof JsonResponse) {
            return $form;
        }

        $this->denyAccessUnlessGranted(FormVoter::EDIT, $form);

        if (null !== $dto->title) {
            $form->setTitle($dto->title);
        }

        if (null !== $dto->description) {
            $form->setDescription($dto->description);
        }

        if (null !== $dto->status) {
            $form->setStatus($dto->status);
        }

        // Metadata changes (title, description, status) do not create a new revision.
        // Revisions are only created when the form schema changes (see saveSchema).
        $this->em->flush();

        return $this->json($form, context: ['groups' => ['form:read']]);
    }

    #[Route('/{formId}', name: 'api_forms_delete', methods: ['DELETE'])]
    public function delete(string $workspaceId, string $formId): JsonResponse
    {
        $form = $this->findFormInWorkspace($workspaceId, $formId);

        if ($form instanceof JsonResponse) {
            return $form;
        }

        $this->denyAccessUnlessGranted(FormVoter::DELETE, $form);

        $this->em->remove($form);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{formId}/schema', name: 'api_forms_save_schema', methods: ['PUT'])]
    public function saveSchema(
        string $workspaceId,
        string $formId,
        #[CurrentUser] User $user,
        #[MapRequestPayload] SaveSchemaRequest $dto,
    ): JsonResponse {
        $form = $this->findFormInWorkspace($workspaceId, $formId);

        if ($form instanceof JsonResponse) {
            return $form;
        }

        $this->denyAccessUnlessGranted(FormVoter::EDIT, $form);

        $revision = new FormRevision();
        $revision->setForm($form);
        $revision->setSchema($dto->schema);
        $revision->setVersion($this->formRevisionRepository->getNextVersion($form));
        $revision->setCreatedBy($user);
        $revision->setTitle($form->getTitle());
        $revision->setDescription($form->getDescription());
        $revision->setStatus($form->getStatus());

        $this->em->persist($revision);
        $form->setCurrentRevision($revision);
        $this->em->flush();

        return $this->json($revision, Response::HTTP_CREATED, [], ['groups' => ['form:revision:read']]);
    }

    #[Route('/{formId}/revisions', name: 'api_forms_revisions', methods: ['GET'])]
    public function revisions(string $workspaceId, string $formId): JsonResponse
    {
        $form = $this->findFormInWorkspace($workspaceId, $formId);

        if ($form instanceof JsonResponse) {
            return $form;
        }

        $this->denyAccessUnlessGranted(FormVoter::VIEW, $form);

        $revisions = $this->formRevisionRepository->findByForm($form);

        return $this->json($revisions, context: ['groups' => ['form:revision:read']]);
    }

    /**
     * Export a form as a JSON or YAML file download.
     *
     * Returns a structured document suitable for re-import on any instance.
     * The `stages` key is empty until the form editor is implemented, but its
     * presence ensures forward-compatibility with future schema exports.
     */
    #[Route('/{formId}/export', name: 'api_forms_export', methods: ['GET'])]
    public function export(string $workspaceId, string $formId, Request $request): Response
    {
        $form = $this->findFormInWorkspace($workspaceId, $formId);

        if ($form instanceof JsonResponse) {
            return $form;
        }

        $this->denyAccessUnlessGranted(FormVoter::VIEW, $form);

        $format = $request->query->getString('format', 'json');

        if (!\in_array($format, ['json', 'yaml'], true)) {
            return $this->json(['error' => 'Invalid format. Use json or yaml.'], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->buildExportPayload($form);
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($form->getTitle())) ?: 'form';

        if ('yaml' === $format) {
            $body = Yaml::dump($data, 4, 2);
            $mime = 'application/yaml';
            $filename = $slug . '.yaml';
        } else {
            $body = json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE) ?: '{}';
            $mime = 'application/json';
            $filename = $slug . '.json';
        }

        return new Response($body, Response::HTTP_OK, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Import a form from a JSON or YAML string, creating a new Form entity.
     *
     * The payload must contain the `content` (raw file text) and `format`
     * ('json' or 'yaml'). On success the newly created form is returned so
     * the frontend can append it to the list immediately.
     */
    #[Route('/import', name: 'api_forms_import', methods: ['POST'])]
    public function import(
        string $workspaceId,
        #[CurrentUser] User $user,
        #[MapRequestPayload] ImportFormRequest $dto,
    ): JsonResponse {
        $workspace = $this->workspaceRepository->find($workspaceId);

        if (null === $workspace) {
            return $this->json(['error' => 'Workspace not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(WorkspaceVoter::FORM_CREATE, $workspace);

        try {
            $data = match ($dto->format) {
                'yaml' => Yaml::parse($dto->content),
                default => json_decode($dto->content, true, 512, \JSON_THROW_ON_ERROR),
            };
        } catch (ParseException|\JsonException $e) {
            return $this->json(['error' => 'Invalid file content: ' . $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!\is_array($data) || !isset($data['title'])) {
            return $this->json(['error' => 'Missing required field: title.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $form = new Form();
        $form->setWorkspace($workspace);
        $form->setCreatedBy($user);
        $form->setTitle((string) $data['title']);

        if (isset($data['description']) && \is_string($data['description'])) {
            $form->setDescription($data['description']);
        }

        if (isset($data['status'])) {
            $status = FormStatus::tryFrom((string) $data['status']);
            if (null !== $status) {
                $form->setStatus($status);
            }
        }

        $this->em->persist($form);
        $this->em->flush();

        return $this->json($form, Response::HTTP_CREATED, [], ['groups' => ['form:read']]);
    }

    private function findFormInWorkspace(string $workspaceId, string $formId): Form|JsonResponse
    {
        $form = $this->formRepository->find($formId);

        if (null === $form || (string) $form->getWorkspace()?->getId() !== $workspaceId) {
            return $this->json(['error' => 'Form not found.'], Response::HTTP_NOT_FOUND);
        }

        return $form;
    }

    /**
     * Build the canonical export document for a form.
     *
     * The `sentinel_version` field allows future parsers to handle
     * breaking changes in the export schema gracefully.
     *
     * @return array<string, mixed>
     */
    /**
     * Restore the form to a specific revision.
     *
     * Updates the form's metadata to match the revision snapshot and sets it
     * as the current revision. No new revision is created — the history is
     * preserved intact and the pointer simply moves back.
     */
    #[Route('/{formId}/revisions/{revisionId}/restore', name: 'api_forms_revision_restore', methods: ['POST'])]
    public function restoreRevision(string $workspaceId, string $formId, string $revisionId): JsonResponse
    {
        $form = $this->findFormInWorkspace($workspaceId, $formId);

        if ($form instanceof JsonResponse) {
            return $form;
        }

        $this->denyAccessUnlessGranted(FormVoter::EDIT, $form);

        $revision = $this->formRevisionRepository->find($revisionId);

        if (null === $revision || (string) $revision->getForm()?->getId() !== $formId) {
            return $this->json(['error' => 'Revision not found.'], Response::HTTP_NOT_FOUND);
        }

        // Restore metadata from the snapshot (fall back to current value when null,
        // which happens for revisions created before metadata snapshotting was added).
        if (null !== $revision->getTitle()) {
            $form->setTitle($revision->getTitle());
        }
        if (null !== $revision->getDescription()) {
            $form->setDescription($revision->getDescription());
        }
        if (null !== $revision->getStatus()) {
            $form->setStatus($revision->getStatus());
        }

        $form->setCurrentRevision($revision);
        $this->em->flush();

        return $this->json($form, context: ['groups' => ['form:read']]);
    }

    /**
     * Export multiple forms at once as a single combined JSON or YAML file.
     *
     * Accepts a JSON body with `ids` (array of form UUIDs) and `format`.
     * Returns a bulk document: {sentinel_version, forms: [...]}.
     */
    #[Route('/export-bulk', name: 'api_forms_export_bulk', methods: ['POST'])]
    public function exportBulk(string $workspaceId, Request $request): Response
    {
        $workspace = $this->workspaceRepository->find($workspaceId);

        if (null === $workspace) {
            return $this->json(['error' => 'Workspace not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('workspace_view', $workspace);

        try {
            /** @var array{ids?: mixed, format?: mixed} $payload */
            $payload = json_decode((string) $request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $ids = $payload['ids'] ?? [];
        $format = \is_string($payload['format'] ?? null) ? $payload['format'] : 'json';

        if (!\is_array($ids) || 0 === \count($ids)) {
            return $this->json(['error' => 'No form IDs provided.'], Response::HTTP_BAD_REQUEST);
        }

        if (!\in_array($format, ['json', 'yaml'], true)) {
            return $this->json(['error' => 'Invalid format. Use json or yaml.'], Response::HTTP_BAD_REQUEST);
        }

        $forms = [];
        foreach ($ids as $id) {
            $form = $this->formRepository->find((string) $id);
            if (null !== $form && (string) $form->getWorkspace()?->getId() === $workspaceId) {
                $this->denyAccessUnlessGranted(FormVoter::VIEW, $form);
                $forms[] = $this->buildExportPayload($form);
            }
        }

        $data = ['sentinel_version' => '1.0', 'forms' => $forms];

        if ('yaml' === $format) {
            $content = Yaml::dump($data, 5, 2);
            $mime = 'application/yaml';
            $filename = 'forms-export.yaml';
        } else {
            $content = json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE) ?: '{}';
            $mime = 'application/json';
            $filename = 'forms-export.json';
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Import multiple forms from a bulk export file.
     *
     * Accepts the same payload as the single import but expects the file to
     * contain a `forms` array at the top level. Each entry is created as a
     * new Form. Returns the array of created forms.
     */
    #[Route('/import-bulk', name: 'api_forms_import_bulk', methods: ['POST'])]
    public function importBulk(
        string $workspaceId,
        #[CurrentUser] User $user,
        #[MapRequestPayload] ImportFormRequest $dto,
    ): JsonResponse {
        $workspace = $this->workspaceRepository->find($workspaceId);

        if (null === $workspace) {
            return $this->json(['error' => 'Workspace not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(WorkspaceVoter::FORM_CREATE, $workspace);

        try {
            $data = match ($dto->format) {
                'yaml' => Yaml::parse($dto->content),
                default => json_decode($dto->content, true, 512, \JSON_THROW_ON_ERROR),
            };
        } catch (ParseException|\JsonException $e) {
            return $this->json(['error' => 'Invalid file content: ' . $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!\is_array($data) || !isset($data['forms']) || !\is_array($data['forms'])) {
            return $this->json(['error' => 'Missing required field: forms[].'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $created = [];
        foreach ($data['forms'] as $entry) {
            if (!\is_array($entry) || !isset($entry['title'])) {
                continue;
            }

            $form = new Form();
            $form->setWorkspace($workspace);
            $form->setCreatedBy($user);
            $form->setTitle((string) $entry['title']);

            if (isset($entry['description']) && \is_string($entry['description'])) {
                $form->setDescription($entry['description']);
            }

            if (isset($entry['status'])) {
                $status = FormStatus::tryFrom((string) $entry['status']);
                if (null !== $status) {
                    $form->setStatus($status);
                }
            }

            $this->em->persist($form);
            $created[] = $form;
        }

        $this->em->flush();

        return $this->json($created, Response::HTTP_CREATED, [], ['groups' => ['form:read']]);
    }

    /** @return array<string, mixed> */
    private function buildExportPayload(Form $form): array
    {
        return [
            'sentinel_version' => '1.0',
            'title' => $form->getTitle(),
            'description' => $form->getDescription(),
            'status' => $form->getStatus()->value,
            'stages' => [],
        ];
    }
}
