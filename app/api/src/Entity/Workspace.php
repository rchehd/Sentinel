<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WorkspaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkspaceRepository::class)]
#[ORM\Table(name: 'workspace')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'], message: 'This workspace name is already taken.')]
#[UniqueEntity(fields: ['slug'], message: 'This slug is already taken.')]
class Workspace
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['workspace:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['workspace:read', 'workspace:write'])]
    private string $name = '';

    #[ORM\Column(length: 100, unique: true, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['workspace:read', 'workspace:write'])]
    private ?string $slug = null;

    /** @var Collection<int, WorkspaceMember> */
    #[ORM\OneToMany(targetEntity: WorkspaceMember::class, mappedBy: 'workspace', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /** @return Collection<int, WorkspaceMember> */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(WorkspaceMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setWorkspace($this);
        }

        return $this;
    }

    public function removeMember(WorkspaceMember $member): static
    {
        $this->members->removeElement($member);

        return $this;
    }
}
