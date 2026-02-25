<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\WorkspaceRole;
use App\Repository\WorkspaceMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkspaceMemberRepository::class)]
#[ORM\Table(name: 'workspace_member')]
#[ORM\UniqueConstraint(name: 'uq_workspace_user', columns: ['workspace_id', 'user_id'])]
class WorkspaceMember
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['workspace_member:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'workspaceMemberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['workspace_member:read'])]
    private ?User $user = null;

    #[ORM\Column(length: 20, enumType: WorkspaceRole::class)]
    #[Groups(['workspace_member:read', 'workspace_member:write'])]
    private WorkspaceRole $role = WorkspaceRole::Viewer;

    #[ORM\Column(name: 'joined_at')]
    #[Groups(['workspace_member:read'])]
    private \DateTimeImmutable $joinedAt;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getWorkspace(): ?Workspace
    {
        return $this->workspace;
    }

    public function setWorkspace(?Workspace $workspace): static
    {
        $this->workspace = $workspace;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRole(): WorkspaceRole
    {
        return $this->role;
    }

    public function setRole(WorkspaceRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
