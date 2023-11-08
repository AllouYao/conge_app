<?php

namespace App\Repository\Impots;

use App\Entity\Impots\CategoryCharge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryCharge>
 *
 * @method CategoryCharge|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategoryCharge|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategoryCharge[]    findAll()
 * @method CategoryCharge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryChargeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryCharge::class);
    }

//    /**
//     * @return CategoryCharge[] Returns an array of CategoryCharge objects
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

//    public function findOneBySomeField($value): ?CategoryCharge
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
