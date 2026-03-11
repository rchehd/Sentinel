<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class AdminCreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public readonly string $username,
        #[Assert\Length(max: 100)]
        public readonly ?string $firstName = null,
        #[Assert\Length(max: 100)]
        public readonly ?string $lastName = null,
        #[Assert\Length(min: 8)]
        public readonly ?string $password = null,
        public readonly bool $mustChangePassword = true,
    ) {
    }
}
