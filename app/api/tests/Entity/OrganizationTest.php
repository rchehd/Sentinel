<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Organization;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class OrganizationTest extends TestCase
{
    public function testCreateOrganization(): void
    {
        $organization = new Organization();
        $organization->setLabel('Test Org');
        $organization->setDomain('test.com');

        $this->assertSame('Test Org', $organization->getLabel());
        $this->assertSame('test.com', $organization->getDomain());
        $this->assertNull($organization->getId());
    }

    public function testOrganizationDomainIsNullable(): void
    {
        $organization = new Organization();
        $organization->setLabel('No Domain Org');

        $this->assertNull($organization->getDomain());
    }

    public function testAddAndRemoveMembers(): void
    {
        $organization = new Organization();
        $organization->setLabel('Org');

        $user = new User();
        $user->setEmail('member@example.com');
        $user->setUsername('member');
        $user->setPassword('password');

        $organization->addMember($user);

        $this->assertCount(1, $organization->getMembers());
        $this->assertSame($organization, $user->getOrganization());

        $organization->removeMember($user);

        $this->assertCount(0, $organization->getMembers());
        $this->assertNull($user->getOrganization());
    }

    public function testAddMemberIsIdempotent(): void
    {
        $organization = new Organization();
        $organization->setLabel('Org');

        $user = new User();
        $user->setEmail('member@example.com');
        $user->setUsername('member');
        $user->setPassword('password');

        $organization->addMember($user);
        $organization->addMember($user);

        $this->assertCount(1, $organization->getMembers());
    }
}
