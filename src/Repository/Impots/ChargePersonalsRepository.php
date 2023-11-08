<?php

namespace App\Repository\Impots;

use App\Entity\Impots\ChargePersonals;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChargePersonals>
 *
 * @method ChargePersonals|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChargePersonals|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChargePersonals[]    findAll()
 * @method ChargePersonals[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChargePersonalsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChargePersonals::class);
    }

//    /**
//     * @return ChargePersonals[] Returns an array of ChargePersonals objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ChargePersonals
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
