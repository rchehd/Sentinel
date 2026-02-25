<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateMemberRoleRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['owner', 'admin', 'editor', 'viewer'])]
        public readonly string $role,
    ) {
    }
}
