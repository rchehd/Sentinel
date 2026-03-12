<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SubmissionStatus;
use App\Repository\SubmissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
#[ORM\Table(name: 'submission')]
#[ORM\UniqueConstraint(name: 'uniq_submission_auth_draft', columns: ['form_id', 'submitted_by_id'], options: ['where' => "(status = 'draft')"])]
#[ORM\UniqueConstraint(name: 'uniq_submission_anon_draft', columns: ['form_id', 'session_token'], options: ['where' => "(status = 'draft')"])]
#[ORM\HasLifecycleCallbacks]
class Submission
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['submission:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Form::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Form $form = null;

    #[ORM\ManyToOne(targetEntity: FormRevision::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['submission:read'])]
    private ?FormRevision $revision = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['submission:read'])]
    private ?User $submittedBy = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $sessionToken = null;

    #[ORM\Column(length: 20, enumType: SubmissionStatus::class)]
    #[Groups(['submission:read'])]
    private SubmissionStatus $status = SubmissionStatus::Draft;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['submission:read'])]
    private array $answers = [];

    #[ORM\Column(nullable: true)]
    #[Groups(['submission:read'])]
    private ?int $scoreTotal = null;

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

    public function getRevision(): ?FormRevision
    {
        return $this->revision;
    }

    public function setRevision(FormRevision $revision): static
    {
        $this->revision = $revision;

        return $this;
    }

    public function getSubmittedBy(): ?User
    {
        return $this->submittedBy;
    }

    public function setSubmittedBy(?User $user): static
    {
        $this->submittedBy = $user;

        return $this;
    }

    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function setSessionToken(?string $token): static
    {
        $this->sessionToken = $token;

        return $this;
    }

    public function getStatus(): SubmissionStatus
    {
        return $this->status;
    }

    public function setStatus(SubmissionStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /** @param array<string, mixed> $answers */
    public function setAnswers(array $answers): static
    {
        $this->answers = $answers;

        return $this;
    }

    public function getScoreTotal(): ?int
    {
        return $this->scoreTotal;
    }

    public function setScoreTotal(?int $score): static
    {
        $this->scoreTotal = $score;

        return $this;
    }
}
