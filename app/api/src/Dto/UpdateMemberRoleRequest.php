<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\WorkspaceRole;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateMemberRoleRequest
{
    public function __construct(
        #[Assert\NotNull]
        public readonly WorkspaceRole $role,
    ) {
    }
}
