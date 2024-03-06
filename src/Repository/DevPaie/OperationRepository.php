<?php

namespace App\Repository\DevPaie;

use App\Entity\DevPaie\Operation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Operation>
 *
 * @method Operation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Operation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Operation[]    findAll()
 * @method Operation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    public function findOperationByType(?array $type): ?array
    {
        return $this->createQueryBuilder('o')
            ->join('o.personal', 'personal')
            ->where('o.typeOperations IN (:types)')
            ->setParameter('types', $type)
            ->groupBy('o.typeOperations')
            ->getQuery()
            ->getResult();
    }
}
