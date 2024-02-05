<?php

namespace App\Repository\ElementVariable;

use App\Entity\ElementVariable\VariablePaie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VariablePaie>
 *
 * @method VariablePaie|null find($id, $lockMode = null, $lockVersion = null)
 * @method VariablePaie|null findOneBy(array $criteria, array $orderBy = null)
 * @method VariablePaie[]    findAll()
 * @method VariablePaie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VariablePaieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VariablePaie::class);
    }


    public function findOneByStatus($value): ?VariablePaie
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
