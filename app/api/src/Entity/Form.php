<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FormStatus;
use App\Repository\FormRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormRepository::class)]
#[ORM\Table(name: '`form`')]
#[ORM\HasLifecycleCallbacks]
class Form
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['form:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['form:read'])]
    private ?User $createdBy = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['form:read', 'form:write'])]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['form:read', 'form:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 20, enumType: FormStatus::class)]
    #[Groups(['form:read', 'form:write'])]
    private FormStatus $status = FormStatus::Draft;

    #[ORM\ManyToOne(targetEntity: FormRevision::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['form:read'])]
    private ?FormRevision $currentRevision = null;

    /** @var Collection<int, FormRevision> */
    #[ORM\OneToMany(targetEntity: FormRevision::class, mappedBy: 'form', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['version' => 'DESC'])]
    private Collection $revisions;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $user): static
    {
        $this->createdBy = $user;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): FormStatus
    {
        return $this->status;
    }

    public function setStatus(FormStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCurrentRevision(): ?FormRevision
    {
        return $this->currentRevision;
    }

    public function setCurrentRevision(?FormRevision $revision): static
    {
        $this->currentRevision = $revision;

        return $this;
    }

    /** @return Collection<int, FormRevision> */
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }
}
