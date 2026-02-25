<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMember;
use App\Enum\UserRole;
use App\Enum\WorkspaceRole;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCreateUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setPassword('hashed_password');

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('testuser', $user->getUsername());
        $this->assertSame('hashed_password', $user->getPassword());
        $this->assertNull($user->getId());
    }

    public function testUserIdentifierIsEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testDefaultRoleIsUser(): void
    {
        $user = new User();

        $this->assertContains(UserRole::User->value, $user->getRoles());
    }

    public function testSetRoles(): void
    {
        $user = new User();
        $user->setRoles([UserRole::SuperAdmin->value]);

        $roles = $user->getRoles();
        $this->assertContains(UserRole::SuperAdmin->value, $roles);
        $this->assertContains(UserRole::User->value, $roles);
    }

    public function testOptionalNameFields(): void
    {
        $user = new User();

        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());

        $user->setFirstName('John');
        $user->setLastName('Doe');

        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
    }

    public function testMustChangePasswordDefaultsFalse(): void
    {
        $user = new User();

        $this->assertFalse($user->isMustChangePassword());
    }

    public function testSetMustChangePassword(): void
    {
        $user = new User();
        $user->setMustChangePassword(true);

        $this->assertTrue($user->isMustChangePassword());
    }

    public function testWorkspaceMembershipsInitiallyEmpty(): void
    {
        $user = new User();

        $this->assertCount(0, $user->getWorkspaceMemberships());
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->setPassword('secret');

        $user->eraseCredentials();

        // Password should remain (eraseCredentials clears temp data, not the stored hash)
        $this->assertSame('secret', $user->getPassword());
    }
}
