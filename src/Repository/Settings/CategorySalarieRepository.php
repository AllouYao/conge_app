<?php

namespace App\Repository\Settings;

use App\Entity\Settings\CategorySalarie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorySalarie>
 *
 * @method CategorySalarie|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategorySalarie|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategorySalarie[]    findAll()
 * @method CategorySalarie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategorySalarieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorySalarie::class);
    }

//    /**
//     * @return CategorySalarie[] Returns an array of CategorySalarie objects
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

//    public function findOneBySomeField($value): ?CategorySalarie
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
