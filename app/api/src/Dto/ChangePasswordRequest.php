<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public readonly string $newPassword,
        #[Assert\NotBlank]
        public readonly string $confirmPassword,
        public readonly ?string $currentPassword = null,
    ) {
    }
}
