<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateWorkspaceRequest;
use App\Dto\UpdateWorkspaceRequest;
use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMember;
use App\Enum\WorkspaceRole;
use App\Repository\WorkspaceRepository;
use App\Security\WorkspaceVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/workspaces')]
class WorkspaceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkspaceRepository $workspaceRepository,
    ) {
    }

    #[Route('', name: 'api_workspaces_list', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $workspaces = $this->workspaceRepository->findByUser($user);

        return $this->json($workspaces, context: ['groups' => ['workspace:read']]);
    }

    #[Route('', name: 'api_workspaces_create', methods: ['POST'])]
    public function create(
        #[CurrentUser] User $user,
        #[MapRequestPayload] CreateWorkspaceRequest $dto,
    ): JsonResponse {
        if (null !== $this->workspaceRepository->findOneBy(['name' => $dto->name])) {
            return $this->json(
                ['errors' => ['name' => 'This workspace name is already taken.']],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (null !== $dto->slug && null !== $this->workspaceRepository->findOneBy(['slug' => $dto->slug])) {
            return $this->json(
                ['errors' => ['slug' => 'This slug is already taken.']],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $workspace = new Workspace();
        $workspace->setName($dto->name);

        if (null !== $dto->slug) {
            $workspace->setSlug($dto->slug);
        }

        $member = new WorkspaceMember();
        $member->setUser($user);
        $member->setRole(WorkspaceRole::Owner);
        $workspace->addMember($member);

        $this->em->persist($workspace);
        $this->em->flush();

        return $this->json($workspace, Response::HTTP_CREATED, [], ['groups' => ['workspace:read']]);
    }

    #[Route('/{id}', name: 'api_workspaces_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $workspace = $this->workspaceRepository->find($id);

        if (null === $workspace) {
            return $this->json(['error' => 'Workspace not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(WorkspaceVoter::VIEW, $workspace);

        return $this->json($workspace, context: ['groups' => ['workspace:read']]);
    }

    #[Route('/{id}', name: 'api_workspaces_update', methods: ['PATCH'])]
    public function update(
        string $id,
        #[MapRequestPayload] UpdateWorkspaceRequest $dto,
    ): JsonResponse {
        $workspace = $this->workspaceRepository->find($id);

        if (null === $workspace) {
            return $this->json(['error' => 'Workspace not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(WorkspaceVoter::EDIT, $workspace);

        if (null !== $dto->name) {
            $workspace->setName($dto->name);
        }

        if (null !== $dto->slug) {
            $workspace->setSlug($dto->slug);
        }

        $this->em->flush();

        return $this->json($workspace, context: ['groups' => ['workspace:read']]);
    }

    #[Route('/{id}', name: 'api_workspaces_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $workspace = $this->workspaceRepository->find($id);

        if (null === $workspace) {
            return $this->json(['error' => 'Workspace not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(WorkspaceVoter::DELETE, $workspace);

        $this->em->remove($workspace);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
