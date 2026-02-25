<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SetupAdminDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    public string $username = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password = '';
}
