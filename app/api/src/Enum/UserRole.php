<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRole: string
{
    case User = 'ROLE_USER';
    case OrgMember = 'ROLE_ORG_MEMBER';
    case OrgOwner = 'ROLE_ORG_OWNER';
    case SuperAdmin = 'ROLE_SUPER_ADMIN';
}
