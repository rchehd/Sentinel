<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateFormRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public readonly string $title,
        #[Assert\Length(max: 2000)]
        public readonly ?string $description = null,
    ) {
    }
}
