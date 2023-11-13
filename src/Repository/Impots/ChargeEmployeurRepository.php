<?php

namespace App\Repository\Impots;

use App\Entity\Impots\ChargeEmployeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChargeEmployeur>
 *
 * @method ChargeEmployeur|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChargeEmployeur|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChargeEmployeur[]    findAll()
 * @method ChargeEmployeur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChargeEmployeurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChargeEmployeur::class);
    }

//    /**
//     * @return ChargeEmployeur[] Returns an array of ChargeEmployeur objects
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

//    public function findOneBySomeField($value): ?ChargeEmployeur
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
