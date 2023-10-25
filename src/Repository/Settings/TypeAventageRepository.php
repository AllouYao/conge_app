<?php

namespace App\Repository\Settings;

use App\Entity\Settings\TypeAventage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeAventage>
 *
 * @method TypeAventage|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeAventage|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeAventage[]    findAll()
 * @method TypeAventage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeAventageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeAventage::class);
    }

//    /**
//     * @return TypeAventage[] Returns an array of TypeAventage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TypeAventage
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
