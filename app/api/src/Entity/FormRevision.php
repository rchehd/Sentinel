<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FormStatus;
use App\Repository\FormRevisionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FormRevisionRepository::class)]
#[ORM\Table(name: 'form_revision')]
class FormRevision
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['form:read', 'form:revision:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'revisions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Form $form = null;

    /** @var array<mixed> */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['form:read', 'form:revision:read'])]
    private array $schema = [];

    /** Snapshot of the form title at the time this revision was saved. */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['form:read', 'form:revision:read'])]
    private ?string $title = null;

    /** Snapshot of the form description at the time this revision was saved. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['form:revision:read'])]
    private ?string $description = null;

    /** Snapshot of the form status at the time this revision was saved. */
    #[ORM\Column(length: 20, enumType: FormStatus::class, nullable: true)]
    #[Groups(['form:revision:read'])]
    private ?FormStatus $status = null;

    #[ORM\Column]
    #[Groups(['form:read', 'form:revision:read'])]
    private int $version = 1;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['form:revision:read'])]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['form:read', 'form:revision:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function setForm(Form $form): static
    {
        $this->form = $form;

        return $this;
    }

    /** @return array<mixed> */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /** @param array<mixed> $schema */
    public function setSchema(array $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
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

    public function getStatus(): ?FormStatus
    {
        return $this->status;
    }

    public function setStatus(?FormStatus $status): static
    {
        $this->status = $status;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
