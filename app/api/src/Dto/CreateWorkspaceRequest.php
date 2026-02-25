<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateWorkspaceRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $name,
        #[Assert\Length(max: 100)]
        public readonly ?string $slug = null,
    ) {
    }
}
