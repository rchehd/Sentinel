<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Form;
use App\Entity\FormRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormRevision>
 */
class FormRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormRevision::class);
    }

    public function findLatestByForm(Form $form): ?FormRevision
    {
        return $this->findOneBy(['form' => $form], ['version' => 'DESC']);
    }

    /** @return FormRevision[] */
    public function findByForm(Form $form): array
    {
        return $this->findBy(['form' => $form], ['version' => 'DESC']);
    }

    public function getNextVersion(Form $form): int
    {
        $result = $this->createQueryBuilder('r')
            ->select('MAX(r.version)')
            ->where('r.form = :form')
            ->setParameter('form', $form)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $result ? 1 : (int) $result + 1;
    }
}
