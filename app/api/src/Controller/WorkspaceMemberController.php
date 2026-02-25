<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\AddMemberRequest;
use App\Dto\UpdateMemberRoleRequest;
use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMember;
use App\Enum\WorkspaceRole;
use App\Repository\UserRepository;
use App\Repository\WorkspaceMemberRepository;
use App\Repository\WorkspaceRepository;
use App\Security\WorkspaceVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/workspaces/{workspaceId}/members')]
class WorkspaceMemberController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkspaceRepository $workspaceRepository,
        private readonly WorkspaceMemberRepository $memberRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('', name: 'api_workspace_members_list', methods: ['GET'])]
    public function index(string $workspaceId): JsonResponse
    {
        $workspace = $this->findWorkspaceOr404($workspaceId);

        if ($workspace instanceof JsonResponse) {
            return $workspace;
        }

        $this->denyAccessUnlessGranted(WorkspaceVoter::VIEW, $workspace);

        return $this->json($this->serializeMembers($workspace));
    }

    #[Route('', name: 'api_workspace_members_add', methods: ['POST'])]
    public function add(
        string $workspaceId,
        #[MapRequestPayload] AddMemberRequest $dto,
        #[CurrentUser] User $currentUser,
    ): JsonResponse {
        $workspace = $this->findWorkspaceOr404($workspaceId);

        if ($workspace instanceof JsonResponse) {
            return $workspace;
        }

        $this->denyAccessUnlessGranted(WorkspaceVoter::MANAGE_MEMBERS, $workspace);

        $user = $this->userRepository->find($dto->userId);

        if (null === $user) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        $existing = $this->memberRepository->findOneByWorkspaceAndUser($workspace, $user);

        if (null !== $existing) {
            return $this->json(
                ['error' => 'User is already a member of this workspace.'],
                Response::HTTP_CONFLICT,
            );
        }

        $member = new WorkspaceMember();
        $member->setUser($user);
        $member->setRole(WorkspaceRole::from($dto->role));
        $workspace->addMember($member);

        $this->em->flush();

        return $this->json($this->serializeMember($member), Response::HTTP_CREATED);
    }

    #[Route('/{memberId}', name: 'api_workspace_members_update', methods: ['PATCH'])]
    public function update(
        string $workspaceId,
        string $memberId,
        #[MapRequestPayload] UpdateMemberRoleRequest $dto,
    ): JsonResponse {
        $workspace = $this->findWorkspaceOr404($workspaceId);

        if ($workspace instanceof JsonResponse) {
            return $workspace;
        }

        $this->denyAccessUnlessGranted(WorkspaceVoter::MANAGE_MEMBERS, $workspace);

        $member = $this->memberRepository->find($memberId);

        if (null === $member || (string) $member->getWorkspace()?->getId() !== $workspaceId) {
            return $this->json(['error' => 'Member not found.'], Response::HTTP_NOT_FOUND);
        }

        $newRole = WorkspaceRole::from($dto->role);

        // Protect against demoting the last owner
        if (WorkspaceRole::Owner === $member->getRole() && WorkspaceRole::Owner !== $newRole) {
            $ownerCount = \count(array_filter(
                $workspace->getMembers()->toArray(),
                static fn (WorkspaceMember $m) => WorkspaceRole::Owner === $m->getRole(),
            ));

            if ($ownerCount <= 1) {
                return $this->json(
                    ['error' => 'Cannot demote the last owner of a workspace.'],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
        }

        $member->setRole($newRole);
        $this->em->flush();

        return $this->json($this->serializeMember($member));
    }

    #[Route('/{memberId}', name: 'api_workspace_members_remove', methods: ['DELETE'])]
    public function remove(
        string $workspaceId,
        string $memberId,
        #[CurrentUser] User $currentUser,
    ): JsonResponse {
        $workspace = $this->findWorkspaceOr404($workspaceId);

        if ($workspace instanceof JsonResponse) {
            return $workspace;
        }

        $member = $this->memberRepository->find($memberId);

        if (null === $member || (string) $member->getWorkspace()?->getId() !== $workspaceId) {
            return $this->json(['error' => 'Member not found.'], Response::HTTP_NOT_FOUND);
        }

        $isSelf = (string) $member->getUser()?->getId() === (string) $currentUser->getId();

        if (!$isSelf) {
            $this->denyAccessUnlessGranted(WorkspaceVoter::MANAGE_MEMBERS, $workspace);
        } else {
            // Self-leave: still requires VIEW access (must be a member)
            $this->denyAccessUnlessGranted(WorkspaceVoter::VIEW, $workspace);
        }

        // Protect against removing the last owner
        if (WorkspaceRole::Owner === $member->getRole()) {
            $ownerCount = \count(array_filter(
                $workspace->getMembers()->toArray(),
                static fn (WorkspaceMember $m) => WorkspaceRole::Owner === $m->getRole(),
            ));

            if ($ownerCount <= 1) {
                return $this->json(
                    ['error' => 'Cannot remove the last owner of a workspace.'],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
        }

        $workspace->removeMember($member);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function findWorkspaceOr404(string $id): Workspace|JsonResponse
    {
        $workspace = $this->workspaceRepository->find($id);

        if (null === $workspace) {
            return $this->json(['error' => 'Workspace not found.'], Response::HTTP_NOT_FOUND);
        }

        return $workspace;
    }

    /** @return array<int, array<string, mixed>> */
    private function serializeMembers(Workspace $workspace): array
    {
        return $workspace->getMembers()->map(
            fn (WorkspaceMember $m) => $this->serializeMember($m),
        )->toArray();
    }

    /** @return array<string, mixed> */
    private function serializeMember(WorkspaceMember $member): array
    {
        return [
            'id' => (string) $member->getId(),
            'userId' => (string) $member->getUser()?->getId(),
            'username' => $member->getUser()?->getUsername(),
            'email' => $member->getUser()?->getEmail(),
            'role' => $member->getRole()->value,
            'joinedAt' => $member->getJoinedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
