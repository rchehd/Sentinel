<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateFormRequest;
use App\Dto\UpdateFormRequest;
use App\Entity\Form;
use App\Entity\User;
use App\Enum\FormStatus;
use App\Repository\FormRepository;
use App\Repository\WorkspaceRepository;
use App\Security\FormVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/workspaces/{workspaceId}/forms')]
class FormController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkspaceRepository $workspaceRepository,
        private readonly FormRepository $formRepository,
    ) {
    }

    #[Route('', name: 'api_forms_list', methods: ['GET'])]
    public function index(string $workspaceId, #[CurrentUser] User $user): JsonResponse
    {
        $workspace = $this->workspaceRepository->find($workspaceId);

        if ($workspace === null) {
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

        if ($workspace === null) {
            return $this->json(['error' => 'Workspace not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(FormVoter::EDIT, $this->makeDummyForm($workspace));

        $form = new Form();
        $form->setWorkspace($workspace);
        $form->setCreatedBy($user);
        $form->setTitle($dto->title);

        if ($dto->description !== null) {
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

        if ($dto->title !== null) {
            $form->setTitle($dto->title);
        }

        if ($dto->description !== null) {
            $form->setDescription($dto->description);
        }

        if ($dto->status !== null) {
            $form->setStatus(FormStatus::from($dto->status));
        }

        if ($dto->schema !== null) {
            $form->setSchema($dto->schema);
        }

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

    private function findFormInWorkspace(string $workspaceId, string $formId): Form|JsonResponse
    {
        $form = $this->formRepository->find($formId);

        if ($form === null || (string) $form->getWorkspace()?->getId() !== $workspaceId) {
            return $this->json(['error' => 'Form not found.'], Response::HTTP_NOT_FOUND);
        }

        return $form;
    }

    /**
     * Creates a transient Form bound to the workspace solely for the voter check on creation.
     * The form is never persisted.
     */
    private function makeDummyForm(\App\Entity\Workspace $workspace): Form
    {
        $dummy = new Form();
        $dummy->setWorkspace($workspace);

        return $dummy;
    }
}
