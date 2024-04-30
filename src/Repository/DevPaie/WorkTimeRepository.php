<?php

namespace App\Repository\DevPaie;

use App\Entity\DevPaie\WorkTime;
use App\Utils\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkTime>
 *
 * @method WorkTime|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkTime|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkTime[]    findAll()
 * @method WorkTime[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkTimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkTime::class);
    }

    /** @return WorkTime[] */
    public function findWorkTimeObj(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.code IN (:code)')
            ->setParameter('code', [Status::NORMAL, Status::SUPPLEMENTAIRE])
            ->getQuery()
            ->getResult();
    }
}
