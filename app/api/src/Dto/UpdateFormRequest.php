<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\FormStatus;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateFormRequest
{
    /**
     * @param array<mixed>|null $schema
     */
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        public readonly ?string $title = null,
        #[Assert\Length(max: 2000)]
        public readonly ?string $description = null,
        public readonly ?FormStatus $status = null,
        public readonly ?array $schema = null,
    ) {
    }
}
