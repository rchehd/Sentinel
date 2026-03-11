<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Form;
use App\Entity\Workspace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Form>
 */
class FormRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Form::class);
    }

    /** @return Form[] */
    public function findByWorkspace(Workspace $workspace): array
    {
        return $this->findBy(['workspace' => $workspace], ['createdAt' => 'DESC']);
    }
}
