<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Form;
use App\Entity\FormRevision;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\FormStatus;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $form = new Form();

        $this->assertSame('', $form->getTitle());
        $this->assertNull($form->getDescription());
        $this->assertSame(FormStatus::Draft, $form->getStatus());
        $this->assertNull($form->getCurrentRevision());
        $this->assertNull($form->getWorkspace());
        $this->assertNull($form->getCreatedBy());
        $this->assertNull($form->getId());
    }

    public function testSetTitle(): void
    {
        $form = new Form();
        $form->setTitle('Contact Form');

        $this->assertSame('Contact Form', $form->getTitle());
    }

    public function testSetDescription(): void
    {
        $form = new Form();
        $form->setDescription('A simple contact form.');

        $this->assertSame('A simple contact form.', $form->getDescription());
    }

    public function testSetStatus(): void
    {
        $form = new Form();
        $form->setStatus(FormStatus::Published);

        $this->assertSame(FormStatus::Published, $form->getStatus());
    }

    public function testSetCurrentRevision(): void
    {
        $revision = new FormRevision();
        $form = new Form();
        $form->setCurrentRevision($revision);

        $this->assertSame($revision, $form->getCurrentRevision());
    }

    public function testSetWorkspace(): void
    {
        $workspace = new Workspace();
        $workspace->setName('My Workspace');

        $form = new Form();
        $form->setWorkspace($workspace);

        $this->assertSame($workspace, $form->getWorkspace());
    }

    public function testSetCreatedBy(): void
    {
        $user = new User();
        $form = new Form();
        $form->setCreatedBy($user);

        $this->assertSame($user, $form->getCreatedBy());
    }
}
