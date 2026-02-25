<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateWorkspaceRequest
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        public readonly ?string $name = null,
        #[Assert\Length(max: 100)]
        public readonly ?string $slug = null,
    ) {
    }
}
