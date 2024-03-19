<?php

namespace App\Repository\DevPaie;

use App\Entity\DevPaie\CongePartiel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CongePartiel>
 *
 * @method CongePartiel|null find($id, $lockMode = null, $lockVersion = null)
 * @method CongePartiel|null findOneBy(array $criteria, array $orderBy = null)
 * @method CongePartiel[]    findAll()
 * @method CongePartiel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CongePartielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CongePartiel::class);
    }

//    /**
//     * @return CongePartiel[] Returns an array of CongePartiel objects
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

//    public function findOneBySomeField($value): ?CongePartiel
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
